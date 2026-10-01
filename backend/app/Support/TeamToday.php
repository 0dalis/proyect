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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cómo va el día de cada empleado activo, en la hora local de su oficina:
 * llegó a tiempo, con retardo, sigue sin registro, tiene vacaciones o
 * permiso, aún no es su hora, descansa, es festivo o no tiene turno.
 */
class TeamToday
{
    /** Estado => etiqueta, en el orden de la gráfica. */
    public const STATES = [
        'on_time' => 'A tiempo',
        'late' => 'Con retardo',
        'missing' => 'Sin registro',
        'vacation' => 'Vacaciones',
        'leave' => 'Permiso',
        'upcoming' => 'Aún no es su hora',
        'rest' => 'Descanso',
        'holiday' => 'Festivo',
        'no_shift' => 'Sin turno',
    ];

    /** No les tocaba trabajar hoy: no cuentan en "X de Y registraron asistencia". */
    private const NOT_SCHEDULED = ['rest', 'holiday', 'no_shift'];

    /**
     * @param  Collection<int, Employee>  $employees  activos, con shift, office y area
     * @return array<string, mixed>
     */
    public function build(Collection $employees, string $companyTimezone): array
    {
        $rows = $this->rows($employees, $companyTimezone);
        $counts = $this->count($rows);
        $scheduled = $rows->reject(fn (array $row) => in_array($row['state'], self::NOT_SCHEDULED, true))->count();

        return [
            'total' => $rows->count(),
            'scheduled' => $scheduled,
            'registered' => $counts['on_time'] + $counts['late'],
            'in_office_now' => $rows->where('in_office', true)->count(),
            'counts' => $counts,
            'labels' => self::STATES,
            'by_office' => $this->groupBy($rows, 'office_id', 'office'),
            'by_shift' => $this->groupBy($rows, 'shift_id', 'shift'),
            // Para actuar rápido: quién no ha llegado y quién llegó tarde
            'missing' => $rows->where('state', 'missing')->sortBy('starts_at')->take(12)->map($this->person(...))->values(),
            'late' => $rows->where('state', 'late')->sortByDesc('minutes_late')->take(12)->map($this->person(...))->values(),
            'away' => $rows->whereIn('state', ['vacation', 'leave'])->take(12)->map($this->person(...))->values(),
        ];
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(Collection $employees, string $companyTimezone): Collection
    {
        if ($employees->isEmpty()) {
            return collect();
        }

        $ids = $employees->pluck('id');
        $localDates = $employees->mapWithKeys(fn (Employee $employee) => [
            $employee->id => now($employee->office?->timezone ?? $companyTimezone),
        ]);
        $from = Carbon::parse($localDates->min(fn (Carbon $date) => $date->toDateString()));
        $to = Carbon::parse($localDates->max(fn (Carbon $date) => $date->toDateString()));

        $holidays = Holiday::between($from, $to);
        $records = AttendanceRecord::query()
            ->whereIn('employee_id', $ids)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->get()
            ->groupBy('employee_id');
        $away = EmployeeRequest::query()
            ->whereIn('employee_id', $ids)
            ->where('status', RequestStatus::Approved)
            ->whereIn('type', [RequestType::Vacation, RequestType::Leave, RequestType::Justification])
            ->whereDate('starts_on', '<=', $to)
            ->where(fn ($query) => $query->whereDate('ends_on', '>=', $from)->orWhere(fn ($q) => $q->whereNull('ends_on')->whereDate('starts_on', '>=', $from)))
            ->get()
            ->groupBy('employee_id');

        return $employees->map(function (Employee $employee) use ($localDates, $holidays, $records, $away) {
            $now = $localDates[$employee->id];
            $date = $now->toDateString();
            $dayRecords = ($records[$employee->id] ?? collect())->filter(fn (AttendanceRecord $r) => $r->work_date->toDateString() === $date);
            $checkIn = $dayRecords->first(fn (AttendanceRecord $r) => $r->type === AttendanceType::CheckIn);
            $checkOut = $dayRecords->first(fn (AttendanceRecord $r) => $r->type === AttendanceType::CheckOut);
            $request = ($away[$employee->id] ?? collect())->first(fn (EmployeeRequest $r) => $r->starts_on->toDateString() <= $date
                && ($r->ends_on ?? $r->starts_on)->toDateString() >= $date);
            $shift = $employee->shift;
            $schedule = $shift?->scheduleFor($now->dayOfWeekIso);
            $startsAt = $schedule ? Carbon::parse("{$date} {$schedule['starts_at']}", $now->getTimezone()) : null;

            $state = match (true) {
                $checkIn !== null => match ($checkIn->status) {
                    AttendanceStatus::OnTime => 'on_time',
                    AttendanceStatus::Late => 'late',
                    default => 'missing',
                },
                isset($holidays[$date]) => 'holiday',
                $shift === null => 'no_shift',
                ! $shift->isActiveOn($now->dayOfWeekIso) => 'rest',
                $request?->type === RequestType::Vacation => 'vacation',
                $request !== null => 'leave',
                $now->lt($startsAt->copy()->addMinutes($shift->tolerance_minutes ?? 0)) => 'upcoming',
                default => 'missing',
            };

            return [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'office_id' => $employee->office_id,
                'office' => $employee->office?->name ?? 'Sin oficina',
                'shift_id' => $employee->shift_id,
                'shift' => $shift?->name ?? 'Sin turno',
                'area' => $employee->area?->name,
                'state' => $state,
                'starts_at' => $schedule['starts_at'] ?? null,
                'check_in' => $checkIn?->recorded_at->setTimezone($now->getTimezone())->format('H:i'),
                'minutes_late' => (int) ($checkIn?->minutes_late ?? 0),
                'in_office' => $checkIn !== null && $checkOut === null && $checkIn->status !== AttendanceStatus::Absent,
                'until' => $request ? ($request->ends_on ?? $request->starts_on)->toDateString() : null,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function count(Collection $rows): array
    {
        $counts = $rows->countBy('state');

        return collect(self::STATES)->map(fn ($label, string $state) => (int) ($counts[$state] ?? 0))->all();
    }

    /**
     * Por oficina o turno. Solo si hay más de uno (si no, repite el total).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function groupBy(Collection $rows, string $key, string $label): array
    {
        $groups = $rows->groupBy(fn (array $row) => $row[$key] ?? 0);

        if ($groups->count() < 2) {
            return [];
        }

        return $groups->map(function (Collection $group) use ($label) {
            $counts = $this->count($group);
            $scheduled = $group->reject(fn (array $row) => in_array($row['state'], self::NOT_SCHEDULED, true))->count();

            return [
                'name' => $group->first()[$label],
                'total' => $group->count(),
                'scheduled' => $scheduled,
                'registered' => $counts['on_time'] + $counts['late'],
                'counts' => $counts,
            ];
        })->sortByDesc('total')->values()->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function person(array $row): array
    {
        return collect($row)->only(['id', 'name', 'office', 'shift', 'area', 'state', 'starts_at', 'check_in', 'minutes_late', 'until'])->all();
    }
}
