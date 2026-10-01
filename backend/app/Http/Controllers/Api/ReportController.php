<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use App\Support\AttendanceSummary;
use App\Support\Csv;
use App\Support\Visibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Resumen de asistencia por empleado. ?format=csv descarga el archivo.
     */
    public function attendance(Request $request, AttendanceSummary $summary): JsonResponse|StreamedResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'area_id' => ['nullable', 'integer'],
        ]);

        $from = $request->date('from')->startOfDay();
        $to = $request->date('to')->endOfDay();

        $employees = Visibility::employees($request->user())
            ->with('shift', 'area:id,name,color')
            ->where(fn ($query) => $query->where('status', EmployeeStatus::Active)->orWhereDate('terminated_on', '>=', $from))
            ->when($request->integer('area_id'), fn ($query, $areaId) => $query->where('area_id', $areaId))
            ->orderBy('first_name')
            ->get();

        $metrics = $summary->forEmployees($employees, $from, $to);

        $rows = $employees->map(fn ($employee) => [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->fullName(),
                'area' => $employee->area?->name,
                'area_color' => $employee->area?->color,
            ],
            'metrics' => $metrics[$employee->id],
        ]);

        if ($request->query('format') === 'csv') {
            ActivityLogger::log('exported', description: "Exportó el reporte de asistencia del {$from->format('d/m/Y')} al {$to->format('d/m/Y')}");

            return Csv::download(
                "asistencia-{$from->toDateString()}-{$to->toDateString()}.csv",
                ['No.', 'Empleado', 'Área', ...array_values(AttendanceSummary::METRICS)],
                $rows->map(fn ($row) => [
                    $row['employee']['employee_code'], $row['employee']['name'], $row['employee']['area'],
                    ...array_values($row['metrics']),
                ]),
            );
        }

        return response()->json([
            'metrics' => AttendanceSummary::METRICS,
            'rows' => $rows,
        ]);
    }
}
