<?php

namespace App\Support;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\Holiday;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

/**
 * Vacaciones según antigüedad (art. 76 LFT, reforma "vacaciones dignas" 2023):
 * 12 días al cumplir el primer año, +2 por año hasta 20 días en el quinto, y
 * después +2 por cada 5 años. Se cuentan días laborables del turno, sin festivos.
 *
 * El derecho corre por "año de servicio": del aniversario de ingreso al siguiente.
 */
class VacationBalance
{
    /**
     * @return array{years_of_service: int, entitled: int, used: int, pending: int, available: int,
     *   period_from: ?string, period_to: ?string, next_entitlement_on: ?string, hired_on: ?string}
     */
    public static function for(Employee $employee, ?Carbon $on = null): array
    {
        $on = ($on ?? now())->copy()->startOfDay();
        $hiredOn = ($employee->hired_on ?? $employee->created_at)?->copy()->startOfDay();

        if (! $hiredOn || $hiredOn->gt($on)) {
            return self::empty($hiredOn);
        }

        $years = (int) $hiredOn->diffInYears($on);

        // Aún no cumple un año: todavía no tiene vacaciones
        if ($years < 1) {
            return [...self::empty($hiredOn), 'next_entitlement_on' => $hiredOn->copy()->addYear()->toDateString()];
        }

        $periodFrom = $hiredOn->copy()->addYears($years);
        $periodTo = $periodFrom->copy()->addYear()->subDay();
        $entitled = self::daysFor($years);

        $requests = EmployeeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('type', RequestType::Vacation)
            ->whereIn('status', [RequestStatus::Approved, RequestStatus::Pending])
            ->whereDate('starts_on', '<=', $periodTo)
            ->whereDate('ends_on', '>=', $periodFrom)
            ->get();

        $used = 0;
        $pending = 0;

        foreach ($requests as $request) {
            $days = self::workingDays($employee, $request->starts_on->max($periodFrom), $request->ends_on->min($periodTo));
            $request->status === RequestStatus::Approved ? $used += $days : $pending += $days;
        }

        return [
            'years_of_service' => $years,
            'entitled' => $entitled,
            'used' => $used,
            'pending' => $pending,
            'available' => max(0, $entitled - $used - $pending),
            'period_from' => $periodFrom->toDateString(),
            'period_to' => $periodTo->toDateString(),
            'next_entitlement_on' => $periodTo->copy()->addDay()->toDateString(),
            'hired_on' => $hiredOn->toDateString(),
        ];
    }

    /**
     * Días de vacaciones por años cumplidos.
     */
    public static function daysFor(int $years): int
    {
        return match (true) {
            $years < 1 => 0,
            $years <= 5 => 12 + ($years - 1) * 2,
            default => 22 + intdiv($years - 6, 5) * 2,
        };
    }

    /**
     * Días del rango que el empleado trabajaría: activos en su turno y no festivos.
     */
    public static function workingDays(Employee $employee, Carbon $from, Carbon $to): int
    {
        if ($from->gt($to)) {
            return 0;
        }

        $employee->loadMissing('shift');
        $holidays = Holiday::between($from, $to);
        $days = 0;

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            if (($employee->shift?->isActiveOn($day->dayOfWeekIso) ?? false) && ! isset($holidays[$day->toDateString()])) {
                $days++;
            }
        }

        return $days;
    }

    private static function empty(?Carbon $hiredOn): array
    {
        return [
            'years_of_service' => 0,
            'entitled' => 0,
            'used' => 0,
            'pending' => 0,
            'available' => 0,
            'period_from' => null,
            'period_to' => null,
            'next_entitlement_on' => null,
            'hired_on' => $hiredOn?->toDateString(),
        ];
    }
}
