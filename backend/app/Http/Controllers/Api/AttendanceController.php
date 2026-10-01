<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Device;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Support\ActivityLogger;
use App\Support\Visibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $visible = Visibility::employees($request->user())->select('id');

        $records = AttendanceRecord::query()
            ->whereIn('employee_id', $visible)
            ->with('employee:id,first_name,last_name,employee_code,area_id', 'office:id,name,latitude,longitude,geofence_radius,timezone')
            ->whereDate('work_date', '>=', $request->date('from') ?? now()->startOfMonth())
            ->whereDate('work_date', '<=', $request->date('to') ?? now())
            ->when($request->integer('employee_id'), fn ($query, $id) => $query->where('employee_id', $id))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest('recorded_at')
            ->paginate($request->integer('per_page', 50));

        return response()->json($records);
    }

    /**
     * Checada desde la app del empleado.
     *
     * biometric: el celular firma "{employee_id}|{device_identifier}|{signed_at}"
     * con una llave que solo se desbloquea con huella / Face ID / PIN del celular.
     * pin: PIN del sistema escrito en el teclado numérico de la app.
     */
    public function punch(Request $request, RegisterAttendance $registerAttendance): JsonResponse
    {
        $data = $request->validate([
            'method' => ['required', 'in:biometric,pin'],
            'device_identifier' => ['required', 'string', 'max:190'],
            'pin' => ['required_if:method,pin', 'nullable', 'digits:6'],
            'signed_at' => ['required_if:method,biometric', 'nullable', 'integer'],
            'signature' => ['required_if:method,biometric', 'nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee = $request->user()->employee();

        if (! $employee) {
            throw ValidationException::withMessages(['attendance' => 'Tu usuario no está ligado a un empleado.']);
        }

        $device = Device::query()
            ->where('employee_id', $employee->id)
            ->where('device_identifier', $data['device_identifier'])
            ->first();

        if (! $device || ! $device->isUsable()) {
            throw ValidationException::withMessages([
                'attendance' => $device ? 'Este celular está pendiente de autorización.' : 'Registra este celular antes de checar.',
            ]);
        }

        if ($data['method'] === 'biometric') {
            $signedAt = Carbon::createFromTimestamp($data['signed_at']);
            $payload = "{$employee->id}|{$device->device_identifier}|{$data['signed_at']}";

            if (abs(now()->diffInSeconds($signedAt)) > 120 || ! $device->verifySignature($payload, $data['signature'])) {
                throw ValidationException::withMessages(['attendance' => 'No se pudo verificar la identidad del dispositivo.']);
            }
        } elseif (! $employee->checkPin($data['pin'])) {
            throw ValidationException::withMessages(['pin' => 'PIN incorrecto.']);
        }

        $record = $registerAttendance->handle(
            $employee,
            $data['method'] === 'biometric' ? AttendanceChannel::AppBiometric : AttendanceChannel::AppPin,
            [
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'device_id' => $device->id,
            ],
        );

        $device->forceFill(['last_used_at' => now()])->save();

        return response()->json($this->present($record), $record->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Registro manual (olvidó checar, falla del kiosko). Aplica las mismas reglas de turno.
     */
    public function storeManual(Request $request, RegisterAttendance $registerAttendance): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'recorded_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        abort_unless(Visibility::canSeeEmployee($request->user(), $data['employee_id']), 404);

        $employee = Employee::query()->with('office')->findOrFail($data['employee_id']);
        // La hora que escribe RH es la de la oficina del empleado
        $at = Carbon::parse($data['recorded_at'], $employee->office->timezone);
        PayrollPeriod::ensureOpen($at->toDateString(), field: 'recorded_at');

        $record = $registerAttendance->handle($employee, AttendanceChannel::Manual, [], $at);

        if ($record->wasRecentlyCreated) {
            ActivityLogger::log('manual_punch', $record, sprintf(
                'Registró manualmente %s de %s a las %s',
                mb_strtolower($record->type->label()),
                $employee->fullName(),
                $at->format('d/m/Y H:i'),
            ));
        }

        return response()->json(self::present($record), $record->wasRecentlyCreated ? 201 : 200);
    }

    public function justify(Request $request, AttendanceRecord $record): JsonResponse
    {
        abort_unless(Visibility::canSeeEmployee($request->user(), $record->employee_id), 404);
        PayrollPeriod::ensureOpen($record->work_date, field: 'is_justified');

        $record->update($request->validate(['is_justified' => ['required', 'boolean']]));
        $record->loadMissing('employee');

        ActivityLogger::log(
            $record->is_justified ? 'justified' : 'unjustified',
            $record,
            ($record->is_justified ? 'Justificó ' : 'Quitó la justificación de ')
                .mb_strtolower($record->status->label()).' de '.$record->employee?->fullName().' del '.$record->work_date->format('d/m/Y'),
        );

        return response()->json(self::present($record));
    }

    /**
     * recorded_at va en UTC (instante exacto); local_time y timezone dicen qué
     * hora era en la oficina (9:00 en Cancún no es el mismo instante que 9:00 en CDMX).
     */
    public static function present(AttendanceRecord $record): array
    {
        $timezone = $record->loadMissing('office:id,timezone')->office?->timezone ?? config('app.timezone');

        return [
            'id' => $record->id,
            'type' => $record->type->value,
            'type_label' => $record->type->label(),
            'status' => $record->status->value,
            'status_label' => $record->status->label(),
            'minutes_late' => $record->minutes_late,
            'minutes_early' => $record->minutes_early,
            'recorded_at' => $record->recorded_at,
            'local_time' => $record->recorded_at->copy()->setTimezone($timezone)->format('H:i'),
            'timezone' => $timezone,
            'overtime_minutes' => $record->overtime_minutes,
            'work_date' => $record->work_date->toDateString(),
            'is_justified' => $record->is_justified,
            'duplicate' => ! $record->wasRecentlyCreated,
        ];
    }
}
