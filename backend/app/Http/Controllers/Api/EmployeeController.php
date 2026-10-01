<?php

namespace App\Http\Controllers\Api;

use App\Actions\ManageAppAccess;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Permission;
use App\Enums\WorkMode;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\RemoteWorkPeriod;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\BadgeRenderer;
use App\Support\PlanSeats;
use App\Support\StoredImage;
use App\Support\TenantRule;
use App\Support\VacationBalance;
use App\Support\Visibility;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employees = Visibility::employees($request->user())
            ->with('office:id,name', 'shift:id,name,starts_at,ends_at,day_schedules', 'area:id,name,color')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%")))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('area_id'), fn ($query, $areaId) => $query->where('area_id', $areaId))
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 25));

        $userIds = User::query()->where('company_id', $request->user()->company_id)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->pluck('id', 'employee_id');

        // Fuera del límite del plan: se listan, pero bloqueados
        $locked = PlanSeats::lockedEmployeeIds($request->user()->company);

        $employees->getCollection()->each(fn (Employee $employee) => $employee
            ->setAttribute('user_id', $userIds[$employee->id] ?? null)
            ->setAttribute('locked_by_plan', in_array($employee->id, $locked, true))
            ->setAttribute('needs_setup', $employee->needsSetup()));

        return response()->json($employees);
    }

    public function show(Request $request, Employee $employee, BadgeRenderer $renderer): JsonResponse
    {
        abort_unless(Visibility::canSeeEmployee($request->user(), $employee->id), 404);

        $employee->load('office', 'shift', 'area', 'managedAreas:id,name', 'remoteWorkPeriods', 'devices');

        if ($request->user()->can(Permission::PayrollManage->value)) {
            $employee->makeVisible('salary');
        }

        $user = User::query()->where('company_id', $employee->company_id)->where('employee_id', $employee->id)->first();

        return response()->json([
            ...$employee->toArray(),
            'locked_by_plan' => $employee->isActive() && PlanSeats::isLocked($request->user()->company, $employee->id),
            'needs_setup' => $employee->needsSetup(),
            'vacation' => VacationBalance::for($employee),
            'photo_url' => $this->photoUrl($employee),
            'badge_issued_at' => $employee->badge_issued_at,
            'badge_expires_on' => $employee->badge_expires_on,
            // QR del frente de la credencial, para previsualizarla en pantalla.
            'badge_qr' => $employee->badge_token ? $renderer->qrDataUri($employee->badge_token) : null,
            'user' => $this->userSummary($user),
        ]);
    }

    /**
     * Alta. Con "Acceso a la app" encendido basta su correo: se le crea el
     * usuario y recibe el código de empresa y una contraseña temporal.
     * Sin app, checa en el kiosko con su PIN o credencial.
     */
    public function store(Request $request, TenantManager $tenants, ManageAppAccess $appAccess): JsonResponse
    {
        $company = $tenants->currentOrFail();

        if (Employee::query()->where('status', EmployeeStatus::Active)->count() >= $company->employeeLimit()) {
            throw ValidationException::withMessages([
                'employee' => "Tu plan permite {$company->employeeLimit()} empleados activos. Agrega un bloque de empleados para continuar.",
            ]);
        }

        $data = $this->validated($request);

        $employee = new Employee($data);

        // Con app, el empleado crea su propio PIN en su primer acceso
        if (! empty($data['pin'])) {
            $employee->setPin($data['pin']);
        }

        $employee->save();
        $employee->issueBadge();

        if ($request->boolean('app_access')) {
            $appAccess->enable($employee, $data['email']);
        }

        return response()->json([
            ...$employee->load('office', 'shift', 'area')->toArray(),
            'app_access' => $request->boolean('app_access'),
        ], 201);
    }

    /**
     * Interruptor "Acceso a la app" en la ficha del empleado.
     */
    public function appAccess(Request $request, Employee $employee, ManageAppAccess $appAccess): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'email' => ['required_if:enabled,true', 'nullable', 'email', 'max:190'],
        ], [
            'email.required_if' => 'Escribe el correo del empleado para darle acceso a la app.',
        ]);

        if ($data['enabled']) {
            if (! $employee->isActive()) {
                throw ValidationException::withMessages(['enabled' => 'Solo los empleados activos pueden tener la app.']);
            }
            $appAccess->enable($employee, $data['email']);
            $employee->forceFill(['email' => $data['email']])->save();
        } else {
            $appAccess->disable($employee);
        }

        return response()->json(['user' => $this->userSummary($appAccess->userOf($employee))]);
    }

    public function resendAppAccess(Employee $employee, ManageAppAccess $appAccess): JsonResponse
    {
        $appAccess->resend($employee);

        return response()->json(['message' => 'Enviamos al empleado una nueva contraseña temporal.']);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $data = $this->validated($request, $employee);

        if (($data['status'] ?? null) === EmployeeStatus::Terminated->value && $employee->terminated_on === null) {
            $data['terminated_on'] = now()->toDateString();
        }

        $employee->fill($data);

        if (isset($data['pin'])) {
            $employee->setPin($data['pin']);
        }

        $employee->save();

        // Al dar de baja, también se bloquea su acceso a la app / web
        if ($employee->status === EmployeeStatus::Terminated) {
            User::query()->where('company_id', $employee->company_id)->where('employee_id', $employee->id)
                ->whereNull('blocked_at')->each(function (User $user) {
                    $user->forceFill(['blocked_at' => now()])->save();
                    $user->tokens()->delete();
                });
        }

        return response()->json($employee->load('office', 'shift', 'area'));
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->update(['status' => EmployeeStatus::Terminated, 'terminated_on' => now()->toDateString()]);
        $employee->delete();

        return response()->json(status: 204);
    }

    /**
     * Invalida la credencial anterior y genera un QR nuevo (credencial perdida).
     */
    public function reissueBadge(Employee $employee): JsonResponse
    {
        $employee->issueBadge();

        return response()->json(['message' => 'Credencial regenerada. La anterior ya no funciona.']);
    }

    public function badge(Request $request, Employee $employee, BadgeRenderer $renderer, TenantManager $tenants): Response
    {
        if (! $employee->badgeIsValid()) {
            $employee->issueBadge();
        }

        ActivityLogger::log('downloaded', $employee, "Descargó la credencial de {$employee->fullName()}");

        return $renderer->pdf([$employee->load('area', 'office')], $tenants->currentOrFail(), $renderer->orientation($request->query('orientation')))
            ->download("credencial-{$employee->employee_code}.pdf");
    }

    /**
     * Credencial tal como se ve en pantalla: el navegador captura el frente y
     * el reverso del preview y se pegan al PDF, idénticos al que se imprimió.
     *
     * @return Response<int, string>
     */
    public function badgeCapture(Request $request, Employee $employee, BadgeRenderer $renderer): Response
    {
        $data = $request->validate([
            'front' => ['required', 'string', 'max:4000000'],
            'back' => ['required', 'string', 'max:4000000'],
            'orientation' => ['nullable', 'string'],
        ]);

        ActivityLogger::log('downloaded', $employee, "Descargó la credencial de {$employee->fullName()}");

        return $renderer->capture($data['front'], $data['back'], $data['orientation'] ?? null)
            ->download("credencial-{$employee->employee_code}.pdf");
    }

    public function badges(Request $request, BadgeRenderer $renderer, TenantManager $tenants): Response
    {
        $request->validate([
            'employee_ids' => ['required', 'array', 'max:200'],
            'orientation' => ['nullable', 'string'],
        ]);

        $employees = Employee::query()->with('area', 'office')->whereIn('id', $request->input('employee_ids'))->get()
            ->each(fn (Employee $employee) => $employee->badgeIsValid() || $employee->issueBadge());

        ActivityLogger::log('downloaded', description: "Descargó {$employees->count()} credenciales para imprimir");

        return $renderer->pdf($employees->all(), $tenants->currentOrFail(), $renderer->orientation($request->input('orientation')))
            ->download('credenciales.pdf');
    }

    public function storeRemotePeriod(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($employee->remoteWorkPeriods()->create($data), 201);
    }

    public function destroyRemotePeriod(Employee $employee, RemoteWorkPeriod $period): JsonResponse
    {
        abort_unless($period->employee_id === $employee->id, 404);
        $period->delete();

        return response()->json(status: 204);
    }

    /**
     * Foto del empleado: se ve en su ficha y en la credencial. El gerente
     * solo la ve si puede ver al empleado y solo su equipo puede cambiarla.
     */
    public function photo(Request $request, Employee $employee, StoredImage $storedImage): Response
    {
        abort_unless(Visibility::canSeeEmployee($request->user(), $employee->id), 404);

        $response = $storedImage->response($employee->photo_path);

        abort_if($response === null, 404);

        return $response;
    }

    public function updatePhoto(Request $request, Employee $employee, StoredImage $storedImage): JsonResponse
    {
        abort_unless(Visibility::canSeeEmployee($request->user(), $employee->id), 404);

        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'photo.required' => 'Selecciona una imagen.',
            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'Usa una imagen JPG, PNG o WebP.',
            'photo.max' => 'La foto no puede pesar más de 2 MB.',
        ]);

        $employee->forceFill([
            'photo_path' => $storedImage->store($data['photo'], $employee->company_id, 'photos', $employee->photo_path),
        ])->save();

        ActivityLogger::log('updated', $employee, 'Cambió la foto de '.$employee->fullName());

        return response()->json([
            'message' => 'Foto actualizada.',
            'photo_url' => $this->photoUrl($employee),
        ]);
    }

    public function destroyPhoto(Request $request, Employee $employee, StoredImage $storedImage): JsonResponse
    {
        abort_unless(Visibility::canSeeEmployee($request->user(), $employee->id), 404);

        $storedImage->delete($employee->photo_path);
        $employee->forceFill(['photo_path' => null])->save();

        ActivityLogger::log('updated', $employee, 'Quitó la foto de '.$employee->fullName());

        return response()->json([
            'message' => 'Foto eliminada.',
            'photo_url' => null,
        ]);
    }

    private function photoUrl(Employee $employee): ?string
    {
        return StoredImage::url('web.empleados.foto.ver', $employee->photo_path, ['employee' => $employee]);
    }

    private function userSummary(?User $user): ?array
    {
        return $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->primaryRole()->value,
            'role_label' => $user->primaryRole()->label(),
            'app_access' => $user->app_access,
            'web_access' => $user->web_access,
            'must_change_password' => $user->must_change_password,
            'blocked_at' => $user->blocked_at,
            'last_login_at' => $user->last_login_at,
        ] : null;
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $required = $employee ? 'sometimes' : 'required';

        // El sueldo solo lo toca quien administra sueldos
        if (! $request->user()->can(Permission::PayrollManage->value)) {
            $request->request->remove('salary');
            $request->request->remove('salary_period');
            $request->json()?->remove('salary');
            $request->json()?->remove('salary_period');
        }

        return $request->validate([
            'first_name' => [$required, 'string', 'max:80'],
            'last_name' => [$required, 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:120'],
            'office_id' => [$required, TenantRule::exists('offices')],
            'shift_id' => [$required, TenantRule::exists('shifts')->where('office_id', $request->input('office_id', $employee?->office_id))],
            'area_id' => [$required, TenantRule::exists('areas')],
            'status' => ['sometimes', Rule::enum(EmployeeStatus::class)],
            'employment_type' => [$required, Rule::enum(EmploymentType::class)],
            'work_mode' => [$required, Rule::enum(WorkMode::class)],
            'hired_on' => ['nullable', 'date'],
            'contract_ends_on' => ['nullable', 'date', 'required_if:employment_type,temporary'],
            'badge_expires_on' => ['nullable', 'date'],
            'app_access' => ['sometimes', 'boolean'],
            // Con app, el correo es su usuario
            'email' => [Rule::requiredIf(! $employee && $request->boolean('app_access')), 'nullable', 'email', 'max:190'],
            // PIN para el kiosko: obligatorio en el alta si no tendrá app (con app lo crea él)
            'pin' => [$employee ? 'sometimes' : Rule::requiredIf(! $request->boolean('app_access')), 'nullable', 'digits:6'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            // daily: sueldo diario (el más común en México)
            'salary_period' => ['nullable', 'in:daily,weekly,biweekly,monthly'],
        ], [
            'shift_id.exists' => 'El turno debe pertenecer a la oficina seleccionada.',
            'contract_ends_on.required_if' => 'Los empleados temporales necesitan fecha de fin de contrato.',
            'email.required' => 'Escribe el correo del empleado para darle acceso a la app.',
            'pin.required' => 'Asigna un PIN de 6 dígitos para que pueda checar en el kiosko.',
            'pin.digits' => 'El PIN debe tener 6 dígitos.',
        ]);
    }
}
