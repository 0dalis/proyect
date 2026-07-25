<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de control') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- ===== Tarjetas de métricas principales ===== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Empresas -->
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Empresas</p>
                            <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $totalCompanies }}</p>
                        </div>
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-building text-indigo-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3 text-xs">
                        <span class="text-green-600 font-medium">{{ $activeCompanies }} activas</span>
                        <span class="text-neutral-400">|</span>
                        <span class="text-red-500 font-medium">{{ $inactiveCompanies }} inactivas</span>
                    </div>
                </div>

                <!-- Usuarios -->
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Usuarios</p>
                            <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $totalUsers }}</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-people text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3 text-xs">
                        <span class="text-green-600 font-medium">{{ $activeUsers }} activos</span>
                        <span class="text-neutral-400">|</span>
                        <span class="text-neutral-500">{{ $totalUsers - $activeUsers }} inactivos</span>
                    </div>
                </div>

                <!-- Empleados -->
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Empleados</p>
                            <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $totalEmployees }}</p>
                        </div>
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-person-badge text-emerald-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3 text-xs">
                        <span class="text-green-600 font-medium">{{ $activeEmployees }} activos</span>
                    </div>
                </div>

                <!-- Sucursales -->
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Sucursales</p>
                            <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $totalOffices }}</p>
                        </div>
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-geo-alt text-amber-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3 text-xs">
                        <span class="text-green-600 font-medium">{{ $activeOffices }} activas</span>
                    </div>
                </div>

                <!-- Áreas -->
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Áreas</p>
                            <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $totalAreas }}</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-diagram-3 text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Tarjetas de suscripciones ===== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Suscripción activa -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-green-500 p-4">
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Suscripción activa</p>
                    <p class="text-2xl font-bold text-green-700">{{ $companiesWithActiveSubscription }}</p>
                    <p class="text-xs text-neutral-400 mt-1">Empresas al corriente</p>
                </div>

                <!-- En periodo de prueba -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-blue-500 p-4">
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">En prueba</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $companiesOnTrial }}</p>
                    <p class="text-xs text-neutral-400 mt-1">Trial activo</p>
                </div>

                <!-- Próximas a vencer -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-amber-500 p-4">
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Por vencer</p>
                    <p class="text-2xl font-bold text-amber-700">{{ $nearingExpiration }}</p>
                    <p class="text-xs text-neutral-400 mt-1">Próximos 30 días</p>
                </div>

                <!-- Vencidas -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-red-500 p-4">
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Vencidas</p>
                    <p class="text-2xl font-bold text-red-700">{{ $companiesExpired }}</p>
                    <p class="text-xs text-neutral-400 mt-1">Sin plan vigente</p>
                </div>

                <!-- Sin plan -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-gray-400 p-4">
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Sin plan</p>
                    <p class="text-2xl font-bold text-gray-700">{{ $companiesWithoutPlan }}</p>
                    <p class="text-xs text-neutral-400 mt-1">No asignado</p>
                </div>
            </div>

            <!-- ===== Sección gráficas ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Ingresos estimados -->
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-800">Ingresos mensuales estimados</h3>
                            <p class="text-xs text-neutral-400">Según suscripciones activas</p>
                        </div>
                        <span class="text-lg font-bold text-emerald-600">${{ number_format($estimatedMonthlyRevenue, 2) }}</span>
                    </div>
                    <div class="p-5">
                        <div style="height: 260px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Crecimiento del sistema -->
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-800">Crecimiento del sistema</h3>
                            <p class="text-xs text-neutral-400">Últimos 12 meses</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-indigo-500 inline-block"></span> Empresas
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Usuarios
                            </span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div style="height: 260px;">
                            <canvas id="growthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Distribución de planes ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-100">
                    <h3 class="text-sm font-semibold text-neutral-800">Distribución de planes</h3>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($plansDistribution as $plan)
                            <div class="flex items-center justify-between bg-neutral-50 rounded-lg p-3 border border-neutral-200">
                                <div>
                                    <p class="text-sm font-medium text-neutral-800">{{ $plan->nombre }}</p>
                                    <p class="text-xs text-neutral-500">{{ $plan->tipo }}</p>
                                </div>
                                <span class="text-lg font-bold text-indigo-600">{{ $plan->companies_count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ===== Tabla de empresas ===== -->
            <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-neutral-800">Detalle por empresa</h3>
                    <span class="text-xs text-neutral-400">{{ $companies->count() }} empresas</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Usuarios</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Empleados</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sucursales</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Suscripción</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($companies as $company)
                                <tr class="hover:bg-neutral-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm font-medium text-neutral-900">{{ $company->name }}</p>
                                        <p class="text-xs text-neutral-400">{{ $company->code }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        @if($company->plan)
                                            <span class="inline-flex items-center gap-1">
                                                {{ $company->plan->nombre }}
                                                <span class="text-xs text-neutral-400">({{ $company->plan->tipo }})</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-neutral-400">Sin plan</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <span class="font-medium">{{ $company->users_count }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <span class="font-medium">{{ $company->employees_count }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <span class="font-medium">{{ $company->offices_count }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($company->hasActiveSubscription())
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                Activa
                                            </span>
                                            <p class="text-xs text-neutral-400 mt-0.5">hasta {{ $company->subscription_ends_at->format('d/m/Y') }}</p>
                                        @elseif($company->isOnTrial())
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                Trial
                                            </span>
                                            <p class="text-xs text-neutral-400 mt-0.5">hasta {{ $company->trial_ends_at->format('d/m/Y') }}</p>
                                        @else
                                            @if($company->subscription_ends_at && $company->subscription_ends_at->lt(now()))
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                    Vencida
                                                </span>
                                                <p class="text-xs text-neutral-400 mt-0.5">{{ $company->subscription_ends_at->format('d/m/Y') }}</p>
                                            @else
                                                <span class="text-xs text-neutral-400">—</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($company->isActive())
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                Activa
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                Inactiva
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ===== Gráfica de Ingresos por Plan =====
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = @json($revenueByPlan);
        const planLabels = revenueData.map(item => item.nombre);
        const planRevenue = revenueData.map(item => parseFloat(item.precio) * parseInt(item.total));
        const planColors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16'];

        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: planLabels,
                datasets: [{
                    label: 'Ingreso mensual estimado',
                    data: planRevenue,
                    backgroundColor: planColors.slice(0, planLabels.length),
                    borderColor: planColors.slice(0, planLabels.length),
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return '$' + ctx.raw.toLocaleString('es-MX', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('es-MX');
                            }
                        },
                        grid: { color: '#f3f4f6' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // ===== Gráfica de Crecimiento =====
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        const companiesGrowth = @json($companiesGrowth);
        const usersGrowth = @json($usersGrowth);

        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: companiesGrowth.labels,
                datasets: [
                    {
                        label: 'Empresas',
                        data: companiesGrowth.data,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Usuarios',
                        data: usersGrowth.data,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ' + ctx.raw + ' registros';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#f3f4f6' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

    });
    </script>
    @endpush
</x-app-layout>
