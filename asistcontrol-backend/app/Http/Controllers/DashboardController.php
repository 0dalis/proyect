<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Area;
use App\Models\Plan;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth'];
    }

    public function index()
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('is_active', true)->count();
        $inactiveCompanies = $totalCompanies - $activeCompanies;

        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();

        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();

        $totalOffices = Office::count();
        $activeOffices = Office::where('is_active', true)->count();

        $totalAreas = Area::count();

        $companiesOnTrial = Company::where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->where(function ($q) {
                $q->whereNull('subscription_ends_at')
                  ->orWhere('subscription_ends_at', '<', now());
            })
            ->count();

        $companiesWithActiveSubscription = Company::where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '>', now())
            ->count();

        $companiesExpired = Company::where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('trial_ends_at')
                       ->orWhere('trial_ends_at', '<', now());
                })->where(function ($sq) {
                    $sq->whereNull('subscription_ends_at')
                       ->orWhere('subscription_ends_at', '<', now());
                });
            })
            ->where(function ($q) {
                $q->whereNotNull('trial_ends_at')
                  ->orWhereNotNull('subscription_ends_at');
            })
            ->count();

        $companiesWithoutPlan = Company::where('is_active', true)
            ->whereNull('plan_id')
            ->count();

        $plansDistribution = Plan::withCount('companies')->get();

        $companies = Company::with(['plan', 'users', 'offices'])
            ->withCount(['users', 'employees', 'offices'])
            ->orderBy('name')
            ->get();

        $nearingExpiration = Company::where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(30)])
            ->count();

        // ===== Datos para gráficas =====

        // Ingresos estimados por plan (empresas activas con suscripción activa)
        $revenueByPlan = Company::where('companies.is_active', true)
            ->whereNotNull('companies.subscription_ends_at')
            ->where('companies.subscription_ends_at', '>', now())
            ->join('plans', 'companies.plan_id', '=', 'plans.id')
            ->select('plans.nombre', 'plans.precio', DB::raw('count(*) as total'))
            ->groupBy('plans.id', 'plans.nombre', 'plans.precio')
            ->get();

        $estimatedMonthlyRevenue = $revenueByPlan->sum(function ($item) {
            return $item->precio * $item->total;
        });

        // Ingresos totales incluyendo trial (precio completo)
        $revenueIncludingTrial = Company::where('companies.is_active', true)
            ->whereNotNull('companies.plan_id')
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNotNull('companies.subscription_ends_at')
                       ->where('companies.subscription_ends_at', '>', now());
                })->orWhere(function ($sq) {
                    $sq->whereNotNull('companies.trial_ends_at')
                       ->where('companies.trial_ends_at', '>', now());
                });
            })
            ->join('plans', 'companies.plan_id', '=', 'plans.id')
            ->select(DB::raw('SUM(plans.precio) as total'))
            ->value('total') ?? 0;

        // Crecimiento: empresas registradas por mes (últimos 12 meses)
        $companiesGrowth = $this->getMonthlyGrowth('companies', 'created_at');
        $usersGrowth = $this->getMonthlyGrowth('users', 'created_at');

        return view('dashboard', compact(
            'totalCompanies',
            'activeCompanies',
            'inactiveCompanies',
            'totalUsers',
            'activeUsers',
            'totalEmployees',
            'activeEmployees',
            'totalOffices',
            'activeOffices',
            'totalAreas',
            'companiesOnTrial',
            'companiesWithActiveSubscription',
            'companiesExpired',
            'companiesWithoutPlan',
            'plansDistribution',
            'companies',
            'nearingExpiration',
            'revenueByPlan',
            'estimatedMonthlyRevenue',
            'revenueIncludingTrial',
            'companiesGrowth',
            'usersGrowth'
        ));
    }

    /**
     * Obtiene registros agrupados por mes para los últimos 12 meses.
     */
    private function getMonthlyGrowth(string $table, string $column): array
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $months->push([
                'year' => $date->year,
                'month' => $date->month,
                'label' => $this->monthLabel($date->month),
            ]);
        }

        $data = DB::table($table)
            ->select(
                DB::raw('YEAR(' . $column . ') as year'),
                DB::raw('MONTH(' . $column . ') as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where($column, '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        $result = ['labels' => [], 'data' => []];
        foreach ($months as $m) {
            $key = $m['year'] . '-' . str_pad($m['month'], 2, '0', STR_PAD_LEFT);
            $result['labels'][] = $m['label'] . ' ' . $m['year'];
            $result['data'][] = isset($data[$key]) ? $data[$key]->count : 0;
        }

        return $result;
    }

    private function monthLabel(int $month): string
    {
        $labels = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];
        return $labels[$month] ?? '';
    }
}
