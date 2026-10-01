<?php

namespace App\Filament\Widgets;

use App\Enums\CompanyStatus;
use App\Models\Plan;
use App\Support\PlatformMetrics;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Tablero principal con el estilo del sistema anterior: tarjetas de métricas
 * con icono, tarjetas de suscripción, ingresos, crecimiento, distribución de
 * planes y detalle por empresa.
 */
class PlatformOverview extends Widget
{
    /** Colores de barra del tablero anterior, en orden fijo por plan. */
    public const PLAN_COLORS = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16'];

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.platform-overview';

    protected function getViewData(): array
    {
        $metrics = app(PlatformMetrics::class);
        $status = $metrics->companiesByStatus();
        $users = $metrics->userTotals();
        $tenant = $metrics->tenantTotals();
        $organization = $metrics->organizationTotals();
        $revenue = $metrics->revenueByPlan();
        $companiesGrowth = $metrics->registrationsByMonth();
        $usersGrowth = $metrics->usersByMonth();
        $punches = $metrics->punchesByDay(14);
        $byPlan = $metrics->companiesByPlan();
        $inactive = $status[CompanyStatus::Suspended->value] + $status[CompanyStatus::Cancelled->value] + $status[CompanyStatus::DeletionPending->value];

        return [
            'cards' => [
                ['label' => 'Empresas', 'value' => array_sum($status), 'icon' => 'bi-building', 'tone' => 'indigo',
                    'good' => ($status['active'] + $status['trial'] + $status['past_due']).' activas', 'bad' => $inactive.' inactivas'],
                ['label' => 'Usuarios', 'value' => $users['total'], 'icon' => 'bi-people', 'tone' => 'blue',
                    'good' => $users['active_30d'].' activos', 'muted' => ($users['total'] - $users['active_30d']).' inactivos (30 días)'],
                ['label' => 'Empleados', 'value' => $tenant['employees'], 'icon' => 'bi-person-badge', 'tone' => 'emerald',
                    'good' => number_format($tenant['punches_today']).' checadas hoy'],
                ['label' => 'Oficinas', 'value' => $organization['offices'], 'icon' => 'bi-geo-alt', 'tone' => 'amber',
                    'muted' => $organization['kiosks'].' kioscos'],
                ['label' => 'Áreas', 'value' => $organization['areas'], 'icon' => 'bi-diagram-3', 'tone' => 'purple',
                    'muted' => $users['with_app'].' con app'],
            ],
            'subscriptions' => $metrics->subscriptionBuckets(),
            'revenueTotal' => $metrics->monthlyRevenue(),
            'revenueAtRisk' => $metrics->revenueAtRisk(),
            'trialPipeline' => $metrics->trialPipeline(),
            // Pastel: ingreso por plan (solo los planes que generan ingreso)
            'revenueChart' => [
                'type' => 'pie',
                'labels' => array_keys($paid = array_filter($revenue)),
                'data' => array_values($paid),
                'colors' => array_values(array_intersect_key(
                    array_combine(array_keys($revenue), array_slice(self::PLAN_COLORS, 0, count($revenue))),
                    $paid,
                )),
                'money' => true,
            ],
            // Pastel: empresas por plan (el color de cada plan es el mismo en todas las gráficas)
            'plansChart' => [
                'type' => 'pie',
                'labels' => array_keys($byPlan),
                'data' => array_values($byPlan),
                'colors' => array_slice(self::PLAN_COLORS, 0, count($byPlan)),
                'suffix' => ' empresas',
            ],
            // Pastel: empresas por estado (verde activas, azul prueba, ámbar vencidas, gris sin plan, rojo bajas)
            'statusChart' => [
                'type' => 'pie',
                'labels' => array_keys($statusSlices = array_filter([
                    'Activas' => $status['active'],
                    'En prueba' => $status['trial'],
                    'Pago vencido' => $status['past_due'],
                    'Sin verificar o eligiendo plan' => $status['pending'] + $status['onboarding'],
                    'Suspendidas, canceladas o en baja' => $inactive,
                ])),
                'data' => array_values($statusSlices),
                'colors' => array_values(array_intersect_key([
                    'Activas' => '#10b981',
                    'En prueba' => '#3b82f6',
                    'Pago vencido' => '#f59e0b',
                    'Sin verificar o eligiendo plan' => '#94a3b8',
                    'Suspendidas, canceladas o en baja' => '#ef4444',
                ], $statusSlices)),
                'suffix' => ' empresas',
            ],
            'growthChart' => [
                'type' => 'line',
                'labels' => array_map(PlatformMetrics::monthLabel(...), array_keys($companiesGrowth)),
                'series' => [
                    ['label' => 'Empresas', 'data' => array_values($companiesGrowth), 'color' => '#6366f1'],
                    ['label' => 'Usuarios', 'data' => array_values($usersGrowth), 'color' => '#10b981'],
                ],
                'suffix' => ' registros',
            ],
            'punchesChart' => [
                'type' => 'line',
                'labels' => array_map(fn (string $day) => Carbon::parse($day)->format('d/m'), array_keys($punches)),
                'series' => [['label' => 'Checadas', 'data' => array_values($punches), 'color' => '#3b82f6']],
            ],
            'punchesTotal' => array_sum($punches),
            'plans' => Plan::query()->orderBy('sort_order')->get()->map(fn (Plan $plan) => [
                'name' => $plan->name,
                'tier' => $plan->database_tier->label(),
                'count' => $byPlan[$plan->name] ?? 0,
            ]),
            'companies' => $metrics->companyDetails(),
            'companiesTotal' => array_sum($status),
        ];
    }
}
