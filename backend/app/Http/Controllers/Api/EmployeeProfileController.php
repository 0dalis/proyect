<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\AttendanceSummary;
use App\Support\Visibility;
use App\Tenancy\TenantManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Detalle de un empleado: gráficas por periodo, historial y reporte PDF.
 */
class EmployeeProfileController extends Controller
{
    private const MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public function __construct(private AttendanceSummary $summary) {}

    public function stats(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeView($request, $employee);
        [$period, $from, $to] = $this->period($request);

        $days = $this->summary->dailyStatuses($employee, $from, $to);
        $buckets = $period === 'year' ? $this->byMonth($days) : $this->byDay($days);

        return response()->json([
            'period' => ['type' => $period, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'buckets' => $buckets,
            'metrics' => $this->summary->forEmployees(collect([$employee->loadMissing('shift')]), $from, $to)[$employee->id],
            'days' => $period === 'year' ? [] : $days,
        ]);
    }

    /**
     * Lo que le pasó al empleado (cambios, justificaciones) y lo que él hizo.
     */
    public function activity(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeView($request, $employee);

        $userId = User::query()->where('company_id', $employee->company_id)->where('employee_id', $employee->id)->value('id');

        $logs = ActivityLog::query()
            ->where(fn ($query) => $query->where('employee_id', $employee->id)
                ->when($userId, fn ($q) => $q->orWhere('user_id', $userId)))
            ->latest('created_at')
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json([...$logs->toArray(), 'actions' => ActivityController::ACTIONS]);
    }

    public function report(Request $request, Employee $employee, TenantManager $tenants): Response
    {
        $this->authorizeView($request, $employee);
        [$period, $from, $to] = $this->period($request);

        $employee->loadMissing('shift', 'office', 'area');
        $days = $this->summary->dailyStatuses($employee, $from, $to);
        $metrics = $this->summary->forEmployees(collect([$employee]), $from, $to)[$employee->id];

        ActivityLogger::log('downloaded', $employee, "Descargó el reporte de asistencia de {$employee->fullName()} ({$from->format('d/m/Y')} – {$to->format('d/m/Y')})");

        return Pdf::loadView('pdf.employee-report', [
            'company' => $tenants->currentOrFail(),
            'employee' => $employee,
            'from' => $from,
            'to' => $to,
            'metrics' => $metrics,
            'metricLabels' => AttendanceSummary::METRICS,
            'days' => array_values(array_filter($days, fn ($day) => ! in_array($day['status'], ['not_employed'], true))),
            'calendarCapture' => $this->calendarCapture($request->input('calendar_capture')),
            'generatedBy' => $request->user()->name,
        ])->setPaper('letter')->download("reporte-{$employee->employee_code}-{$from->toDateString()}.pdf");
    }

    /**
     * Valida la captura del calendario que manda el panel (data URI en PNG) y
     * la devuelve lista para el <img> del PDF. Cualquier cosa rara se ignora:
     * el reporte sale igual, solo que sin la imagen.
     */
    private function calendarCapture(mixed $capture): ?string
    {
        if (! is_string($capture) || ! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $capture, $matches)) {
            return null;
        }

        $payload = $matches[1];
        if (strlen($payload) > 4_000_000 || base64_decode($payload, true) === false) {
            return null;
        }

        return "data:image/png;base64,{$payload}";
    }

    private function authorizeView(Request $request, Employee $employee): void
    {
        abort_unless(Visibility::canSeeEmployee($request->user(), $employee->id), 404);
    }

    /**
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    private function period(Request $request): array
    {
        $request->validate([
            'period' => ['nullable', 'in:week,month,year'],
            'date' => ['nullable', 'date'],
        ]);

        $period = $request->input('period', 'month');
        $date = $request->date('date') ?? now();

        return match ($period) {
            'week' => [$period, $date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'year' => [$period, $date->copy()->startOfYear(), $date->copy()->endOfYear()],
            default => [$period, $date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
        };
    }

    private function byDay(array $days): array
    {
        return array_map(fn (array $day) => [
            'key' => $day['date'],
            'label' => Carbon::parse($day['date'])->locale('es')->translatedFormat('D d'),
            'on_time' => (int) ($day['status'] === 'on_time'),
            'late' => (int) ($day['status'] === 'late'),
            'absent' => (int) ($day['status'] === 'absent'),
            'excused' => (int) ($day['status'] === 'excused'),
            'minutes_late' => $day['minutes_late'],
        ], $days);
    }

    private function byMonth(array $days): array
    {
        $buckets = [];

        foreach ($days as $day) {
            $month = (int) substr($day['date'], 5, 2);
            $buckets[$month] ??= ['key' => substr($day['date'], 0, 7), 'label' => self::MONTHS[$month - 1], 'on_time' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'minutes_late' => 0];

            if (in_array($day['status'], ['on_time', 'late', 'absent', 'excused'], true)) {
                $buckets[$month][$day['status']]++;
            }
            $buckets[$month]['minutes_late'] += $day['minutes_late'];
        }

        return array_values($buckets);
    }
}
