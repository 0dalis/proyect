<?php

namespace App\Support;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyDeletion;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Métricas globales para el panel Super Admin. Recorre todas las bases de
 * empresa con una conexión de sondeo independiente de la empresa activa.
 */
class PlatformMetrics
{
    private const CACHE_SECONDS = 60;

    private const TIMEZONE = 'America/Mexico_City';

    /**
     * @return array<string, int>
     */
    public function companiesByStatus(): array
    {
        $counts = Company::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(CompanyStatus::cases())
            ->mapWithKeys(fn (CompanyStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * Ingreso mensual estimado de las empresas que pagan (activas o con pago vencido).
     */
    public function monthlyRevenue(): float
    {
        return $this->payingCompanies()->sum(fn (Company $company) => $company->estimatedMonthlyPrice());
    }

    /**
     * Lo que sumarían las pruebas en curso si todas se convirtieran.
     */
    public function trialPipeline(): float
    {
        return Company::query()->with('plan')->where('status', CompanyStatus::Trial)->get()
            ->sum(fn (Company $company) => $company->estimatedMonthlyPrice());
    }

    /**
     * @return array<string, float> nombre del plan => ingreso mensual
     */
    public function revenueByPlan(): array
    {
        $byPlan = $this->payingCompanies()->groupBy('plan_id');

        return Plan::query()->orderBy('sort_order')->get()
            ->mapWithKeys(fn (Plan $plan) => [
                $plan->name => round(($byPlan[$plan->id] ?? collect())->sum(fn (Company $c) => $c->estimatedMonthlyPrice()), 2),
            ])
            ->all();
    }

    /**
     * @return array<string, int> "2026-09" => empresas registradas
     */
    public function registrationsByMonth(int $months = 12): array
    {
        $from = now()->startOfMonth()->subMonths($months - 1);
        $counts = Company::query()
            ->where('created_at', '>=', $from)
            ->get(['created_at'])
            ->countBy(fn (Company $company) => $company->created_at->format('Y-m'));

        return collect(range(0, $months - 1))
            ->mapWithKeys(fn (int $i) => [$key = $from->copy()->addMonths($i)->format('Y-m') => (int) ($counts[$key] ?? 0)])
            ->all();
    }

    public function trialsEndingSoon(int $days = 7): int
    {
        return Company::query()->where('status', CompanyStatus::Trial)
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)])
            ->count();
    }

    /**
     * Estado de cada base: en línea, latencia, tamaño y empresas que aloja.
     *
     * @return list<array<string, mixed>>
     */
    public function databases(): array
    {
        return Cache::remember('platform.databases', self::CACHE_SECONDS, function () {
            $companies = Company::query()->with('plan')->whereNotNull('database')->get()->groupBy('database');
            $sizes = $this->databaseSizes();

            $rows = [[
                'name' => config('database.connections.central.database'),
                'tier' => 'Central',
                'companies' => Company::query()->count(),
            ]];

            foreach ($companies as $database => $group) {
                $rows[] = [
                    'name' => $database,
                    'tier' => $group->first()->plan->database_tier->label(),
                    'companies' => $group->count(),
                ];
            }

            return array_map(function (array $row) use ($sizes) {
                $probe = $this->probe($row['name']);

                return [
                    ...$row,
                    ...$probe,
                    'size_mb' => $sizes[$row['name']]['size_mb'] ?? 0,
                    'tables' => $sizes[$row['name']]['tables'] ?? 0,
                ];
            }, $rows);
        });
    }

    /**
     * Empleados activos y checadas de hoy sumando todas las bases.
     *
     * @return array{employees: int, punches_today: int}
     */
    public function tenantTotals(): array
    {
        return Cache::remember('platform.tenant-totals', self::CACHE_SECONDS, function () {
            $totals = ['employees' => 0, 'punches_today' => 0];

            foreach ($this->tenantDatabases() as $database) {
                $totals['employees'] += (int) $this->onTenant($database, fn ($db) => $db->table('employees')
                    ->where('status', 'active')->whereNull('deleted_at')->count());
                $totals['punches_today'] += (int) $this->onTenant($database, fn ($db) => $db->table('attendance_records')
                    ->where('recorded_at', '>=', now(self::TIMEZONE)->startOfDay()->utc())->count());
            }

            return $totals;
        });
    }

    /**
     * @return array<string, int> "2026-09-25" => checadas de todas las empresas
     */
    public function punchesByDay(int $days = 30): array
    {
        return Cache::remember("platform.punches.{$days}", self::CACHE_SECONDS, function () use ($days) {
            $from = now(self::TIMEZONE)->startOfDay()->subDays($days - 1);
            $offset = (int) $from->utcOffset();
            $totals = [];

            foreach ($this->tenantDatabases() as $database) {
                $rows = $this->onTenant($database, fn ($db) => $db->table('attendance_records')
                    ->where('recorded_at', '>=', $from->copy()->utc())
                    ->selectRaw('DATE(DATE_ADD(recorded_at, INTERVAL ? MINUTE)) as day, count(*) as total', [$offset])
                    ->groupBy('day')
                    ->pluck('total', 'day')) ?? [];

                foreach ($rows as $day => $total) {
                    $totals[$day] = ($totals[$day] ?? 0) + $total;
                }
            }

            return collect(range(0, $days - 1))
                ->mapWithKeys(fn (int $i) => [$key = $from->copy()->addDays($i)->toDateString() => (int) ($totals[$key] ?? 0)])
                ->all();
        });
    }

    /**
     * @return array<string, string>
     */
    public function systemHealth(): array
    {
        $failedJobs = DB::connection('central')->table('failed_jobs')->count();
        $pendingJobs = DB::connection('central')->table('jobs')->count();
        $freeBytes = @disk_free_space(storage_path()) ?: 0;
        $totalBytes = @disk_total_space(storage_path()) ?: 1;

        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'mysql' => (string) DB::connection('central')->selectOne('select version() as v')->v,
            'queue' => config('queue.default'),
            'pending_jobs' => (string) $pendingJobs,
            'failed_jobs' => (string) $failedJobs,
            'disk_free' => round($freeBytes / 1024 ** 3, 1).' GB libres de '.round($totalBytes / 1024 ** 3).' GB',
            'disk_free_percent' => (string) round($freeBytes / $totalBytes * 100),
            'mail' => config('mail.default'),
            'stripe' => filled(config('cashier.secret')) ? 'configurado' : 'sin configurar',
        ];
    }

    /**
     * @return Collection<int, Company>
     */
    /**
     * Usuarios de todas las empresas (del tablero anterior: total y activos).
     *
     * @return array{total: int, owners: int, with_app: int, active_30d: int, blocked: int}
     */
    public function userTotals(): array
    {
        return Cache::remember('platform.users', self::CACHE_SECONDS, fn () => [
            'total' => User::query()->count(),
            'owners' => User::query()->where('is_owner', true)->count(),
            'with_app' => User::query()->whereNotNull('employee_id')->where('app_access', true)->whereNull('blocked_at')->count(),
            'active_30d' => User::query()->where('last_login_at', '>=', now()->subDays(30))->count(),
            'blocked' => User::query()->whereNotNull('blocked_at')->count(),
        ]);
    }

    /**
     * Oficinas y áreas sumando todas las bases.
     *
     * @return array{offices: int, areas: int, kiosks: int}
     */
    public function organizationTotals(): array
    {
        return Cache::remember('platform.organization', self::CACHE_SECONDS, function () {
            $totals = ['offices' => 0, 'areas' => 0, 'kiosks' => 0];

            foreach ($this->tenantDatabases() as $database) {
                foreach (['offices', 'areas', 'kiosks'] as $table) {
                    $totals[$table] += (int) $this->onTenant($database, fn ($db) => $db->table($table)->count());
                }
            }

            return $totals;
        });
    }

    /**
     * Empresas por plan (las que pueden operar), para la distribución de planes.
     *
     * @return array<string, int>
     */
    public function companiesByPlan(): array
    {
        $counts = Company::query()
            ->whereIn('status', [CompanyStatus::Trial, CompanyStatus::Active, CompanyStatus::PastDue])
            ->selectRaw('plan_id, count(*) as total')->groupBy('plan_id')->pluck('total', 'plan_id');

        return Plan::query()->orderBy('sort_order')->get()
            ->mapWithKeys(fn (Plan $plan) => [$plan->name => (int) ($counts[$plan->id] ?? 0)])
            ->all();
    }

    /**
     * @return array<string, int> "2026-09" => usuarios creados
     */
    public function usersByMonth(int $months = 12): array
    {
        $from = now()->startOfMonth()->subMonths($months - 1);
        $counts = User::query()->where('created_at', '>=', $from)->get(['created_at'])
            ->countBy(fn (User $user) => $user->created_at->format('Y-m'));

        return collect(range(0, $months - 1))
            ->mapWithKeys(fn (int $i) => [$key = $from->copy()->addMonths($i)->format('Y-m') => (int) ($counts[$key] ?? 0)])
            ->all();
    }

    /**
     * Ingreso mensual de las empresas con pago vencido (en riesgo de bajar a Free).
     */
    public function revenueAtRisk(): float
    {
        return Company::query()->with('plan')->where('status', CompanyStatus::PastDue)->get()
            ->sum(fn (Company $company) => $company->estimatedMonthlyPrice());
    }

    /**
     * Bajas (solicitudes de eliminación) de este mes y en proceso.
     *
     * @return array{this_month: int, in_progress: int}
     */
    public function deletions(): array
    {
        return [
            'this_month' => CompanyDeletion::query()->where('requested_at', '>=', now()->startOfMonth())->count(),
            'in_progress' => CompanyDeletion::query()->inProgress()->count(),
        ];
    }

    /**
     * Pagos anuales contra mensuales de las empresas que pagan.
     *
     * @return array{month: int, year: int}
     */
    public function billingIntervals(): array
    {
        $counts = $this->payingCompanies()
            ->reject(fn (Company $company) => $company->plan->isFree())
            ->countBy(fn (Company $company) => $company->billing_interval ?? 'month');

        return ['month' => (int) ($counts['month'] ?? 0), 'year' => (int) ($counts['year'] ?? 0)];
    }

    /**
     * Empleados, oficinas y usuarios de cada empresa (para la tabla de empresas).
     *
     * @return array<int, array{employees: int, offices: int}>
     */
    public function countsByCompany(): array
    {
        return Cache::remember('platform.counts-by-company', self::CACHE_SECONDS, function () {
            $counts = [];

            foreach ($this->tenantDatabases() as $database) {
                $employees = $this->onTenant($database, fn ($db) => $db->table('employees')->where('status', 'active')
                    ->whereNull('deleted_at')->selectRaw('company_id, count(*) as total')->groupBy('company_id')->pluck('total', 'company_id')) ?? [];
                $offices = $this->onTenant($database, fn ($db) => $db->table('offices')
                    ->selectRaw('company_id, count(*) as total')->groupBy('company_id')->pluck('total', 'company_id')) ?? [];

                foreach ($employees as $companyId => $total) {
                    $counts[$companyId]['employees'] = (int) $total;
                }
                foreach ($offices as $companyId => $total) {
                    $counts[$companyId]['offices'] = (int) $total;
                }
            }

            return $counts;
        });
    }

    /**
     * Tarjetas de suscripción del tablero (como el sistema anterior).
     *
     * @return array{active: int, trial: int, ending: int, past_due: int, no_plan: int}
     */
    public function subscriptionBuckets(): array
    {
        $companies = Company::query()->with('plan')->get(['id', 'plan_id', 'status']);

        return [
            'active' => $companies->filter(fn (Company $c) => $c->status === CompanyStatus::Active && ! $c->plan->isFree())->count(),
            'trial' => $companies->where('status', CompanyStatus::Trial)->count(),
            'ending' => $this->trialsEndingSoon(),
            'past_due' => $companies->where('status', CompanyStatus::PastDue)->count(),
            // Free, o todavía sin elegir plan (sin verificar o eligiendo)
            'no_plan' => $companies->filter(fn (Company $c) => in_array($c->status, [CompanyStatus::Pending, CompanyStatus::Onboarding], true)
                || ($c->status === CompanyStatus::Active && $c->plan->isFree()))->count(),
        ];
    }

    /**
     * Filas de "Detalle por empresa": usuarios, empleados y oficinas de cada una.
     *
     * @return Collection<int, Company>
     */
    public function companyDetails(int $limit = 15): Collection
    {
        $counts = $this->countsByCompany();

        return Company::query()->with('plan')->withCount('users')->latest()->limit($limit)->get()
            ->each(function (Company $company) use ($counts) {
                $company->setAttribute('employees_total', $counts[$company->id]['employees'] ?? 0);
                $company->setAttribute('offices_total', $counts[$company->id]['offices'] ?? 0);
            });
    }

    private function payingCompanies(): Collection
    {
        return Company::query()->with('plan')
            ->whereIn('status', [CompanyStatus::Active, CompanyStatus::PastDue])
            ->get();
    }

    /**
     * @return list<string>
     */
    private function tenantDatabases(): array
    {
        return Company::query()->whereNotNull('database')->distinct()->pluck('database')->all();
    }

    /**
     * @return array<string, array{size_mb: float, tables: int}>
     */
    private function databaseSizes(): array
    {
        $prefix = config('tenancy.database_prefix');

        return DB::connection('central')->table('information_schema.tables')
            ->where('table_schema', 'like', str_replace('_', '\_', $prefix).'%')
            ->groupBy('table_schema')
            ->selectRaw('table_schema as name, round(sum(data_length + index_length) / 1024 / 1024, 2) as size_mb, count(*) as tables')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->name => ['size_mb' => (float) $row->size_mb, 'tables' => (int) $row->tables]])
            ->all();
    }

    /**
     * @return array{online: bool, latency_ms: float|null, error: string|null}
     */
    private function probe(string $database): array
    {
        $started = microtime(true);

        try {
            $this->onTenant($database, fn ($db) => $db->select('select 1'), rethrow: true);

            return ['online' => true, 'latency_ms' => round((microtime(true) - $started) * 1000, 1), 'error' => null];
        } catch (Throwable $e) {
            return ['online' => false, 'latency_ms' => null, 'error' => str($e->getMessage())->limit(120)->toString()];
        }
    }

    private function onTenant(string $database, callable $callback, bool $rethrow = false): mixed
    {
        config(['database.connections.probe' => [...config('database.connections.tenant'), 'database' => $database]]);
        DB::purge('probe');

        try {
            return $callback(DB::connection('probe'));
        } catch (Throwable $e) {
            if ($rethrow) {
                throw $e;
            }

            return null;
        } finally {
            DB::purge('probe');
        }
    }

    public static function monthLabel(string $yearMonth): string
    {
        return Carbon::createFromFormat('Y-m', $yearMonth)->locale('es')->translatedFormat('M y');
    }
}
