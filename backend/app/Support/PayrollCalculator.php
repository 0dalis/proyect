<?php

namespace App\Support;

use App\Enums\AttendanceType;
use App\Models\AttendanceRecord;
use App\Models\BonusRule;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pre-nómina estimada: sueldo diario por los días del periodo, descuento por
 * faltas sin justificar, tiempo extra, festivos trabajados y bonos cuyas
 * reglas se cumplen. No calcula impuestos.
 *
 * El sueldo es por día (no por hora trabajada): las horas solo cuentan como
 * tiempo extra, después de la salida del horario. El valor de la hora extra es
 * el sueldo diario entre las horas de su jornada.
 *
 * - Tiempo extra (arts. 67 y 68 LFT): las primeras 9 horas de la semana se
 *   pagan al doble; las que pasen de 9, al triple.
 * - Festivo trabajado (art. 75 LFT): se paga el salario doble además del día.
 */
class PayrollCalculator
{
    public const OPERATORS = ['<', '<=', '=', '>=', '>'];

    private const DAYS_PER_SALARY_PERIOD = ['daily' => 1, 'weekly' => 7, 'biweekly' => 15, 'monthly' => 30];

    /** Horas extra por semana que se pagan al doble; las demás, al triple. */
    private const DOUBLE_OVERTIME_MINUTES_PER_WEEK = 9 * 60;

    public function __construct(private AttendanceSummary $summary) {}

    /**
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, BonusRule>  $rules  vacía si el módulo de bonos está apagado
     * @return list<array<string, mixed>>
     */
    public function calculate(Collection $employees, Carbon $from, Carbon $to, Collection $rules, bool $includeSalary = true): array
    {
        $metrics = $this->summary->forEmployees($employees, $from, $to);
        $periodDays = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $overtimeByWeek = $this->overtimeByWeek($employees->pluck('id')->all(), $from, $to);

        return $employees->map(function (Employee $employee) use ($metrics, $periodDays, $rules, $includeSalary, $overtimeByWeek) {
            $employeeMetrics = $metrics[$employee->id];
            $dailyRate = $includeSalary ? $this->dailyRate($employee) : 0.0;
            $hourlyRate = $includeSalary ? $this->hourlyRate($employee, $dailyRate) : 0.0;

            // Días del periodo por sueldo diario, menos las faltas sin justificar
            $base = round($dailyRate * $periodDays, 2);
            $deductions = round($dailyRate * $employeeMetrics['unjustified_absences'], 2);

            [$doubleMinutes, $tripleMinutes] = $this->splitOvertime($overtimeByWeek[$employee->id] ?? []);
            $overtimePay = round($hourlyRate * ($doubleMinutes / 60 * 2 + $tripleMinutes / 60 * 3), 2);
            $holidayPay = round($dailyRate * 2 * $employeeMetrics['holidays_worked'], 2);

            $bonuses = $rules
                ->filter(fn (BonusRule $rule) => $this->appliesTo($rule, $employee) && $this->meetsConditions($rule, $employeeMetrics))
                ->map(fn (BonusRule $rule) => [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'amount' => $rule->amount_type === 'percent'
                        ? round($base * (float) $rule->amount / 100, 2)
                        : (float) $rule->amount,
                ])
                ->values();

            return [
                'employee' => [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->fullName(),
                    'area' => $employee->area?->name,
                    'salary' => $includeSalary ? (float) $employee->salary : null,
                    'salary_period' => $employee->salary_period,
                ],
                'metrics' => $employeeMetrics,
                'base' => $base,
                'deductions' => $deductions,
                'overtime' => [
                    'double_hours' => round($doubleMinutes / 60, 2),
                    'triple_hours' => round($tripleMinutes / 60, 2),
                    'amount' => $overtimePay,
                ],
                'holiday_pay' => $holidayPay,
                'bonuses' => $bonuses->all(),
                'bonus_total' => round($bonuses->sum('amount'), 2),
                'total' => round($base - $deductions + $overtimePay + $holidayPay + $bonuses->sum('amount'), 2),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, int>  $metrics
     */
    public function meetsConditions(BonusRule $rule, array $metrics): bool
    {
        foreach ($rule->conditions as $condition) {
            $actual = $metrics[$condition['metric']] ?? null;
            $expected = (float) $condition['value'];

            $passes = match ($condition['operator']) {
                '<' => $actual < $expected,
                '<=' => $actual <= $expected,
                '=' => $actual == $expected,
                '>=' => $actual >= $expected,
                '>' => $actual > $expected,
                default => false,
            };

            if ($actual === null || ! $passes) {
                return false;
            }
        }

        return true;
    }

    private function appliesTo(BonusRule $rule, Employee $employee): bool
    {
        return empty($rule->employee_ids) || in_array($employee->id, array_map('intval', $rule->employee_ids), true);
    }

    private function dailyRate(Employee $employee): float
    {
        $days = self::DAYS_PER_SALARY_PERIOD[$employee->salary_period ?? ''] ?? null;

        if (! $employee->salary || ! $days) {
            return 0.0;
        }

        return (float) $employee->salary / $days;
    }

    /**
     * Valor de una hora extra: el sueldo diario entre las horas de su jornada.
     */
    private function hourlyRate(Employee $employee, float $dailyRate): float
    {
        $hours = $this->scheduledHours($employee);

        return $hours > 0 ? $dailyRate / $hours : 0.0;
    }

    private function scheduledHours(Employee $employee): float
    {
        $shift = $employee->shift;

        // Promedio diario de su semana (lunes a jueves de 9 h y viernes de 8 h cuentan distinto)
        if ($shift && count($shift->weekdays) > 0) {
            return $shift->weeklyMinutes() / count($shift->weekdays) / 60;
        }

        return ($shift?->scheduledMinutes() ?? 480) / 60;
    }

    /**
     * Minutos extra de cada empleado agrupados por semana (lunes a domingo).
     *
     * @param  list<int>  $employeeIds
     * @return array<int, array<string, int>> employee_id => [semana => minutos]
     */
    private function overtimeByWeek(array $employeeIds, Carbon $from, Carbon $to): array
    {
        $result = [];

        AttendanceRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('type', AttendanceType::CheckOut)
            ->where('overtime_minutes', '>', 0)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->get(['employee_id', 'work_date', 'overtime_minutes'])
            ->each(function (AttendanceRecord $record) use (&$result) {
                $week = $record->work_date->format('o-W');
                $result[$record->employee_id][$week] = ($result[$record->employee_id][$week] ?? 0) + $record->overtime_minutes;
            });

        return $result;
    }

    /**
     * @param  array<string, int>  $weeks
     * @return array{0: int, 1: int} minutos al doble, minutos al triple
     */
    private function splitOvertime(array $weeks): array
    {
        $double = 0;
        $triple = 0;

        foreach ($weeks as $minutes) {
            $double += min($minutes, self::DOUBLE_OVERTIME_MINUTES_PER_WEEK);
            $triple += max(0, $minutes - self::DOUBLE_OVERTIME_MINUTES_PER_WEEK);
        }

        return [$double, $triple];
    }
}
