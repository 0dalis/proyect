<!DOCTYPE html>
<html lang="es" class="scroll-smooth antialiased selection:bg-brand-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AsistControl — Control de Asistencia Inteligente</title>
    <meta name="description" content="Sistema SaaS de control de asistencia laboral con geolocalización, gestión de turnos, vacaciones y más.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
                            50: '#f0f3ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Pattern Overlay */
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(99, 102, 241, 0.06) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .dark .bg-grid-pattern {
            background-image: radial-gradient(rgba(99, 102, 241, 0.12) 1px, transparent 1px);
        }

        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }

        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.16, 1, 0.3, 1), padding 0.35s ease;
        }
        .faq-item.active .faq-content {
            max-height: 250px;
        }
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }
        .faq-icon {
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body class="bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans transition-colors duration-300">

<!-- ===== NAVBAR ===== -->
<header class="fixed top-0 left-0 right-0 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800/80 z-50 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="#" class="flex items-center gap-2.5 group">
                <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-indigo-700 rounded-lg flex items-center justify-center shadow-lg shadow-brand-500/20 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Asist<span class="text-brand-600 dark:text-brand-400">Control</span></span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="#servicios" class="hover:text-slate-900 dark:hover:text-white transition-colors">Servicios</a>
                <a href="#como-funciona" class="hover:text-slate-900 dark:hover:text-white transition-colors">Cómo funciona</a>
                <a href="#planes" class="hover:text-slate-900 dark:hover:text-white transition-colors">Planes</a>
                <a href="#testimonios" class="hover:text-slate-900 dark:hover:text-white transition-colors">Testimonios</a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                <a href="{{ route('acceso') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-2 transition-colors">
                    Iniciar Sesión
                </a>
                <a href="{{ route('acceso') }}#registro" class="inline-flex items-center justify-center px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg shadow-md shadow-brand-600/30 hover:shadow-brand-500/50 transition-all hover:-translate-y-0.5">
                    Probar Gratis
                </a>
                <!-- Botón Toggle de Tema -->
                <button id="themeToggle" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-all" title="Cambiar tema">
                    <svg id="sunIcon" class="w-4 h-4 text-amber-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg id="moonIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu button -->
            <button id="mobileMenuBtn" class="md:hidden p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path id="menuIconOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path id="menuIconClose" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" class="hidden"/>
                </svg>
            </button>
        </div>

        <!-- Mobile menu -->
        <div id="mobileMenu" class="md:hidden hidden pb-6 pt-2 border-t border-slate-200 dark:border-slate-800/60 space-y-3">
            <a href="#servicios" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Servicios</a>
            <a href="#como-funciona" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Cómo funciona</a>
            <a href="#planes" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Planes</a>
            <a href="#testimonios" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Testimonios</a>
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800/80 flex flex-col gap-2">
                <a href="{{ route('acceso') }}" class="block px-3 py-2 text-center text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 rounded-lg">Acceso</a>
                <a href="{{ route('acceso') }}#registro" class="block px-3 py-2 text-center text-sm font-semibold text-white bg-brand-600 rounded-lg">Comenzar gratis</a>
                <button id="themeToggleMobile" class="block px-3 py-2 text-center text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 rounded-lg">
                    <span id="themeLabelMobile">Modo Oscuro</span>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- ===== HERO ===== -->
<section class="relative pt-32 pb-20 md:pt-40 md:pb-28 overflow-hidden bg-grid-pattern">
    <!-- Glow Background Effects -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-brand-600/10 dark:bg-brand-600/20 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">

            <div class="lg:col-span-7 text-center lg:text-left reveal">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-full text-xs font-medium text-brand-600 dark:text-brand-300 mb-6">
                    <span class="flex h-2 w-2 rounded-full bg-brand-500 animate-pulse"></span>
                    Versión 2.0 — Gestión Inteligente de Personal
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-[1.1]">
                    Gestión de asistencia <br class="hidden sm:inline"/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 via-brand-500 to-indigo-600 dark:from-brand-300 dark:via-brand-400 dark:to-indigo-300">moderna y automatizada</span>
                </h1>

                <p class="mt-6 text-base sm:text-lg text-slate-500 dark:text-slate-400 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Controla marcajes con geolocalización GPS, gestiona turnos rotativos y automatiza reportes de nómina sin depender de checadores físicos.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="{{ route('acceso') }}#registro" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-sm rounded-lg shadow-lg shadow-brand-600/25 transition-all hover:-translate-y-0.5">
                        Empezar prueba gratis ({{ $daysTrial }} días)
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="#servicios" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 font-semibold text-sm rounded-lg transition-all">
                        Ver características
                    </a>
                </div>

                <div class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-900 flex items-center justify-center lg:justify-start gap-6">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 border-2 border-white dark:border-slate-950 flex items-center justify-center text-xs text-brand-600 dark:text-brand-300 font-bold">JD</div>
                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 border-2 border-white dark:border-slate-950 flex items-center justify-center text-xs text-emerald-600 dark:text-emerald-300 font-bold">MR</div>
                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 border-2 border-white dark:border-slate-950 flex items-center justify-center text-xs text-purple-600 dark:text-purple-300 font-bold">AL</div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <strong class="text-slate-700 dark:text-slate-200 font-semibold">+500 empresas</strong> optimizan sus operaciones diariamente.
                    </p>
                </div>
            </div>

            <!-- Previsualización de Dashboard UI -->
            <div class="lg:col-span-5 reveal reveal-delay-1">
                <div class="relative mx-auto max-w-md lg:max-w-none">
                    <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-brand-500 to-indigo-600 opacity-20 blur-xl"></div>
                    <div class="relative bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-2xl">

                        <!-- Header de la ventana -->
                        <div class="px-4 py-3 bg-slate-950/80 border-b border-slate-800/80 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-500/80"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/80"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></div>
                            </div>
                            <span class="text-[11px] font-mono text-slate-500">app.asistcontrol.com/live</span>
                        </div>

                        <!-- Métricas Mockup -->
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3.5 bg-slate-950/50 border border-slate-800/80 rounded-lg">
                                    <span class="text-xs text-slate-400">Personal Activo</span>
                                    <div class="flex items-baseline justify-between mt-1">
                                        <span class="text-xl font-bold text-white">247</span>
                                        <span class="text-[10px] text-emerald-400 bg-emerald-950/50 px-1.5 py-0.5 rounded border border-emerald-800/40">98% Asistencia</span>
                                    </div>
                                </div>
                                <div class="p-3.5 bg-slate-950/50 border border-slate-800/80 rounded-lg">
                                    <span class="text-xs text-slate-400">Puntualidad</span>
                                    <div class="flex items-baseline justify-between mt-1">
                                        <span class="text-xl font-bold text-white">94.2%</span>
                                        <span class="text-[10px] text-brand-300 bg-brand-950/50 px-1.5 py-0.5 rounded border border-brand-800/40">Excelente</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Feed en vivo -->
                            <div class="p-3.5 bg-slate-950/50 border border-slate-800/80 rounded-lg">
                                <span class="text-[11px] font-semibold tracking-wider uppercase text-slate-500 block mb-3">Registros recientes (GPS Validado)</span>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs p-2 bg-slate-900/80 rounded border border-slate-800/50">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-6 h-6 rounded bg-emerald-500/10 text-emerald-400 font-mono font-bold text-[10px] flex items-center justify-center">ML</div>
                                            <span class="text-slate-300 font-medium">María López</span>
                                        </div>
                                        <span class="font-mono text-emerald-400 text-[11px]">08:00:12 AM</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs p-2 bg-slate-900/80 rounded border border-slate-800/50">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-6 h-6 rounded bg-brand-500/10 text-brand-400 font-mono font-bold text-[10px] flex items-center justify-center">CR</div>
                                            <span class="text-slate-300 font-medium">Carlos Ruiz</span>
                                        </div>
                                        <span class="font-mono text-brand-400 text-[11px]">08:01:45 AM</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs p-2 bg-slate-900/80 rounded border border-slate-800/50">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-6 h-6 rounded bg-amber-500/10 text-amber-400 font-mono font-bold text-[10px] flex items-center justify-center">AG</div>
                                            <span class="text-slate-300 font-medium">Ana García</span>
                                        </div>
                                        <span class="font-mono text-amber-400 text-[11px]">08:15:02 AM (Retardo)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===== METRICAS NUMÉRICAS ===== -->
<section class="py-12 border-y border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-900/40 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div class="reveal">
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono" data-target="500" data-suffix="+">0+</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Empresas activas</p>
            </div>
            <div class="reveal reveal-delay-1">
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono" data-target="12000" data-suffix="+">0+</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Empleados monitoreados</p>
            </div>
            <div class="reveal reveal-delay-2">
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono" data-target="1000" data-suffix="K+">0K+</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Marcajes procesados</p>
            </div>
            <div class="reveal reveal-delay-3">
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono" data-target="99.9" data-suffix="%" data-decimal="true">0%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Disponibilidad del servicio</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== SERVICIOS / BENTO GRID ===== -->
<section id="servicios" class="py-24 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-xs font-semibold text-brand-600 dark:text-brand-400 tracking-widest uppercase reveal">Funcionalidades Principales</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-2 reveal reveal-delay-1">
                Todo lo que necesitas para un control eficiente
            </p>
            <p class="text-slate-500 dark:text-slate-400 text-sm sm:text-base mt-4 reveal reveal-delay-2">
                Elimina fraudes de asistencia, automatiza incidencias y simplifica los reportes de nómina en minutos.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Card 1 -->
            <div class="p-6 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800/80 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 reveal">
                <div class="w-10 h-10 rounded-lg bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Geolocalización GPS</h3>
                <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm leading-relaxed">
                    Valida marcajes dentro del rango geográfico permitido. Define radio de tolerancia por sucursal para evitar registros fuera de zona.
                </p>
            </div>

            <!-- Card 2 -->
            <div class="p-6 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800/80 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 reveal reveal-delay-1">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Turnos Inteligentes</h3>
                <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm leading-relaxed">
                    Configura horarios flexibles o rotativos. El sistema calcula automáticamente retardos, horas extra y salidas antes de tiempo.
                </p>
            </div>

            <!-- Card 3 -->
            <div class="p-6 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800/80 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 reveal reveal-delay-2">
                <div class="w-10 h-10 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Permisos e Incidencias</h3>
                <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm leading-relaxed">
                    Permite a los colaboradores solicitar vacaciones o enviar justificantes con fotografías de incapacidades directamente desde la App.
                </p>
            </div>

            <!-- Card 4 -->
            <div class="p-6 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800/80 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 reveal">
                <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Reportes Exportables</h3>
                <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm leading-relaxed">
                    Genera pre-nóminas consolidadas en formato Excel o CSV listos para ser importados en tu sistema contable.
                </p>
            </div>

            <!-- Card 5 -->
            <div class="p-6 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800/80 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 reveal reveal-delay-1">
                <div class="w-10 h-10 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Notificaciones Push</h3>
                <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm leading-relaxed">
                    Comunica avisos organizacionales a todo el personal o grupos específicos en tiempo real a través de la aplicación móvil.
                </p>
            </div>

            <!-- Card 6 -->
            <div class="p-6 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800/80 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 reveal reveal-delay-2">
                <div class="w-10 h-10 rounded-lg bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Modo Kiosco y PIN</h3>
                <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm leading-relaxed">
                    Convierte cualquier tableta en una estación fija de recepción para marcaje rápido mediante código de empleado único.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ===== CÓMO FUNCIONA ===== -->
<section id="como-funciona" class="py-24 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-200 dark:border-slate-800/80 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-xs font-semibold text-brand-600 dark:text-brand-400 tracking-widest uppercase reveal">Implementación Ágil</h2>
            <p class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-2 reveal reveal-delay-1">
                Paso a paso sin complicaciones
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="reveal">
                <div class="font-mono text-2xl font-bold text-brand-600 dark:text-brand-400 mb-2">01</div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Crea tu cuenta</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Regístrate en menos de 2 minutos sin ingresar tarjetas de crédito.</p>
            </div>

            <div class="reveal reveal-delay-1">
                <div class="font-mono text-2xl font-bold text-brand-600 dark:text-brand-400 mb-2">02</div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Define Sucursales</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Establece ubicaciones GPS, límites de geocerca y tolerancias de entrada.</p>
            </div>

            <div class="reveal reveal-delay-2">
                <div class="font-mono text-2xl font-bold text-brand-600 dark:text-brand-400 mb-2">03</div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Alta de Empleados</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Importa la lista de colaboradores e invítalos a descargar la App móvil.</p>
            </div>

            <div class="reveal reveal-delay-3">
                <div class="font-mono text-2xl font-bold text-brand-600 dark:text-brand-400 mb-2">04</div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Control Total</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Monitorea en tiempo real las asistencias e incidencias operativas.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== PLANES ===== -->
<section id="planes" class="py-24 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-xs font-semibold text-brand-600 dark:text-brand-400 tracking-widest uppercase reveal">Precios Transparentes</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-2 reveal reveal-delay-1">
                Planes adaptados a tu escala
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto items-stretch">
            @if(isset($planes) && $planes->isNotEmpty())
                @foreach($planes as $plan)
                    <div class="reveal reveal-delay-{{ $loop->index + 1 }} relative bg-white dark:bg-slate-900/60 border {{ $loop->first ? 'border-brand-500 shadow-xl shadow-brand-500/10' : 'border-slate-200 dark:border-slate-800' }} rounded-xl p-8 flex flex-col justify-between transition-colors duration-300">
                        @if($loop->first)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-brand-600 text-white text-[10px] uppercase tracking-wider font-bold rounded-full">
                                Recomendado
                            </div>
                        @endif

                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $plan->nombre }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $plan->tipo }}</p>

                            <div class="my-6">
                                <span class="text-4xl font-extrabold font-mono text-slate-900 dark:text-white">${{ number_format($plan->precio, 2) }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">/ mes</span>
                                @if($plan->iva > 0)
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">+ IVA ({{ $plan->iva }}%)</p>
                                @endif
                            </div>

                            <ul class="space-y-3 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800/80 pt-6 mb-8">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Hasta {{ $plan->max_users ?? 'ilimitados' }} usuarios
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Geolocalización GPS
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    App iOS y Android
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Soporte técnico
                                </li>
                            </ul>
                        </div>

                        <a href="{{ route('acceso') }}#registro" class="w-full py-2.5 text-center font-semibold text-xs rounded-lg transition-all {{ $loop->first ? 'bg-brand-600 hover:bg-brand-500 text-white shadow-md' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }}">
                            Seleccionar Plan
                        </a>
                    </div>
                @endforeach
            @else
                <!-- Fallback si no hay planes cargados -->
                <div class="col-span-3 text-center py-12 bg-slate-50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-slate-800">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Consulta con nuestro equipo comercial para un plan a la medida.</p>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- ===== TESTIMONIOS ===== -->
<section id="testimonios" class="py-24 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-200 dark:border-slate-800/80 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-xs font-semibold text-brand-600 dark:text-brand-400 tracking-widest uppercase reveal">Casos de Éxito</h2>
            <p class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-2 reveal reveal-delay-1">
                Respaldado por equipos de alto rendimiento
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="p-6 bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 rounded-xl reveal transition-colors duration-300">
                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed italic">"Digitalizar la asistencia redujo en un 90% el tiempo que destinábamos a la revisión manual de retardos antes de procesar la nómina."</p>
                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800/60 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-brand-500/20 text-brand-600 dark:text-brand-300 font-bold text-xs flex items-center justify-center">CG</div>
                    <div>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Carlos García</p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500">Director RH, LogiTrans</p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 rounded-xl reveal reveal-delay-1 transition-colors duration-300">
                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed italic">"El control de geolocalización fue clave para coordinar a nuestro equipo de campo distribuido en múltiples puntos de la ciudad."</p>
                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800/60 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 font-bold text-xs flex items-center justify-center">MR</div>
                    <div>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Mariana Ruiz</p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500">Operaciones, Retail Express</p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 rounded-xl reveal reveal-delay-2 transition-colors duration-300">
                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed italic">"La interfaz es impecable y la adopción por parte del personal fue inmediata. Sin necesidad de capacitaciones complejas."</p>
                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800/60 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-purple-500/20 text-purple-600 dark:text-purple-300 font-bold text-xs flex items-center justify-center">AM</div>
                    <div>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Alejandro Méndez</p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500">TechSolutions MX</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FAQ ===== -->
<section class="py-24 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-xs font-semibold text-brand-600 dark:text-brand-400 tracking-widest uppercase reveal">Dudas Comunes</h2>
            <p class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-2 reveal reveal-delay-1">Preguntas Frecuentes</p>
        </div>

        <div class="space-y-3">
            @php $faqs = [
                ['q' => '¿Requiere instalación de equipo especial?', 'a' => 'No. Funciona 100% en la nube desde cualquier navegador web y mediante aplicaciones móviles nativas para iOS y Android.'],
                ['q' => '¿Permite marcajes sin conexión a internet?', 'a' => 'Sí. La App almacena los registros de forma local con la hora y ubicación cifradas, sincronizándolos en cuanto detecte conexión.'],
                ['q' => '¿Se adapta a horarios rotativos?', 'a' => 'Completamente. Puedes definir múltiples turnos, tolerancias de entrada y reglas de descansos según la estructura de tu empresa.'],
            ]; @endphp

            @foreach($faqs as $index => $faq)
                <div class="faq-item reveal bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden transition-colors duration-300">
                    <button class="faq-toggle w-full flex items-center justify-between px-5 py-4 text-left">
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $faq['q'] }}</span>
                        <svg class="faq-icon w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content">
                        <p class="px-5 pb-4 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-900 py-12 text-xs text-slate-400 dark:text-slate-500 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-brand-600 rounded flex items-center justify-center text-white font-bold text-xs">A</div>
            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">AsistControl</span>
        </div>
        <p>&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
        <div class="flex gap-6">
            <a href="{{ route('privacidad') }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Privacidad</a>
            <a href="{{ route('terminos') }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Términos</a>
        </div>
    </div>
</footer>

<script>
// ===== MODO CLARO / OSCURO =====
const $html = $('html');
const $sunIcon = $('#sunIcon');
const $moonIcon = $('#moonIcon');
const $themeLabelMobile = $('#themeLabelMobile');

function applyTheme(isDark) {
    if (isDark) {
        $html.addClass('dark');
        $sunIcon.removeClass('hidden');
        $moonIcon.addClass('hidden');
        $themeLabelMobile.text('Modo Claro');
        localStorage.setItem('theme', 'dark');
    } else {
        $html.removeClass('dark');
        $sunIcon.addClass('hidden');
        $moonIcon.removeClass('hidden');
        $themeLabelMobile.text('Modo Oscuro');
        localStorage.setItem('theme', 'light');
    }
}

const savedTheme = localStorage.getItem('theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
    applyTheme(true);
} else {
    applyTheme(false);
}

$('#themeToggle').on('click', function() {
    applyTheme(!$html.hasClass('dark'));
});

$('#themeToggleMobile').on('click', function() {
    applyTheme(!$html.hasClass('dark'));
});

// ===== MOBILE MENU =====
$('#mobileMenuBtn').on('click', function() {
    $('#mobileMenu').toggleClass('hidden');
    $('#menuIconOpen').toggleClass('hidden');
    $('#menuIconClose').toggleClass('hidden');
});

// ===== SCROLL REVEAL =====
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            if ($(entry.target).find('[data-target]').length) {
                animateCounters(entry.target);
            }
        }
    });
}, { threshold: 0.1 });

$('.reveal').each(function() {
    observer.observe(this);
});

// ===== COUNTER ANIMATION =====
function animateCounters(container) {
    const $counters = $(container).find('[data-target]');
    $counters.each(function() {
        const $counter = $(this);
        if ($counter.data('animated')) return;
        $counter.data('animated', true);
        const target = parseFloat($counter.data('target'));
        const suffix = $counter.data('suffix') || '';
        const decimal = $counter.data('decimal') === true;
        const duration = 1500;
        const start = performance.now();

        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;
            $counter.text(decimal ? current.toFixed(1) + suffix : Math.floor(current) + suffix);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    });
}

// ===== FAQ TOGGLE =====
$('.faq-toggle').on('click', function() {
    const $item = $(this).parent();
    const wasActive = $item.hasClass('active');
    $('.faq-item').removeClass('active');
    if (!wasActive) $item.addClass('active');
});
</script>
</body>
</html>
