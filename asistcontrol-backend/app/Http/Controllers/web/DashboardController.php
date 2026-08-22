<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $today = now()->toDateString();

        $employees = $this->companyEmployees($company)->get();
        $totalEmployees = $employees->count();
        $activeEmployees = $employees->where('is_active', true)->count();
        $trackableEmployees = $employees
            ->where('is_active', true)
            ->filter(fn ($e) => ! is_null($e->user_id));

        $officesTotal = $company->offices()->count();
        $officesActive = $company->offices()->where('is_active', true)->count();

        $areasTotal = $company->areas()->count();
        $areasActive = $company->areas()->where('is_active', true)->count();

        $todayAttendances = Attendance::where('company_id', $company->id)
            ->whereDate('date', $today)
            ->get();

        $checkedInToday = AttendanceRecord::where('type', 'check_in')
            ->whereDate('recorded_at', $today)
            ->whereHas('user', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->count();

        $presentUserIds = $todayAttendances->pluck('user_id')->filter();
        $presentCount = $trackableEmployees->whereIn('user_id', $presentUserIds)->count();
        $absencesToday = max(0, $trackableEmployees->count() - $presentCount);

        $statusCounts = $todayAttendances->groupBy('status')->map->count();
        $present = (int) ($statusCounts['present'] ?? 0);
        $late = (int) ($statusCounts['late'] ?? 0);
        $absent = (int) ($statusCounts['absent'] ?? 0);
        $justified = (int) ($statusCounts['justified'] ?? 0);

        $expectedCount = $trackableEmployees->count();
        $attended = $present + $late;
        $assistancePercent = $expectedCount > 0 ? round($attended / $expectedCount * 100, 1) : 0;
        $punctualityPercent = $attended > 0 ? round($present / $attended * 100, 1) : 0;
        $workedHours = round($todayAttendances->sum('worked_minutes') / 60, 1);

        $recentRecords = AttendanceRecord::where('type', 'check_in')
            ->whereDate('recorded_at', $today)
            ->whereHas('user', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->with([
                'user' => fn ($q) => $q->with(['employee.office', 'employee.area']),
            ])
            ->orderByDesc('recorded_at')
            ->limit(6)
            ->get()
            ->map(function ($record) {
                $employee = $record->user?->employee;

                return [
                    'employee_name' => $employee?->full_name ?? ($record->user?->email ?? 'Empleado'),
                    'area' => $employee?->area?->name ?? null,
                    'office' => $employee?->office?->name ?? null,
                    'type' => $record->type,
                    'time' => $record->recorded_at?->format('H:i'),
                ];
            })
            ->values();

        [$trendLabels, $trendData] = $this->attendanceTrend($company, 7);

        $byOffice = $this->companyEmployees($company)
            ->join('offices', 'employees.office_id', '=', 'offices.id')
            ->select('offices.name as label', DB::raw('count(*) as value'))
            ->groupBy('offices.id', 'offices.name')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])
            ->values();

        $userEmployee = $request->user()->employee;

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ],
            'user' => [
                'first_name' => $userEmployee?->first_name,
                'last_name' => $userEmployee?->last_name,
            ],
            'stats' => [
                'employees_total' => $totalEmployees,
                'employees_active' => $activeEmployees,
                'offices_total' => $officesTotal,
                'offices_active' => $officesActive,
                'areas_total' => $areasTotal,
                'areas_active' => $areasActive,
                'attendance_today' => $checkedInToday,
                'absences_today' => $absencesToday,
            ],
            'summary' => [
                'assistance_percent' => $assistancePercent,
                'punctuality_percent' => $punctualityPercent,
                'worked_hours_today' => $workedHours,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'justified' => $justified,
                'expected' => $expectedCount,
            ],
            'recent_records' => $recentRecords,
            'attendance_trend' => [
                'labels' => $trendLabels,
                'data' => $trendData,
            ],
            'distribution_by_office' => [
                'labels' => $byOffice->pluck('label'),
                'data' => $byOffice->pluck('value'),
            ],
            'attendance_status_today' => [
                'labels' => ['Presente', 'Retardo', 'Ausente', 'Justificado'],
                'data' => [$present, $late, $absent, $justified],
            ],
        ]);
    }

    private function companyEmployees(Company $company)
    {
        return $company->employees()->where(function ($query) {
            $query->whereNull('user_id')
                ->orWhereHas('user', function ($userQuery) {
                    $userQuery->whereDoesntHave('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'owner');
                    });
                });
        });
    }

    private function attendanceTrend(Company $company, int $days): array
    {
        $dayLabels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $dayLabels[$date->dayOfWeekIso - 1];
            $data[] = Attendance::where('company_id', $company->id)
                ->whereDate('date', $date->toDateString())
                ->count();
        }

        return [$labels, $data];
    }
}
