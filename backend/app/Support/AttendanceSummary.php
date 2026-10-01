<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\Holiday;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Indicadores de asistencia por empleado en un periodo. Es la base de los
 * reportes, de la pre-nómina y de las reglas de bonos.
 *
 * Las checadas se guardan en UTC; "hoy" y las horas se calculan en la zona
 * horaria de la oficina de cada empleado (9:00 en CDMX y 9:00 en Cancún son
 * instantes distintos, pero ambos son "las 9 de la mañana" de su oficina).
 */
class AttendanceSummary
{
    public const METRICS = [
        'scheduled_days' => 'Días laborables',
        'worked_days' => 'Días trabajados',
        'on_time' => 'Entradas a tiempo',
        'lates' => 'Retardos',
        'unjustified_lates' => 'Retardos sin justificar',
        'absences' => 'Faltas',
        'unjustified_absences' => 'Faltas sin justificar',
        'early_leaves' => 'Salidas anticipadas',
        'minutes_late' => 'Minutos de retardo',
        'excused_days' => 'Días de vacaciones o permiso',
        'holidays' => 'Días festivos',
        'holidays_worked' => 'Festivos trabajados',
        'overtime_minutes' => 'Minutos de tiempo extra',
    ];

    /**
     * @param  Collection<int, Employee>  $employees  con la relación shift cargada
     * @return array<int, array<string, int>> employee_id => métricas
     */
    public function forEmployees(Collection $employees, Carbon $from, Carbon $to): array
    {
        $to = $to->copy()->min(now()->addDay()->endOfDay());
        $ids = $employees->pluck('id');
        // Puede llegar una colección simple (p. ej. el resumen de un solo empleado)
        EloquentCollection::make($employees->all())->loadMissing('office');
        $holidays = Holiday::between($from, $to);

        $checkOuts = AttendanceRecord::query()
            ->whereIn('employee_id', $ids)
            ->where('type', AttendanceType::CheckOut)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->selectRaw('employee_id, coalesce(sum(overtime_minutes), 0) as overtime')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $checkIns = AttendanceRecord::query()
            ->whereIn('employee_id', $ids)
            ->where('type', AttendanceType::CheckIn)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->get()
            ->groupBy('employee_id');

        $earlyLeaves = AttendanceRecord::query()
            ->whereIn('employee_id', $ids)
            ->where('status', AttendanceStatus::EarlyLeave)
            ->where('is_justified', false)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->selectRaw('employee_id, count(*) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $excused = EmployeeRequest::query()
            ->whereIn('employee_id', $ids)
            ->where('status', RequestStatus::Approved)
            ->whereIn('type', [RequestType::Vacation, RequestType::Leave, RequestType::Justification])
            ->whereDate('starts_on', '<=', $to)
            ->where(fn ($query) => $query->whereNull('ends_on')->whereDate('starts_on', '>=', $from)->orWhereDate('ends_on', '>=', $from))
            ->get()
            ->groupBy('employee_id');

        $result = [];

        foreach ($employees as $employee) {
            $result[$employee->id] = $this->summarize(
                $employee,
                $from,
                $to,
                $checkIns[$employee->id] ?? collect(),
                (int) ($earlyLeaves[$employee->id] ?? 0),
                $excused[$employee->id] ?? collect(),
                $holidays,
            );

            $result[$employee->id]['overtime_minutes'] = (int) ($checkOuts[$employee->id]->overtime ?? 0);
        }

        return $result;
    }

    /**
     * Estado de cada día de un empleado, para gráficas y el reporte individual.
     *
     * status: on_time | late | absent | excused | holiday | rest | pending | not_employed
     *
     * @return list<array{date: string, status: string, justified: bool, excused_type: ?string, check_in: ?string, check_out: ?string, minutes_late: int, overtime_minutes: int, holiday: ?string}>
     */
    public function dailyStatuses(Employee $employee, Carbon $from, Carbon $to): array
    {
        $employee->loadMissing('shift', 'office');
        $timezone = $employee->office?->timezone ?? config('app.timezone');
        $today = now($timezone)->toDateString();
        $holidays = Holiday::between($from, $to);

        $records = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->get()
            ->groupBy(fn (AttendanceRecord $record) => $record->work_date->toDateString());

        $excusedDays = $this->excusedDays(EmployeeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', RequestStatus::Approved)
            ->whereIn('type', [RequestType::Vacation, RequestType::Leave, RequestType::Justification])
            ->whereDate('starts_on', '<=', $to)
            ->where(fn ($query) => $query->whereNull('ends_on')->whereDate('starts_on', '>=', $from)->orWhereDate('ends_on', '>=', $from))
            ->get());

        $days = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $date = $day->toDateString();
            $dayRecords = $records[$date] ?? collect();
            $checkIn = $dayRecords->first(fn (AttendanceRecord $r) => $r->type === AttendanceType::CheckIn);
            $checkOut = $dayRecords->first(fn (AttendanceRecord $r) => $r->type === AttendanceType::CheckOut);

            $status = match (true) {
                ($employee->hired_on && $day->lt($employee->hired_on)) || ($employee->terminated_on && $day->gt($employee->terminated_on)) => 'not_employed',
                $checkIn !== null => match ($checkIn->status) {
                    AttendanceStatus::OnTime => 'on_time',
                    AttendanceStatus::Late => 'late',
                    default => 'absent',
                },
                isset($holidays[$date]) => 'holiday',
                // Sin turno (recién importado) no hay días laborables
                ! ($employee->shift?->isActiveOn($day->dayOfWeekIso) ?? false) => 'rest',
                isset($excusedDays[$date]) => 'excused',
                $date >= $today => 'pending',
                default => 'absent',
            };

            $days[] = [
                'date' => $date,
                'status' => $status,
                'justified' => (bool) $checkIn?->is_justified,
                'excused_type' => $excusedDays[$date] ?? null,
                'check_in' => $checkIn?->recorded_at->setTimezone($timezone)->format('H:i'),
                'check_out' => $checkOut?->recorded_at->setTimezone($timezone)->format('H:i'),
                'minutes_late' => (int) ($checkIn?->minutes_late ?? 0),
                'overtime_minutes' => (int) ($checkOut?->overtime_minutes ?? 0),
                'holiday' => $holidays[$date] ?? null,
            ];
        }

        return $days;
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $checkIns
     * @param  Collection<int, EmployeeRequest>  $excusedRequests
     * @return array<string, int>
     */
    private function summarize(Employee $employee, Carbon $from, Carbon $to, Collection $checkIns, int $earlyLeaves, Collection $excusedRequests, array $holidays = []): array
    {
        // "Hoy" de la oficina del empleado, no del servidor (UTC)
        $today = now($employee->office?->timezone ?? config('app.timezone'))->toDateString();

        $start = $employee->hired_on && $employee->hired_on->gt($from) ? $employee->hired_on : $from;
        $end = $employee->terminated_on && $employee->terminated_on->lt($to) ? $employee->terminated_on : $to;

        $checkInDays = $checkIns->keyBy(fn (AttendanceRecord $record) => $record->work_date->toDateString());
        $excusedDays = $this->excusedDays($excusedRequests);

        $metrics = array_fill_keys(array_keys(self::METRICS), 0);

        if ($start->lte($end)) {
            foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $day) {
                if (! ($employee->shift?->isActiveOn($day->dayOfWeekIso) ?? false)) {
                    continue;
                }

                $date = $day->toDateString();
                $record = $checkInDays[$date] ?? null;

                // Aún no llega ese día en su oficina
                if ($date > $today) {
                    continue;
                }

                // Festivo: descanso obligatorio; si trabajó, se paga doble adicional
                if (isset($holidays[$date])) {
                    if ($record === null) {
                        $metrics['holidays']++;

                        continue;
                    }
                    $metrics['holidays_worked']++;
                }

                $metrics['scheduled_days']++;

                if ($record === null) {
                    // Hoy todavía puede checar: no se cuenta como falta
                    if ($date === $today) {
                        $metrics['scheduled_days']--;
                    } elseif (isset($excusedDays[$date])) {
                        $metrics['excused_days']++;
                    } else {
                        $metrics['absences']++;
                        $metrics['unjustified_absences']++;
                    }

                    continue;
                }

                $metrics['worked_days']++;

                match ($record->status) {
                    AttendanceStatus::OnTime => $metrics['on_time']++,
                    AttendanceStatus::Late => $this->countLate($metrics, $record),
                    AttendanceStatus::Absent => $this->countAbsentArrival($metrics, $record),
                    default => null,
                };
            }
        }

        $metrics['early_leaves'] = $earlyLeaves;

        return $metrics;
    }

    private function countLate(array &$metrics, AttendanceRecord $record): void
    {
        $metrics['lates']++;
        $metrics['minutes_late'] += $record->minutes_late;

        if (! $record->is_justified) {
            $metrics['unjustified_lates']++;
        }
    }

    /**
     * Llegó después del límite: estuvo presente, pero cuenta como falta.
     */
    private function countAbsentArrival(array &$metrics, AttendanceRecord $record): void
    {
        $metrics['absences']++;
        $metrics['minutes_late'] += $record->minutes_late;

        if (! $record->is_justified) {
            $metrics['unjustified_absences']++;
        }
    }

    /**
     * Día => tipo de solicitud aprobada que lo cubre (vacation | leave | justification).
     *
     * @param  Collection<int, EmployeeRequest>  $requests
     * @return array<string, string>
     */
    private function excusedDays(Collection $requests): array
    {
        $days = [];

        foreach ($requests as $request) {
            foreach (CarbonPeriod::create($request->starts_on, $request->ends_on ?? $request->starts_on) as $day) {
                $days[$day->toDateString()] = $request->type->value;
            }
        }

        return $days;
    }
}
