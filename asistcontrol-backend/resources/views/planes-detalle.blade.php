<!DOCTYPE html>
<html lang="es" class="scroll-smooth antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planes y Precios — AsistControl</title>
    <meta name="description" content="Compare planes de AsistControl y elija la solución de control de asistencia ideal para su empresa.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f3ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                            400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                            800: '#3730a3', 900: '#312e81', 950: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans transition-colors duration-300">

@include('partials.public-navbar')

<!-- ===== HERO ===== -->
<section class="pt-28 pb-12 border-b border-slate-200 dark:border-slate-800/80">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Planes y precios
        </h1>
        <p class="mt-3 text-sm sm:text-base text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
            Seleccione el plan que mejor se adapte a la escala de su operación. Todos los planes incluyen acceso a la aplicación móvil y soporte técnico.
        </p>
    </div>
</section>

<!-- ===== TABLA COMPARATIVA ===== -->
<section class="py-16 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(isset($planes) && $planes->isNotEmpty())
            <!-- Desktop: Tabla comparativa -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full border-separate border-spacing-0">
                    <thead>
                        <tr>
                            <th class="sticky left-0 bg-white dark:bg-slate-950 z-10 p-4 border-b border-slate-200 dark:border-slate-800 text-left">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Funcionalidad</span>
                            </th>
                            @foreach($planes as $plan)
                                <th class="p-4 border-b border-slate-200 dark:border-slate-800 text-center min-w-[200px] {{ $loop->index === 2 ? 'bg-brand-50 dark:bg-brand-950/20' : '' }}">
                                    <div class="inline-flex flex-col items-center">
                                        @if($loop->index === 2)
                                            <span class="text-[10px] font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400 mb-1">Recomendado</span>
                                        @endif
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $plan->nombre }}</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $plan->tipo }}</span>
                                        <div class="mt-2">
                                            <span class="text-xl font-extrabold font-mono text-slate-900 dark:text-white">${{ number_format($plan->precio, 2) }}</span>
                                            <span class="text-[11px] text-slate-500 dark:text-slate-400">/mes</span>
                                        </div>
                                        @if($plan->iva > 0)
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500">+ IVA {{ $plan->iva }}%</span>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="text-xs">
                        @php
                            $allFeatures = [];
                            foreach($planes as $plan) {
                                if(is_array($plan->caracteristicas)) {
                                    foreach($plan->caracteristicas as $feature) {
                                        $key = is_array($feature) ? ($feature['name'] ?? '') : $feature;
                                        if(!in_array($key, $allFeatures)) $allFeatures[] = $key;
                                    }
                                }
                            }
                        @endphp

                        {{-- Usuarios --}}
                        <tr class="border-b border-slate-100 dark:border-slate-800/60">
                            <td class="sticky left-0 bg-white dark:bg-slate-950 z-10 p-4 font-medium text-slate-700 dark:text-slate-300">Usuarios incluidos</td>
                            @foreach($planes as $plan)
                                <td class="p-4 text-center {{ $loop->index === 2 ? 'bg-brand-50/50 dark:bg-brand-950/10' : '' }}">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $plan->max_users ?? 'Ilimitados' }}</span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Funcionalidades --}}
                        @foreach($allFeatures as $featureName)
                            <tr class="border-b border-slate-100 dark:border-slate-800/60">
                                <td class="sticky left-0 bg-white dark:bg-slate-950 z-10 p-4 text-slate-600 dark:text-slate-400">{{ $featureName }}</td>
                                @foreach($planes as $plan)
                                    @php
                                        $hasFeature = false;
                                        if(is_array($plan->caracteristicas)) {
                                            foreach($plan->caracteristicas as $f) {
                                                $name = is_array($f) ? ($f['name'] ?? '') : $f;
                                                if($name === $featureName) { $hasFeature = true; break; }
                                            }
                                        }
                                    @endphp
                                    <td class="p-4 text-center {{ $loop->index === 2 ? 'bg-brand-50/50 dark:bg-brand-950/10' : '' }}">
                                        @if($hasFeature)
                                            <svg class="w-4 h-4 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <svg class="w-4 h-4 text-slate-300 dark:text-slate-700 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Soporte --}}
                        <tr class="border-b border-slate-100 dark:border-slate-800/60">
                            <td class="sticky left-0 bg-white dark:bg-slate-950 z-10 p-4 font-medium text-slate-700 dark:text-slate-300">Soporte técnico</td>
                            @foreach($planes as $plan)
                                <td class="p-4 text-center {{ $loop->index === 2 ? 'bg-brand-50/50 dark:bg-brand-950/10' : '' }}">
                                    <span class="text-slate-600 dark:text-slate-400">
                                        @if($loop->index === 0) Correo electrónico
                                        @elseif($loop->index === 1) Prioritario por chat
                                        @else Dedicado 24/7
                                        @endif
                                    </span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- CTA --}}
                        <tr>
                            <td class="sticky left-0 bg-white dark:bg-slate-950 z-10 p-4"></td>
                            @foreach($planes as $plan)
                                <td class="p-4 text-center {{ $loop->index === 2 ? 'bg-brand-50/50 dark:bg-brand-950/10' : '' }}">
                                    <a href="{{ route('acceso') }}#registro"
                                       class="inline-flex items-center justify-center px-5 py-2 {{ $loop->index === 2 ? 'bg-brand-600 hover:bg-brand-500 text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }} text-xs font-semibold rounded-lg transition-colors">
                                        Seleccionar
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile: Cards apiladas -->
            <div class="lg:hidden space-y-6">
                @foreach($planes as $plan)
                    <div class="bg-white dark:bg-slate-900/40 border {{ $loop->index === 2 ? 'border-brand-500 ring-1 ring-brand-500/30' : 'border-slate-200 dark:border-slate-800' }} rounded-xl p-6">
                        @if($loop->index === 2)
                            <span class="inline-block text-[10px] font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400 mb-3">Plan Recomendado</span>
                        @endif
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $plan->nombre }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $plan->tipo }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xl font-extrabold font-mono text-slate-900 dark:text-white">${{ number_format($plan->precio, 2) }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 block">/mes</span>
                            </div>
                        </div>

                        <div class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-4">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600 dark:text-slate-400">Usuarios</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $plan->max_users ?? 'Ilimitados' }}</span>
                            </div>
                            @if(is_array($plan->caracteristicas))
                                @foreach($plan->caracteristicas as $feature)
                                    <div class="flex items-center gap-2 text-xs">
                                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-slate-600 dark:text-slate-400">{{ is_array($feature) ? ($feature['name'] ?? '') : $feature }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <a href="{{ route('acceso') }}#registro"
                           class="block w-full mt-5 py-2.5 text-center {{ $loop->index === 2 ? 'bg-brand-600 hover:bg-brand-500 text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }} font-semibold text-xs rounded-lg transition-colors">
                            Seleccionar {{ $plan->nombre }}
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16">
                <p class="text-sm text-slate-500 dark:text-slate-400">Consulte con nuestro equipo comercial para un plan a la medida de su empresa.</p>
                <a href="{{ route('landing') }}#contacto" class="inline-block mt-4 px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition-colors">Contactar a Ventas</a>
            </div>
        @endif
    </div>
</section>

<!-- ===== FAQ PLANES ===== -->
<section class="py-20 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-200 dark:border-slate-800/80 transition-colors duration-300">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-10">Preguntas frecuentes sobre planes</h2>

        <div class="space-y-3">
            @php $faqs = [
                ['q' => '¿Puedo cambiar de plan en cualquier momento?', 'a' => 'Sí. Puede actualizar o degradar su plan directamente desde el panel de administración. Los cambios se aplican de inmediato y la facturación se ajusta proporcionalmente.'],
                ['q' => '¿Hay un periodo de prueba gratuito?', 'a' => 'Todos los planes incluyen un periodo de prueba de '.($daysTrial ?? 14).' días sin costo. No se requiere tarjeta de crédito para comenzar.'],
                ['q' => '¿Qué métodos de pago aceptan?', 'a' => 'Aceptamos transferencias bancarias, tarjetas de crédito y débito (Visa, Mastercard), y pagos recurrentes automatizados.'],
                ['q' => '¿El precio incluye actualizaciones?', 'a' => 'Sí. Todas las actualizaciones de funcionalidades y parches de seguridad están incluidas en su suscripción mensual sin costo adicional.'],
                ['q' => '¿Ofrecen planes personalizados para empresas grandes?', 'a' => 'Sí. Para organizaciones con más de 500 empleados, ofrecemos planes empresariales con características y soporte personalizados. Contacte a nuestro equipo de ventas.'],
                ['q' => '¿Hay contratos de permanencia?', 'a' => 'No. Todos nuestros planes son de suscripción mensual sin contratos de permanencia. Puede cancelar en cualquier momento.'],
            ]; @endphp

            @foreach($faqs as $faq)
                <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                    <button onclick="this.parentElement.classList.toggle('active')" class="w-full flex items-center justify-between px-5 py-4 text-left">
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $faq['q'] }}</span>
                        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200" style="transform: ;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="overflow-hidden transition-all duration-300" style="max-height: 0;">
                        <p class="px-5 pb-4 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="py-20 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Comience su prueba gratuita hoy</h2>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Sin compromisos. Configure su empresa en minutos y descubra cómo AsistControl puede transformar su gestión de personal.</p>
        <div class="mt-8">
            <a href="{{ route('acceso') }}#registro" class="inline-flex items-center justify-center px-8 py-3 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-sm rounded-lg transition-colors">
                Crear cuenta gratuita
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-900 py-10 text-xs text-slate-400 dark:text-slate-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
        <a href="{{ route('landing') }}" class="flex items-center gap-2">
            <div class="w-6 h-6 bg-brand-600 rounded flex items-center justify-center text-white font-bold text-xs">A</div>
            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">AsistControl</span>
        </a>
        <p>&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
        <div class="flex gap-6">
            <a href="{{ route('privacidad') }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Privacidad</a>
            <a href="{{ route('terminos') }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Términos</a>
        </div>
    </div>
</footer>

<style>
.faq-item.active .faq-content, .bg-white.active > div:last-child { max-height: 200px !important; }
.bg-white.active svg { transform: rotate(180deg); }
</style>
</body>
</html>
