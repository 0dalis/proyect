@php
    use App\Enums\CompanyStatus;

    $tones = [
        'indigo' => 'bg-indigo-100 text-indigo-600',
        'blue' => 'bg-blue-100 text-blue-600',
        'emerald' => 'bg-emerald-100 text-emerald-600',
        'amber' => 'bg-amber-100 text-amber-600',
        'purple' => 'bg-purple-100 text-purple-600',
    ];
    $subscriptionCards = [
        ['label' => 'Suscripción activa', 'value' => $subscriptions['active'], 'hint' => 'Empresas al corriente', 'border' => 'border-green-500', 'text' => 'text-green-700'],
        ['label' => 'En prueba', 'value' => $subscriptions['trial'], 'hint' => 'Prueba activa', 'border' => 'border-blue-500', 'text' => 'text-blue-700'],
        ['label' => 'Por vencer', 'value' => $subscriptions['ending'], 'hint' => 'Pruebas en los próximos 7 días', 'border' => 'border-amber-500', 'text' => 'text-amber-700'],
        ['label' => 'Pago vencido', 'value' => $subscriptions['past_due'], 'hint' => 'Cobro rechazado', 'border' => 'border-red-500', 'text' => 'text-red-700'],
        ['label' => 'Sin plan de pago', 'value' => $subscriptions['no_plan'], 'hint' => 'Free o sin elegir plan', 'border' => 'border-gray-400', 'text' => 'text-gray-700'],
    ];
    $statusBadge = fn (CompanyStatus $status) => match ($status) {
        CompanyStatus::Active => 'bg-green-100 text-green-800',
        CompanyStatus::Trial => 'bg-blue-100 text-blue-800',
        CompanyStatus::PastDue => 'bg-amber-100 text-amber-800',
        CompanyStatus::Pending, CompanyStatus::Onboarding => 'bg-gray-100 text-gray-700',
        default => 'bg-red-100 text-red-800',
    };
@endphp

<x-filament-widgets::widget>
    <div class="asist-dashboard space-y-6">

        {{-- Tarjetas de métricas principales --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($cards as $card)
                <div class="asist-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ $card['label'] }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($card['value']) }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $tones[$card['tone']] }}">
                            <i class="bi {{ $card['icon'] }} text-xl" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                        @isset($card['good'])
                            <span class="font-medium text-green-600">{{ $card['good'] }}</span>
                        @endisset
                        @isset($card['bad'])
                            <span class="text-gray-400">|</span>
                            <span class="font-medium text-red-500">{{ $card['bad'] }}</span>
                        @endisset
                        @isset($card['muted'])
                            @isset($card['good'])<span class="text-gray-400">|</span>@endisset
                            <span class="text-gray-500">{{ $card['muted'] }}</span>
                        @endisset
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tarjetas de suscripciones --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($subscriptionCards as $card)
                <div class="asist-card border-l-4 {{ $card['border'] }} p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ $card['label'] }}</p>
                    <p class="text-2xl font-bold {{ $card['text'] }}">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Gráficas --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="asist-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Ingresos mensuales estimados</h3>
                        <p class="text-xs text-gray-400">
                            Empresas que pagan · en riesgo ${{ number_format($revenueAtRisk, 2) }} · si las pruebas convierten +${{ number_format($trialPipeline, 2) }}
                        </p>
                    </div>
                    <span class="text-lg font-bold text-emerald-600">${{ number_format($revenueTotal, 2) }}</span>
                </div>
                <div class="p-5">
                    @if (array_sum($revenueChart['data']))
                        <div wire:ignore x-data="asistChart(@js($revenueChart))" style="height: 260px">
                            <canvas x-ref="canvas" aria-label="Ingreso mensual estimado por plan" role="img"></canvas>
                        </div>
                    @else
                        <p class="py-24 text-center text-sm text-gray-400">Aún no hay empresas pagando.</p>
                    @endif
                </div>
            </div>

            <div class="asist-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Crecimiento del sistema</h3>
                        <p class="text-xs text-gray-400">Últimos 12 meses</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-600">
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full" style="background:#6366f1"></span> Empresas</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full" style="background:#10b981"></span> Usuarios</span>
                    </div>
                </div>
                <div class="p-5">
                    <div wire:ignore x-data="asistChart(@js($growthChart))" style="height: 260px">
                        <canvas x-ref="canvas" aria-label="Empresas y usuarios registrados por mes" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pasteles: planes y estado de las empresas --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="asist-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Distribución de planes</h3>
                    <p class="text-xs text-gray-400">Empresas activas, en prueba o con pago vencido</p>
                </div>
                <div class="p-5">
                    @if (array_sum($plansChart['data']))
                        <div wire:ignore x-data="asistChart(@js($plansChart))" style="height: 220px">
                            <canvas x-ref="canvas" aria-label="Empresas por plan" role="img"></canvas>
                        </div>
                    @else
                        <p class="py-16 text-center text-sm text-gray-400">Aún no hay empresas operando.</p>
                    @endif
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($plans as $i => $plan)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                                <p class="flex items-center gap-1.5 text-xs font-medium text-gray-700">
                                    <span class="inline-block h-2 w-2 rounded-full" style="background: {{ \App\Filament\Widgets\PlatformOverview::PLAN_COLORS[$i % 7] }}"></span>
                                    {{ $plan['name'] }}
                                </p>
                                <p class="text-lg font-bold text-indigo-600">{{ $plan['count'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="asist-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Empresas por estado</h3>
                    <p class="text-xs text-gray-400">Todas las empresas registradas</p>
                </div>
                <div class="p-5">
                    @if (array_sum($statusChart['data']))
                        <div wire:ignore x-data="asistChart(@js($statusChart))" style="height: 290px">
                            <canvas x-ref="canvas" aria-label="Empresas por estado" role="img"></canvas>
                        </div>
                    @else
                        <p class="py-16 text-center text-sm text-gray-400">Aún no hay empresas registradas.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Línea: checadas por día --}}
        <div class="asist-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Checadas por día</h3>
                    <p class="text-xs text-gray-400">Todas las empresas · últimos 14 días</p>
                </div>
                <span class="text-lg font-bold text-blue-600">{{ number_format($punchesTotal) }}</span>
            </div>
            <div class="p-5">
                <div wire:ignore x-data="asistChart(@js($punchesChart))" style="height: 220px">
                    <canvas x-ref="canvas" aria-label="Checadas por día" role="img"></canvas>
                </div>
            </div>
        </div>

        {{-- Detalle por empresa --}}
        <div class="asist-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-800">Detalle por empresa</h3>
                <a href="{{ \App\Filament\Resources\Companies\CompanyResource::getUrl() }}" class="text-xs font-medium text-blue-600 hover:underline">
                    Ver las {{ $companiesTotal }} empresas <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/80">
                        <tr>
                            @foreach (['Empresa' => 'left', 'Plan' => 'left', 'Usuarios' => 'center', 'Empleados' => 'center', 'Oficinas' => 'center', 'Suscripción' => 'center', 'Estado' => 'center'] as $heading => $align)
                                <th class="px-4 py-3 text-{{ $align }} text-xs font-medium uppercase tracking-wider text-gray-500">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($companies as $company)
                            <tr class="transition-colors hover:bg-gray-50/80">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ \App\Filament\Resources\Companies\CompanyResource::getUrl('view', ['record' => $company]) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">{{ $company->name }}</a>
                                    <p class="text-xs text-gray-400">{{ $company->code }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    {{ $company->plan->name }}
                                    <span class="text-xs text-gray-400">(${{ number_format($company->estimatedMonthlyPrice(), 2) }}/mes)</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm font-medium">{{ $company->users_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                                    <span class="font-medium">{{ $company->employees_total }}</span>
                                    <span class="text-xs text-gray-400">/ {{ $company->employeeLimit() }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm font-medium">{{ $company->offices_total }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @if ($company->status === CompanyStatus::Trial && $company->trial_ends_at)
                                        <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Prueba</span>
                                        <p class="mt-0.5 text-xs text-gray-400">hasta {{ $company->trial_ends_at->format('d/m/Y') }}</p>
                                    @elseif ($company->status === CompanyStatus::PastDue)
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Vencida</span>
                                        @if ($company->past_due_since)
                                            <p class="mt-0.5 text-xs text-gray-400">desde {{ $company->past_due_since->format('d/m/Y') }}</p>
                                        @endif
                                    @elseif ($company->status === CompanyStatus::Active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">{{ $company->plan->isFree() ? 'Free' : 'Activa' }}</span>
                                        @if (! $company->plan->isFree())
                                            <p class="mt-0.5 text-xs text-gray-400">{{ $company->billing_interval === 'year' ? 'Anual' : 'Mensual' }}</p>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge($company->status) }}">{{ $company->status->label() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Aún no hay empresas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
