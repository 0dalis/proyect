<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Mail\CredencialesEmpleadoMail;
use App\Models\Area;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use App\Services\PlanLimitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyCompleteController extends Controller
{
    public function __construct(
        private PlanLimitsService $limits
    ) {}

    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function status(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        return response()->json([
            'setup_step' => $company->setup_step,
            'is_active' => $company->is_active,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'slug' => $company->slug,
                'logo_path' => $company->logo_path,
            ],
            'offices_count' => $company->offices()->count(),
            'areas_count' => $company->areas()->count(),
            'employees_count' => $company->employees()->count(),
        ]);
    }

    public function limits(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        return response()->json($this->limits->summary($company));
    }

    public function officeLimit(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $limit = $this->limits->officeLimit($company);
        $count = $this->limits->countOffices($company);
        $plan = $this->limits->planFor($company);

        return response()->json([
            'plan_name' => $plan?->nombre,
            'plan_id' => $plan?->id,
            'office_limit' => $limit,
            'office_count' => $count,
            'available' => $limit === null ? null : max(0, $limit - $count),
            'can_create' => $this->limits->canCreateOffice($company),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($data);

        return response()->json([
            'message' => 'Perfil de empresa actualizado correctamente.',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'slug' => $company->slug,
                'logo_path' => $company->logo_path,
            ],
        ]);
    }

    public function offices(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $offices = $company->offices()->orderBy('name')->get();

        return response()->json(['offices' => $offices]);
    }

    public function storeOffice(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $this->limits->canCreateOffice($company)) {
            $limit = $this->limits->officeLimit($company);

            return response()->json([
                'message' => "Alcanzaste el límite de oficinas de tu plan ($limit).",
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'timezone' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $office = $company->offices()->create([
            'name' => $request->name,
            'code' => $request->code,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters,
            'timezone' => $request->timezone ?? 'UTC',
            'country' => $request->country,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Oficina creada correctamente.',
            'office' => $office,
        ], 201);
    }

    public function updateOffice(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $office = $company->offices()->findOrFail($id);

        if (! $this->limits->canEditOffice($company, $office)) {
            return response()->json([
                'message' => 'Las oficinas que exceden tu plan solo pueden eliminarse.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'timezone' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $office->update($request->only([
            'name', 'code', 'latitude', 'longitude', 'radius_meters', 'timezone', 'country', 'is_active',
        ]));

        return response()->json([
            'message' => 'Oficina actualizada correctamente.',
            'office' => $office,
        ]);
    }

    public function deleteOffice(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $office = $company->offices()->findOrFail($id);

        if ($company->offices()->count() <= 1) {
            return response()->json([
                'message' => 'Debes mantener al menos una oficina.',
            ], 422);
        }

        if ($office->employees()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la oficina porque tiene empleados asignados.',
            ], 422);
        }

        $office->delete();

        return response()->json(['message' => 'Oficina eliminada correctamente.']);
    }

    public function areas(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $areas = $company->areas()->orderBy('name')->get();

        return response()->json(['areas' => $areas]);
    }

    public function storeArea(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $area = $company->areas()->create([
            'name' => $request->name,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Área creada correctamente.',
            'area' => $area,
        ], 201);
    }

    public function updateArea(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $area = $company->areas()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $area->update($request->only(['name', 'is_active']));

        return response()->json([
            'message' => 'Área actualizada correctamente.',
            'area' => $area,
        ]);
    }

    public function deleteArea(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $area = $company->areas()->findOrFail($id);

        if ($area->employees()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el área porque tiene empleados asignados.',
            ], 422);
        }

        $area->delete();

        return response()->json(['message' => 'Área eliminada correctamente.']);
    }

    public function employees(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $employees = $company->employees()
            ->with(['office', 'area', 'user'])
            ->where(function ($query) {
                $query->whereNull('user_id')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->whereDoesntHave('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'owner');
                        });
                    });
            })
            ->orderBy('first_name')
            ->get();

        return response()->json(['employees' => $employees]);
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $this->limits->canCreateEmployee($company)) {
            $limit = $this->limits->employeeLimit($company);

            return response()->json([
                'message' => "Alcanzaste el límite de empleados de tu plan ($limit).",
            ], 422);
        }

        $hasAreas = $company->areas()->exists();
        $hasAppAccess = $request->boolean('has_app_access', false);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:10|regex:/^[A-Z0-9]+$/|
                unique:employees,employee_code,NULL,id,company_id,' . $company->id,
            'office_id' => 'required|exists:offices,id',
            'area_id' => $hasAreas ? 'required|exists:areas,id' : 'nullable|exists:areas,id',
            'is_area_manager' => 'boolean',
            'has_app_access' => 'boolean',
            'email' => $hasAppAccess
                ? 'required|email|max:255|unique:users,email'
                : 'nullable|email|max:255',
        ], [
            'email.unique' => 'Ese correo ya está en uso, usa otro correo diferente.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->boolean('is_area_manager') && ! $request->filled('area_id')) {
            return response()->json([
                'errors' => ['area_id' => ['Para asignar un gerente de área primero debes seleccionar un área.']],
            ], 422);
        }

        if ($request->boolean('is_area_manager') && $company->employees()
            ->where('area_id', $request->area_id)
            ->where('is_area_manager', true)
            ->exists()) {
            return response()->json([
                'errors' => ['area_id' => ['El área seleccionada ya tiene un gerente asignado.']],
            ], 422);
        }

        $employee = DB::transaction(function () use ($company, $request, $hasAppAccess) {
            $employee = $company->employees()->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'employee_code' => $request->employee_code,
                'office_id' => $request->office_id,
                'area_id' => $request->area_id,
                'is_area_manager' => $request->boolean('is_area_manager', false),
                'pin' => '',
                'is_active' => true,
            ]);

            if ($hasAppAccess && $request->email) {
                $password = Str::random(12);

                $user = User::create([
                    'company_id' => $company->id,
                    'email' => $request->email,
                    'password' => $password,
                    'pending_password' => $password,
                    'is_active' => true,
                ]);

                $user->assignRole('employee');
                $employee->update(['user_id' => $user->id]);
            }

            return $employee;
        });

        $employee->load(['office', 'area', 'user']);

        return response()->json([
            'message' => 'Empleado creado correctamente.',
            'employee' => $employee,
        ], 201);
    }

    public function updateEmployee(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        if ($employee->user_id && $employee->user->hasRole('owner')) {
            return response()->json([
                'message' => 'El owner de la empresa no puede modificarse desde esta vista.',
            ], 422);
        }

        if (! $this->limits->canEditEmployee($company, $employee)) {
            return response()->json([
                'message' => 'Los empleados que exceden tu plan solo pueden eliminarse.',
            ], 422);
        }

        $hasAreas = $company->areas()->exists();
        $hasAppAccess = $request->boolean('has_app_access', false);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:10|regex:/^[A-Z0-9]+$/|
                unique:employees,employee_code,' . $employee->id . ',id,company_id,' . $company->id,
            'office_id' => 'required|exists:offices,id',
            'area_id' => $hasAreas ? 'required|exists:areas,id' : 'nullable|exists:areas,id',
            'is_area_manager' => 'boolean',
            'is_active' => 'boolean',
            'has_app_access' => 'boolean',
            'email' => $hasAppAccess
                ? 'required|email|max:255|unique:users,email,' . ($employee->user_id ?? 'NULL')
                : 'nullable|email|max:255',
        ], [
            'email.unique' => 'Ese correo ya está en uso, usa otro correo diferente.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->boolean('is_area_manager') && ! $request->filled('area_id')) {
            return response()->json([
                'errors' => ['area_id' => ['Para asignar un gerente de área primero debes seleccionar un área.']],
            ], 422);
        }

        if ($request->boolean('is_area_manager') && $company->employees()
            ->where('area_id', $request->area_id)
            ->where('is_area_manager', true)
            ->where('id', '!=', $employee->id)
            ->exists()) {
            return response()->json([
                'errors' => ['area_id' => ['El área seleccionada ya tiene un gerente asignado.']],
            ], 422);
        }

        $employee = DB::transaction(function () use ($company, $request, $employee, $hasAppAccess) {
            $employee->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'employee_code' => $request->employee_code,
                'office_id' => $request->office_id,
                'area_id' => $request->area_id,
                'is_area_manager' => $request->boolean('is_area_manager', false),
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($hasAppAccess && $request->email && ! $employee->user_id) {
                $password = Str::random(12);

                $user = User::create([
                    'company_id' => $company->id,
                    'email' => $request->email,
                    'password' => $password,
                    'pending_password' => $password,
                    'is_active' => true,
                ]);

                $user->assignRole('employee');
                $employee->update(['user_id' => $user->id]);
            } elseif ($hasAppAccess && $employee->user_id && $request->email) {
                $employee->user->update(['email' => $request->email]);
            } elseif (! $hasAppAccess && $employee->user_id) {
                $employee->user->delete();
                $employee->update(['user_id' => null]);
            }

            return $employee;
        });

        $employee->load(['office', 'area', 'user']);

        return response()->json([
            'message' => 'Empleado actualizado correctamente.',
            'employee' => $employee,
        ]);
    }

    public function deleteEmployee(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        if ($employee->user_id && $employee->user->hasRole('owner')) {
            return response()->json([
                'message' => 'El owner de la empresa no puede eliminarse.',
            ], 422);
        }

        if ($employee->user_id) {
            $employee->user->delete();
        }

        $employee->delete();

        return response()->json(['message' => 'Empleado eliminado correctamente.']);
    }

    public function generateEmployeeCode(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        return response()->json([
            'code' => $this->uniqueEmployeeCode($company),
        ]);
    }

    private function uniqueEmployeeCode(Company $company): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $prefix = Str::substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(Str::ascii($company->name))), 0, 4);
        if ($prefix === '') {
            $prefix = 'EMP';
        }

        $maxAttempts = 30;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = Str::substr($prefix . substr(str_shuffle($alphabet), 0, 6), 0, 10);

            if (! $company->employees()->where('employee_code', $code)->exists()) {
                return $code;
            }
        }

        return Str::substr($prefix . substr(str_shuffle($alphabet), 0, 6), 0, 10);
    }

    public function nextStep(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $currentStep = (int) $request->input('step', $company->setup_step);

        $nextStep = match ($currentStep) {
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            default => $currentStep,
        };

        $company->update(['setup_step' => $nextStep]);

        return response()->json([
            'message' => 'Paso avanzado correctamente.',
            'setup_step' => $nextStep,
        ]);
    }

    public function previousStep(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $currentStep = (int) $request->input('step', $company->setup_step);

        $prevStep = match ($currentStep) {
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            default => 1,
        };

        $company->update(['setup_step' => $prevStep]);

        return response()->json([
            'message' => 'Paso retrocedido correctamente.',
            'setup_step' => $prevStep,
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $officesCount = $company->offices()->count();
        $employeesCount = $this->limits->countEmployees($company);

        if ($officesCount === 0 || $employeesCount === 0) {
            return response()->json([
                'message' => 'Debes completar todos los pasos antes de finalizar.',
                'missing' => array_filter([
                    $officesCount === 0 ? 'Debes crear al menos una oficina.' : null,
                    $employeesCount === 0 ? 'Debes crear al menos un empleado.' : null,
                ]),
            ], 422);
        }

        $company->update([
            'is_active' => true,
            'setup_step' => 5,
        ]);

        $emailsSent = $this->sendPendingCredentials($company);

        return response()->json([
            'message' => '¡Configuración completada! Tu empresa ya está activa.',
            'is_active' => true,
            'emails_sent' => $emailsSent,
        ]);
    }

    private function sendPendingCredentials(Company $company): int
    {
        $sent = 0;

        $employees = $company->employees()
            ->whereNotNull('user_id')
            ->whereHas('user', function ($query) {
                $query->whereNotNull('pending_password')
                    ->whereDoesntHave('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'owner');
                    });
            })
            ->with('user')
            ->get();

        foreach ($employees as $employee) {
            $user = $employee->user;
            $password = $user->pending_password;

            try {
                Mail::to($user->email)->send(new CredencialesEmpleadoMail($user, $employee, $password));
                $user->update(['pending_password' => null]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }
}
