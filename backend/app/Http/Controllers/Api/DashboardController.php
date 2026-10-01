<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceType;
use App\Enums\EmployeeStatus;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Device;
use App\Models\EmployeeRequest;
use App\Support\AttendanceSummary;
use App\Support\TeamToday;
use App\Support\Visibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Datos del inicio del panel, limitados a los empleados que ve cada rol.
 * Las fechas son las de la zona horaria de la empresa (y de cada oficina
 * para el estado de hoy).
 */
class DashboardController extends Controller
{
    private const DAYS = 14;

    public function __invoke(Request $request, TeamToday $teamToday, AttendanceSummary $summary): JsonResponse
    {
        $user = $request->user();
        $timezone = $user->company->timezone ?? config('app.timezone');
        $today = now($timezone)->startOfDay();
        $visible = Visibility::employees($user)->select('id');
        $active = Visibility::employees($user)->where('status', EmployeeStatus::Active)
            ->with(['shift', 'office', 'area'])->get();

        $series = $this->series($visible, $today);
        $team = $teamToday->build($active, $timezone);

        return response()->json([
            // Compatibilidad: tarjetas del inicio
            'today' => [
                ...$series->last(),
                'active_employees' => $active->count(),
                'checked_in' => $team['registered'],
            ],
            'team' => $team,
            'series' => $series->values(),
            'month' => $this->month($summary, $active, $today),
            'punctuality' => $this->punctuality($visible, $today),
            'upcoming_vacations' => $this->upcomingVacations($visible, $today),
            'pending_requests' => EmployeeRequest::query()->whereIn('employee_id', $visible)
                ->where('status', RequestStatus::Pending)->count(),
            'pending_by_type' => EmployeeRequest::query()->whereIn('employee_id', $visible)
                ->where('status', RequestStatus::Pending)
                ->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'pending_devices' => $user->can('users.manage')
                ? Device::query()->whereNull('approved_at')->whereNull('revoked_at')->count()
                : 0,
        ]);
    }

    /**
     * Entradas por día y estado de los últimos 14 días.
     */
    private function series($visible, Carbon $today)
    {
        $from = $today->copy()->subDays(self::DAYS - 1);

        $byDay = AttendanceRecord::query()
            ->whereIn('employee_id', $visible)
            ->where('type', AttendanceType::CheckIn)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->selectRaw('DATE(work_date) as day, status, count(*) as total')
            ->groupBy('day', 'status')
            ->toBase()
            ->get()
            ->groupBy('day');

        return collect(range(0, self::DAYS - 1))->map(function (int $offset) use ($from, $byDay) {
            $day = $from->copy()->addDays($offset)->toDateString();
            $counts = ($byDay[$day] ?? collect())->pluck('total', 'status');

            return [
                'day' => $day,
                'on_time' => (int) ($counts['on_time'] ?? 0),
                'late' => (int) ($counts['late'] ?? 0),
                'absent' => (int) ($counts['absent'] ?? 0),
            ];
        });
    }

    /**
     * Totales del mes en curso y los empleados con más retardos.
     *
     * @return array<string, mixed>
     */
    private function month(AttendanceSummary $summary, $employees, Carbon $today): array
    {
        $metrics = $employees->isEmpty() ? [] : $summary->forEmployees($employees, $today->copy()->startOfMonth(), $today->copy());
        $sum = fn (string $key) => array_sum(array_column($metrics, $key));
        $names = $employees->keyBy('id');

        return [
            'worked_days' => $sum('worked_days'),
            'scheduled_days' => $sum('scheduled_days'),
            'lates' => $sum('lates'),
            'absences' => $sum('unjustified_absences'),
            'excused_days' => $sum('excused_days'),
            'overtime_hours' => round($sum('overtime_minutes') / 60, 1),
            'attendance_rate' => $sum('scheduled_days') > 0
                ? round($sum('worked_days') / max(1, $sum('scheduled_days') - $sum('excused_days')) * 100)
                : null,
            'top_lates' => collect($metrics)
                ->filter(fn (array $m) => $m['lates'] > 0)
                ->sortByDesc('lates')
                ->take(5)
                ->map(fn (array $m, int $id) => [
                    'id' => $id,
                    'public_id' => $names[$id]->getRouteKey(),
                    'name' => $names[$id]->fullName(),
                    'lates' => $m['lates'],
                    'minutes_late' => $m['minutes_late'],
                ])
                ->values(),
        ];
    }

    /**
     * Puntualidad de los últimos 30 días contra los 30 anteriores.
     *
     * @return array{current: ?int, previous: ?int}
     */
    private function punctuality($visible, Carbon $today): array
    {
        $rate = function (Carbon $from, Carbon $to) use ($visible): ?int {
            $counts = AttendanceRecord::query()
                ->whereIn('employee_id', $visible)
                ->where('type', AttendanceType::CheckIn)
                ->whereDate('work_date', '>=', $from->toDateString())
                ->whereDate('work_date', '<=', $to->toDateString())
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
            $onTime = (int) ($counts['on_time'] ?? 0);
            $total = $onTime + (int) ($counts['late'] ?? 0);

            return $total > 0 ? (int) round($onTime / $total * 100) : null;
        };

        return [
            'current' => $rate($today->copy()->subDays(29), $today),
            'previous' => $rate($today->copy()->subDays(59), $today->copy()->subDays(30)),
        ];
    }

    /**
     * Vacaciones aprobadas en curso o que empiezan en los próximos 14 días.
     */
    private function upcomingVacations($visible, Carbon $today)
    {
        return EmployeeRequest::query()
            ->whereIn('employee_id', $visible)
            ->where('status', RequestStatus::Approved)
            ->where('type', RequestType::Vacation)
            ->whereDate('ends_on', '>=', $today->toDateString())
            ->whereDate('starts_on', '<=', $today->copy()->addDays(14)->toDateString())
            ->with('employee:id,first_name,last_name')
            ->orderBy('starts_on')
            ->limit(8)
            ->get()
            ->map(fn (EmployeeRequest $vacation) => [
                'id' => $vacation->id,
                'name' => $vacation->employee?->fullName(),
                'starts_on' => $vacation->starts_on->toDateString(),
                'ends_on' => $vacation->ends_on->toDateString(),
                'ongoing' => $vacation->starts_on->lte($today),
            ]);
    }
}
