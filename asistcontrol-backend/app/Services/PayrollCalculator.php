<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;

class PayrollCalculator
{
    /**
     * Calcula (o recalcula) los conceptos de nómina de un periodo para todos
     * los empleados activos de la empresa.
     */
    public function calculate(PayrollPeriod $period): PayrollPeriod
    {
        $company = $period->company;
        $settings = $company->setting;
        $concepts = $company->payrollConcepts()->where('is_active', true)->get();

        $employees = $company->employees()
            ->where('is_active', true)
            ->with(['compensation', 'concepts.concept'])
            ->orderBy('first_name')
            ->get();

        $periodDays = max(1, $period->start_date->diffInDays($period->end_date) + 1);

        DB::transaction(function () use ($period, $employees, $settings, $concepts, $periodDays) {
            $period->items()->delete();

            foreach ($employees as $employee) {
                $item = $this->calculateEmployee($period, $employee, $settings, $concepts, $periodDays);
                $period->items()->create($item);
            }

            $period->update([
                'status' => 'calculated',
                'calculated_at' => now(),
            ]);
        });

        return $period->fresh('items');
    }

    private function calculateEmployee(
        PayrollPeriod $period,
        Employee $employee,
        $settings,
        $concepts,
        int $periodDays
    ): array {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
            ])
            ->get();

        $workedDays = $attendances->whereIn('status', ['present', 'late', 'justified'])->count();
        $workedMinutes = (int) $attendances->sum('worked_minutes');
        $overtimeMinutes = (int) $attendances->sum('overtime_minutes');
        $lateCount = $attendances->where('status', 'late')->count();
        $absenceCount = $attendances->where('status', 'absent')->count();

        $comp = $employee->compensation;
        $salaryType = $comp->salary_type ?? 'fixed';
        $baseSalary = (float) ($comp->base_salary ?? 0);
        $dailyHours = (float) ($comp->daily_hours ?? 8);
        $overtimeFactor = (float) ($comp->overtime_factor ?? 1.5);
        $cap = $comp->overtime_cap_minutes;

        $expectedMinutes = (int) round($workedDays * $dailyHours * 60);

        if ($salaryType === 'hourly') {
            $baseAmount = $baseSalary * ($workedMinutes / 60);
            $hourlyRate = $baseSalary;
        } else {
            $baseAmount = $baseSalary * ($workedDays / $periodDays);
            $hourlyRate = $dailyHours > 0 ? $baseSalary / ($dailyHours * 30) : 0;
        }

        $effectiveOvertime = $cap !== null ? min($overtimeMinutes, (int) $cap) : $overtimeMinutes;
        $overtimeAmount = ($effectiveOvertime / 60) * $hourlyRate * $overtimeFactor;

        // Conceptos: recurrentes de empresa + overrides/asignaciones por empleado.
        $overrides = $employee->concepts
            ->where('is_active', true)
            ->keyBy('payroll_concept_id');

        $bonuses = 0.0;
        $deductions = 0.0;
        $detail = [];

        foreach ($concepts as $concept) {
            $assignment = $overrides->get($concept->id);

            // Un concepto no recurrente solo aplica si está asignado al empleado.
            if (! $concept->is_recurring && ! $assignment) {
                continue;
            }

            $amount = (float) ($assignment->amount_override ?? $concept->amount);

            $computed = match ($concept->calculation) {
                'percentage' => $baseAmount * ($amount / 100),
                'per_hour' => $amount * ($workedMinutes / 60),
                'per_day' => $amount * $workedDays,
                default => $amount,
            };

            if ($concept->type === 'deduction') {
                $deductions += $computed;
            } else {
                $bonuses += $computed;
            }

            $detail[] = [
                'concept' => $concept->name,
                'type' => $concept->type,
                'amount' => round($computed, 2),
            ];
        }

        // Reglas globales de la empresa.
        if ($settings) {
            if ($settings->attendance_bonus_enabled && $absenceCount === 0) {
                $bonuses += (float) $settings->attendance_bonus_amount;
                $detail[] = ['concept' => 'Bono de asistencia', 'type' => 'bonus', 'amount' => (float) $settings->attendance_bonus_amount];
            }

            if ($settings->late_penalty_amount > 0 && $lateCount > 0) {
                $penalty = (float) $settings->late_penalty_amount * $lateCount;
                $deductions += $penalty;
                $detail[] = ['concept' => 'Penalización por retardos', 'type' => 'deduction', 'amount' => round($penalty, 2)];
            }

            if ($settings->absence_penalty_amount > 0 && $absenceCount > 0) {
                $penalty = (float) $settings->absence_penalty_amount * $absenceCount;
                $deductions += $penalty;
                $detail[] = ['concept' => 'Penalización por faltas', 'type' => 'deduction', 'amount' => round($penalty, 2)];
            }
        }

        // Préstamos y anticipos activos.
        foreach ($employee->loans()->where('status', 'active')->get() as $loan) {
            $deductions += (float) $loan->installment_amount;
            $detail[] = ['concept' => 'Préstamo: ' . $loan->concept, 'type' => 'deduction', 'amount' => (float) $loan->installment_amount];
        }

        $gross = $baseAmount + $overtimeAmount + $bonuses;
        $net = $gross - $deductions;

        return [
            'employee_id' => $employee->id,
            'worked_days' => $workedDays,
            'worked_minutes' => $workedMinutes,
            'expected_minutes' => $expectedMinutes,
            'overtime_minutes' => $effectiveOvertime,
            'late_count' => $lateCount,
            'absence_count' => $absenceCount,
            'base_amount' => round($baseAmount, 2),
            'overtime_amount' => round($overtimeAmount, 2),
            'bonuses_amount' => round($bonuses, 2),
            'deductions_amount' => round($deductions, 2),
            'gross_amount' => round($gross, 2),
            'net_amount' => round($net, 2),
            'breakdown' => $detail,
        ];
    }
}
