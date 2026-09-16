<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    private function baseQuery(Request $request, Company $company)
    {
        $query = Attendance::query()
            ->where('attendances.company_id', $company->id)
            ->whereBetween('attendances.date', [$request->from, $request->to]);

        if ($request->filled('office_id')) {
            $query->where('attendances.office_id', $request->office_id);
        }
        if ($request->filled('employee_id')) {
            $query->where('attendances.employee_id', $request->employee_id);
        }
        if ($request->filled('shift_id')) {
            $query->where('attendances.shift_id', $request->shift_id);
        }
        if ($request->filled('area_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('area_id', $request->area_id));
        }

        return $query;
    }

    private function aggregateColumns(): array
    {
        return [
            "count(*) as records",
            "sum(case when attendances.status = 'present' then 1 else 0 end) as present",
            "sum(case when attendances.status = 'late' then 1 else 0 end) as late",
            "sum(case when attendances.status = 'absent' then 1 else 0 end) as absent",
            "sum(case when attendances.status = 'justified' then 1 else 0 end) as justified",
            "coalesce(sum(attendances.worked_minutes), 0) as worked_minutes",
            "coalesce(sum(attendances.overtime_minutes), 0) as overtime_minutes",
            "coalesce(sum(attendances.late_minutes), 0) as late_minutes",
            "coalesce(sum(attendances.early_minutes), 0) as early_minutes",
            "count(distinct attendances.employee_id) as employees",
        ];
    }

    public function attendance(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'group_by' => 'nullable|in:company,area,office,employee',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $groupBy = $request->input('group_by', 'company');
        $base = $this->baseQuery($request, $company);
        $columns = $this->aggregateColumns();

        $totals = (clone $base)->selectRaw(implode(', ', $columns))->first();

        $groups = $this->groupedRows($base, $groupBy, $columns);

        $series = (clone $base)
            ->selectRaw('attendances.date')
            ->selectRaw("sum(case when attendances.status = 'present' then 1 else 0 end) as present")
            ->selectRaw("sum(case when attendances.status = 'late' then 1 else 0 end) as late")
            ->selectRaw("sum(case when attendances.status = 'absent' then 1 else 0 end) as absent")
            ->groupBy('attendances.date')
            ->orderBy('attendances.date')
            ->get();

        return response()->json([
            'group_by' => $groupBy,
            'totals' => $this->withRates($totals),
            'groups' => $groups->map(fn ($row) => $this->withRates($row))->values(),
            'series_by_day' => $series,
        ]);
    }

    private function groupedRows($base, string $groupBy, array $columns)
    {
        $aggregate = implode(', ', $columns);

        if ($groupBy === 'area') {
            return (clone $base)
                ->leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->leftJoin('areas', 'areas.id', '=', 'employees.area_id')
                ->selectRaw("coalesce(areas.name, 'Sin área') as label")
                ->selectRaw($aggregate)
                ->groupBy('areas.name')
                ->orderBy('label')
                ->get();
        }

        if ($groupBy === 'office') {
            return (clone $base)
                ->leftJoin('offices', 'offices.id', '=', 'attendances.office_id')
                ->selectRaw("coalesce(offices.name, 'Sin oficina') as label")
                ->selectRaw($aggregate)
                ->groupBy('offices.name')
                ->orderBy('label')
                ->get();
        }

        if ($groupBy === 'employee') {
            return (clone $base)
                ->leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->selectRaw("coalesce(concat(employees.first_name, ' ', employees.last_name), 'Sin empleado') as label")
                ->selectRaw('attendances.employee_id')
                ->selectRaw($aggregate)
                ->groupBy('attendances.employee_id', 'employees.first_name', 'employees.last_name')
                ->orderBy('label')
                ->get();
        }

        return collect([
            (clone $base)->selectRaw("'Toda la empresa' as label")->selectRaw($aggregate)->first(),
        ]);
    }

    private function withRates($row)
    {
        if (! $row) {
            return $row;
        }
        $row = (array) $row;
        $present = (int) ($row['present'] ?? 0);
        $late = (int) ($row['late'] ?? 0);
        $total = (int) ($row['records'] ?? 0);

        $row['punctuality_rate'] = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        $row['absence_rate'] = $total > 0 ? round((((int) ($row['absent'] ?? 0)) / $total) * 100, 1) : 0;

        return $row;
    }

    public function employee(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $records = Attendance::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$request->from, $request->to])
            ->with('records')
            ->orderBy('date')
            ->get();

        $totals = [
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'justified' => $records->where('status', 'justified')->count(),
            'worked_minutes' => (int) $records->sum('worked_minutes'),
            'overtime_minutes' => (int) $records->sum('overtime_minutes'),
        ];

        return response()->json([
            'employee' => $employee->only(['id', 'first_name', 'last_name', 'employee_code']),
            'totals' => $totals,
            'records' => $records,
        ]);
    }

    public function payroll(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'period_id' => 'required|integer|exists:payroll_periods,id',
            'group_by' => 'nullable|in:company,area,office,employee',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $period = $company->payrollPeriods()->findOrFail($request->period_id);
        $groupBy = $request->input('group_by', 'company');

        $totals = $period->items()
            ->selectRaw('coalesce(sum(base_amount),0) as base')
            ->selectRaw('coalesce(sum(overtime_amount),0) as overtime')
            ->selectRaw('coalesce(sum(bonuses_amount),0) as bonuses')
            ->selectRaw('coalesce(sum(deductions_amount),0) as deductions')
            ->selectRaw('coalesce(sum(gross_amount),0) as gross')
            ->selectRaw('coalesce(sum(net_amount),0) as net')
            ->selectRaw('count(*) as employees')
            ->first();

        $join = DB::table('payroll_items')
            ->join('employees', 'employees.id', '=', 'payroll_items.employee_id')
            ->leftJoin('offices', 'offices.id', '=', 'employees.office_id')
            ->leftJoin('areas', 'areas.id', '=', 'employees.area_id')
            ->where('payroll_items.payroll_period_id', $period->id);

        $label = match ($groupBy) {
            'area' => "coalesce(areas.name, 'Sin área')",
            'office' => "coalesce(offices.name, 'Sin oficina')",
            'employee' => "concat(employees.first_name, ' ', employees.last_name)",
            default => "'Toda la empresa'",
        };

        $groups = $join
            ->selectRaw("$label as label")
            ->selectRaw('count(*) as employees')
            ->selectRaw('coalesce(sum(payroll_items.base_amount),0) as base')
            ->selectRaw('coalesce(sum(payroll_items.overtime_amount),0) as overtime')
            ->selectRaw('coalesce(sum(payroll_items.bonuses_amount),0) as bonuses')
            ->selectRaw('coalesce(sum(payroll_items.deductions_amount),0) as deductions')
            ->selectRaw('coalesce(sum(payroll_items.net_amount),0) as net')
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return response()->json([
            'period' => $period,
            'group_by' => $groupBy,
            'totals' => $totals,
            'groups' => $groups,
        ]);
    }
}
