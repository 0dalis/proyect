<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\PayrollPeriod;
use App\Support\ActivityLogger;
use App\Support\PanelNotifier;
use App\Support\TenantRule;
use App\Support\VacationBalance;
use App\Support\Visibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = EmployeeRequest::query()
            ->whereIn('employee_id', Visibility::employees($user)->select('id'))
            ->with('employee:id,first_name,last_name,area_id', 'attendanceRecord')
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('type')->toString(), fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate($request->integer('per_page', 25));

        $requests->getCollection()->each(fn (EmployeeRequest $item) => $item->setAttribute('can_review', $this->canReview($request, $item)));

        return response()->json($requests);
    }

    public function store(Request $request): JsonResponse
    {
        $employee = $request->user()->employee();

        if (! $employee) {
            throw ValidationException::withMessages(['request' => 'Tu usuario no está ligado a un empleado.']);
        }

        $data = $request->validate([
            'type' => ['required', Rule::enum(RequestType::class)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on', 'required_if:type,vacation'],
            'expected_time' => ['nullable', 'date_format:H:i', 'required_if:type,late_arrival', 'required_if:type,early_departure'],
            'reason' => ['required', 'string', 'max:1000'],
            'attendance_record_id' => ['nullable', TenantRule::exists('attendance_records')->where('employee_id', $employee->id)],
        ]);

        PayrollPeriod::ensureOpen($data['starts_on'], $data['ends_on'] ?? null, 'starts_on');

        if ($data['type'] === RequestType::Vacation->value) {
            $this->ensureVacationDays($employee, Carbon::parse($data['starts_on']), Carbon::parse($data['ends_on']));
        }

        $employeeRequest = EmployeeRequest::query()->create([...$data, 'employee_id' => $employee->id]);
        $employeeRequest->forceFill(['status' => RequestStatus::Pending])->save();
        PanelNotifier::requestSubmitted($employeeRequest);

        return response()->json($employeeRequest, 201);
    }

    public function review(Request $request, EmployeeRequest $employeeRequest): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($this->canReview($request, $employeeRequest), 403, $employeeRequest->type === RequestType::Vacation
            ? 'Solo el dueño o un administrador pueden autorizar vacaciones.'
            : 'No puedes autorizar esta solicitud.');

        if ($employeeRequest->status !== RequestStatus::Pending) {
            throw ValidationException::withMessages(['decision' => 'Esta solicitud ya fue revisada.']);
        }

        if ($data['decision'] === 'approved') {
            PayrollPeriod::ensureOpen($employeeRequest->starts_on, $employeeRequest->ends_on, 'decision');
        }

        $employeeRequest->forceFill([
            'status' => RequestStatus::from($data['decision']),
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['notes'] ?? null,
        ])->save();

        if ($employeeRequest->status === RequestStatus::Approved) {
            $this->applyApproval($employeeRequest);
        }

        $employeeRequest->loadMissing('employee');
        PanelNotifier::requestReviewed($employeeRequest, $request->user());
        ActivityLogger::log(
            $data['decision'],
            $employeeRequest,
            sprintf(
                '%s %s de %s',
                $data['decision'] === 'approved' ? 'Aprobó' : 'Rechazó',
                mb_strtolower($employeeRequest->type->label()),
                $employeeRequest->employee?->fullName(),
            ).($data['notes'] ?? null ? ": {$data['notes']}" : ''),
        );

        return response()->json($employeeRequest);
    }

    private function canReview(Request $request, EmployeeRequest $employeeRequest): bool
    {
        $user = $request->user();

        if ($employeeRequest->employee_id === $user->employee_id) {
            return false;
        }

        $permission = $employeeRequest->type === RequestType::Vacation ? Permission::VacationsApprove : Permission::RequestsApprove;

        return $user->can($permission->value) && Visibility::canSeeEmployee($user, $employeeRequest->employee_id);
    }

    /**
     * Una justificación aprobada hace que el retardo/falta no cuente para bonos.
     */
    private function applyApproval(EmployeeRequest $employeeRequest): void
    {
        $query = AttendanceRecord::query()->where('employee_id', $employeeRequest->employee_id);

        if ($employeeRequest->attendance_record_id) {
            $query->whereKey($employeeRequest->attendance_record_id)->update(['is_justified' => true]);

            return;
        }

        if (in_array($employeeRequest->type, [RequestType::Justification, RequestType::LateArrival, RequestType::EarlyDeparture], true)) {
            $query->whereDate('work_date', '>=', $employeeRequest->starts_on)
                ->whereDate('work_date', '<=', $employeeRequest->ends_on ?? $employeeRequest->starts_on)
                ->update(['is_justified' => true]);
        }
    }

    /**
     * Vacaciones: los días laborables pedidos no pueden pasar de los disponibles
     * del año de servicio (los pendientes de aprobar ya están apartados).
     */
    private function ensureVacationDays(Employee $employee, Carbon $from, Carbon $to): void
    {
        $balance = VacationBalance::for($employee, $from);
        $requested = VacationBalance::workingDays($employee, $from, $to);

        if ($requested === 0) {
            throw ValidationException::withMessages(['ends_on' => 'En esas fechas no tienes días laborables (descansos o festivos).']);
        }

        if ($balance['entitled'] === 0) {
            $since = $balance['next_entitlement_on'] ? ' Tendrás vacaciones a partir del '.Carbon::parse($balance['next_entitlement_on'])->format('d/m/Y').'.' : '';

            throw ValidationException::withMessages(['ends_on' => 'Aún no cumples un año de antigüedad.'.$since]);
        }

        if ($requested > $balance['available']) {
            throw ValidationException::withMessages([
                'ends_on' => "Pediste {$requested} días laborables y te quedan {$balance['available']} de {$balance['entitled']} en este año de servicio.",
            ]);
        }
    }
}
