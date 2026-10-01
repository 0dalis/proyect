<?php

namespace App\Actions;

use App\Enums\AttendanceChannel;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\Shift;
use App\Support\Geo;
use App\Support\PlanSeats;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Registra entrada o salida para cualquier canal (app o kiosko).
 */
class RegisterAttendance
{
    /**
     * @param  array{latitude?: float|null, longitude?: float|null, accuracy?: float|null, device_id?: int|null, kiosk_id?: int|null, photo_path?: string|null}  $context
     */
    public function handle(Employee $employee, AttendanceChannel $channel, array $context = [], ?Carbon $at = null): AttendanceRecord
    {
        if (! $employee->isActive()) {
            $this->fail('El empleado no está activo.');
        }

        $company = app(TenantManager::class)->current();
        if ($company && PlanSeats::isLocked($company, $employee->id)) {
            $this->fail(PlanSeats::message($company));
        }

        $employee->loadMissing('office', 'shift');
        $office = $employee->office;
        $shift = $employee->shift;

        // Carga masiva: hasta que lo organicen (oficina y turno) no puede checar
        if (! $office || ! $shift) {
            $this->fail('Este empleado aún no tiene oficina y turno asignados. Pide a RH que lo organice.');
        }
        $now = ($at ?? now())->copy()->setTimezone($office->timezone);

        $duplicate = $this->recentPunch($employee, $now);
        if ($duplicate) {
            return $duplicate;
        }

        [$workDate, $shiftStart, $shiftEnd] = $this->resolveShiftOccurrence($shift, $now);

        $existing = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->pluck('type')
            ->map(fn ($type) => $type instanceof AttendanceType ? $type : AttendanceType::from($type));

        $type = match (true) {
            ! $existing->contains(AttendanceType::CheckIn) => AttendanceType::CheckIn,
            ! $existing->contains(AttendanceType::CheckOut) => AttendanceType::CheckOut,
            default => $this->fail('Ya registraste entrada y salida de este turno.'),
        };

        $location = $this->checkGeofence($employee, $channel, $context, $now);

        [$status, $minutesLate, $minutesEarly] = $type === AttendanceType::CheckIn
            ? $this->checkInStatus($shift, $shiftStart, $now)
            : $this->checkOutStatus($shiftEnd, $now);

        // El sueldo es por día: solo cuenta el tiempo extra después de la salida del turno
        $overtimeMinutes = $type === AttendanceType::CheckOut ? $this->overtime($shiftEnd, $now) : 0;

        return AttendanceRecord::query()->create([
            'employee_id' => $employee->id,
            'office_id' => $office->id,
            'shift_id' => $shift->id,
            'work_date' => $workDate,
            'type' => $type,
            'recorded_at' => $now->copy()->utc(),
            'channel' => $channel,
            'status' => $status,
            'minutes_late' => $minutesLate,
            'minutes_early' => $minutesEarly,
            'overtime_minutes' => $overtimeMinutes,
            'latitude' => $context['latitude'] ?? null,
            'longitude' => $context['longitude'] ?? null,
            'accuracy_meters' => $context['accuracy'] ?? null,
            'distance_meters' => $location['distance'],
            'geofence_skipped' => $location['skipped'],
            'photo_path' => $context['photo_path'] ?? null,
            'device_id' => $context['device_id'] ?? null,
            'kiosk_id' => $context['kiosk_id'] ?? null,
            'is_justified' => $status !== AttendanceStatus::OnTime && $this->hasApprovedNotice($employee, $type, $workDate),
        ]);
    }

    /**
     * Aviso de llegada tarde / salida anticipada aprobado antes de checar.
     */
    private function hasApprovedNotice(Employee $employee, AttendanceType $type, string $workDate): bool
    {
        return EmployeeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', RequestStatus::Approved)
            ->where('type', $type === AttendanceType::CheckIn ? RequestType::LateArrival : RequestType::EarlyDeparture)
            ->whereDate('starts_on', '<=', $workDate)
            ->where(fn ($query) => $query->whereNull('ends_on')->whereDate('starts_on', $workDate)->orWhereDate('ends_on', '>=', $workDate))
            ->exists();
    }

    private function recentPunch(Employee $employee, Carbon $now): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('recorded_at', [
                $now->copy()->utc()->subMinutes(config('tenancy.duplicate_punch_minutes')),
                $now->copy()->utc()->addMinutes(config('tenancy.duplicate_punch_minutes')),
            ])
            ->latest('recorded_at')
            ->first();
    }

    /**
     * Encuentra a qué jornada pertenece la checada. Un turno nocturno que empieza
     * ayer a las 22:00 sigue siendo "la jornada de ayer" a las 05:50 de hoy.
     *
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    private function resolveShiftOccurrence(Shift $shift, Carbon $now): array
    {
        $candidates = [];

        foreach ([$now->copy()->subDay(), $now->copy()] as $day) {
            // Cada día puede tener su propio horario (ej. viernes de 9 a 17)
            [$start, $end] = $this->dayBounds($shift, $day);

            // Ventana en la que una checada cuenta para esta jornada
            $windowStart = $start->copy()->subHours(4);
            $windowEnd = $end->copy()->addHours(6);

            if ($now->between($windowStart, $windowEnd)) {
                $candidates[] = [$day->toDateString(), $start, $end, $shift->isActiveOn($day->dayOfWeekIso)];
            }
        }

        // Preferir la jornada activa más reciente
        usort($candidates, fn ($a, $b) => [$b[3], $b[1]] <=> [$a[3], $a[1]]);

        if ($candidates === []) {
            [$start, $end] = $this->dayBounds($shift, $now);

            return [$now->toDateString(), $start, $end];
        }

        return array_slice($candidates[0], 0, 3);
    }

    /**
     * Entrada y salida de la jornada que empieza ese día, con su horario especial si lo tiene.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dayBounds(Shift $shift, Carbon $day): array
    {
        $schedule = $shift->scheduleFor($day->dayOfWeekIso);
        $start = $day->copy()->setTimeFromTimeString($schedule['starts_at']);
        $end = $day->copy()->setTimeFromTimeString($schedule['ends_at']);

        if ($shift->crossesMidnight($day->dayOfWeekIso)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * @return array{distance: float|null, skipped: bool}
     */
    private function checkGeofence(Employee $employee, AttendanceChannel $channel, array $context, Carbon $now): array
    {
        $office = $employee->office;
        $hasCoordinates = isset($context['latitude'], $context['longitude']);
        $distance = $hasCoordinates && $office->hasLocation()
            ? Geo::distanceInMeters($office->latitude, $office->longitude, $context['latitude'], $context['longitude'])
            : null;

        // El kiosko está físicamente en la oficina: no valida geocerca
        if (in_array($channel, [AttendanceChannel::KioskQr, AttendanceChannel::KioskPin, AttendanceChannel::Manual], true)) {
            return ['distance' => $distance, 'skipped' => false];
        }

        if ($employee->worksRemotelyOn($now)) {
            return ['distance' => $distance, 'skipped' => true];
        }

        if (! $office->hasLocation()) {
            $this->fail('La oficina aún no tiene ubicación configurada. Pide a un administrador que la registre.');
        }

        if (! $hasCoordinates) {
            $this->fail('Activa la ubicación de tu celular para registrar asistencia.');
        }

        if ($distance > $office->geofence_radius) {
            $this->fail(sprintf(
                'Estás a %d m de %s. Debes estar a menos de %d m para registrar.',
                round($distance), $office->name, $office->geofence_radius
            ));
        }

        return ['distance' => $distance, 'skipped' => false];
    }

    /**
     * @return array{0: AttendanceStatus, 1: int, 2: int}
     */
    private function checkInStatus(Shift $shift, Carbon $shiftStart, Carbon $now): array
    {
        $minutesLate = max(0, (int) floor($shiftStart->diffInMinutes($now, false)));

        $status = match (true) {
            $minutesLate <= $shift->tolerance_minutes => AttendanceStatus::OnTime,
            $minutesLate <= $shift->absence_after_minutes => AttendanceStatus::Late,
            default => AttendanceStatus::Absent,
        };

        return [$status, $status === AttendanceStatus::OnTime ? 0 : $minutesLate, 0];
    }

    /**
     * @return array{0: AttendanceStatus, 1: int, 2: int}
     */
    private function checkOutStatus(Carbon $shiftEnd, Carbon $now): array
    {
        $minutesEarly = max(0, (int) floor($now->diffInMinutes($shiftEnd, false)));

        return [$minutesEarly > 0 ? AttendanceStatus::EarlyLeave : AttendanceStatus::OnTime, 0, $minutesEarly];
    }

    /**
     * Tiempo extra: lo que se quedó después de la salida de su horario (la del
     * día, si tiene horario especial), en bloques completos de 30 minutos.
     */
    private function overtime(Carbon $shiftEnd, Carbon $now): int
    {
        $block = max(1, (int) config('tenancy.overtime_block_minutes'));
        $after = max(0, (int) floor($shiftEnd->diffInMinutes($now, false)));

        return intdiv($after, $block) * $block;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['attendance' => $message]);
    }
}
