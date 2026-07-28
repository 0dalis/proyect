<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AsistControl — Control de Asistencia Inteligente</title>
    <meta name="description" content="Sistema SaaS de control de asistencia laboral con geolocalización, gestión de turnos, vacaciones y más.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                            950: '#172554',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }
        .reveal-delay-5 { transition-delay: 0.5s; }
        .reveal-delay-6 { transition-delay: 0.6s; }
        .counter {
            transition: all 1.5s ease-out;
        }
        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out, padding 0.4s ease-in-out;
        }
        .faq-item.active .faq-content {
            max-height: 300px;
        }
        .faq-item.active .faq-icon {
            transform: rotate(45deg);
        }
        .faq-icon {
            transition: transform 0.3s ease;
        }
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #2563eb;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
    </style>
</head>
<body class="bg-white text-gray-900 font-sans antialiased">

<!-- ===== NAVBAR ===== -->
<nav class="fixed top-0 left-0 right-0 bg-white/95 backdrop-blur border-b border-gray-100 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="#" class="flex items-center gap-2">
                <div class="w-9 h-9 bg-brand-600 rounded-xl flex items-center justify-center shadow-lg shadow-brand-200">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900 tracking-tight">AsistControl</span>
            </a>
            <div class="hidden md:flex items-center gap-8">
                <a href="#servicios" class="nav-link text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">Servicios</a>
                <a href="#como-funciona" class="nav-link text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">Cómo funciona</a>
                <a href="#planes" class="nav-link text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">Planes</a>
                <a href="#testimonios" class="nav-link text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">Testimonios</a>
                <a href="{{ route('acceso') }}" class="inline-flex items-center px-4 py-2 border border-brand-600 text-brand-600 text-sm font-semibold rounded-xl hover:bg-brand-50 transition-colors">
                    Acceso
                </a>
                <a href="{{ route('acceso') }}#registro" class="inline-flex items-center px-5 py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-xl hover:bg-brand-700 transition-all shadow-lg shadow-brand-200 hover:shadow-xl hover:shadow-brand-300 hover:-translate-y-0.5">
                    Comenzar gratis
                </a>
            </div>
            <!-- Mobile menu button -->
            <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path id="menuIconOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path id="menuIconClose" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" class="hidden"/>
                </svg>
            </button>
        </div>
        <!-- Mobile menu -->
        <div id="mobileMenu" class="md:hidden hidden pb-4 space-y-2">
            <a href="#servicios" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-brand-600 transition-colors">Servicios</a>
            <a href="#como-funciona" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-brand-600 transition-colors">Cómo funciona</a>
            <a href="#planes" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-brand-600 transition-colors">Planes</a>
            <a href="#testimonios" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-brand-600 transition-colors">Testimonios</a>
            <a href="{{ route('acceso') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-brand-600 hover:bg-brand-50 transition-colors">Acceso</a>
            <a href="{{ route('acceso') }}#registro" class="block px-3 py-3 rounded-xl text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 transition-colors text-center">Comenzar gratis</a>
        </div>
    </div>
</nav>

<!-- ===== HERO ===== -->
<section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
    <div class="max-w-7xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="reveal">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-brand-50 text-brand-700 text-xs font-semibold rounded-full mb-6 ring-1 ring-brand-100">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                    </span>
                    Prueba gratis por 14 días
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-gray-900 leading-[1.1] tracking-tight">
                    Controla la asistencia
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 to-brand-500">sin complicaciones</span>
                </h1>
                <p class="mt-6 text-lg text-gray-500 leading-relaxed max-w-xl">
                    Digitaliza las entradas y salidas de tu equipo con geolocalización, turnos inteligentes, vacaciones y reportes en tiempo real desde cualquier dispositivo.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('acceso') }}#registro" class="group inline-flex items-center justify-center px-6 py-3.5 bg-brand-600 text-white font-semibold rounded-xl hover:bg-brand-700 transition-all shadow-lg shadow-brand-200 hover:shadow-xl hover:shadow-brand-300 hover:-translate-y-0.5">
                        Probar gratis 14 días
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="#servicios" class="inline-flex items-center justify-center px-6 py-3.5 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all">
                        Explorar servicios
                    </a>
                </div>
                <div class="mt-8 flex items-center gap-8">
                    <div class="flex -space-x-3">
                        <div class="w-9 h-9 bg-emerald-500 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold shadow">✓</div>
                        <div class="w-9 h-9 bg-brand-500 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold shadow">✓</div>
                        <div class="w-9 h-9 bg-purple-500 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold shadow">✓</div>
                        <div class="w-9 h-9 bg-amber-500 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold shadow">✓</div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">+500 empresas</p>
                        <p class="text-xs text-gray-400">confían en nosotros</p>
                    </div>
                </div>
            </div>
            <div class="reveal reveal-delay-1 hidden lg:block">
                <div class="relative">
                    <div class="absolute -inset-4 bg-gradient-to-br from-brand-400/20 via-brand-300/10 to-transparent rounded-3xl blur-2xl"></div>
                    <div class="relative bg-white border border-gray-200/80 rounded-2xl shadow-2xl shadow-gray-200/50 p-1">
                        <div class="bg-gradient-to-br from-brand-600 to-brand-700 rounded-t-xl p-4 flex items-center gap-3">
                            <div class="w-3 h-3 bg-red-400/80 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-400/80 rounded-full"></div>
                            <div class="w-3 h-3 bg-emerald-400/80 rounded-full"></div>
                            <span class="text-xs text-white/80 ml-2 font-medium">Dashboard — Panel de control</span>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gradient-to-br from-brand-50 to-blue-50 rounded-xl p-4 border border-brand-100/50">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold text-brand-700">Activos hoy</p>
                                        <span class="text-emerald-600 text-xs font-bold bg-emerald-50 px-1.5 py-0.5 rounded">+12%</span>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900 mt-2">247</p>
                                </div>
                                <div class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-100/50">
                                    <p class="text-xs font-semibold text-emerald-700">A tiempo</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-2">94%</p>
                                    <div class="mt-2 w-full bg-emerald-100 rounded-full h-1.5">
                                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width:94%"></div>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-100/50">
                                    <p class="text-xs font-semibold text-amber-700">Retardos</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-2">8</p>
                                </div>
                                <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-4 border border-purple-100/50">
                                    <p class="text-xs font-semibold text-purple-700">Solicitudes</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-2">5</p>
                                    <span class="text-xs text-purple-500 font-medium">3 pendientes</span>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Últimos marcajes</span>
                                    <span class="text-xs text-brand-600 font-medium cursor-pointer hover:underline">Ver todo</span>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between text-sm bg-white rounded-lg p-2.5 border border-gray-100">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 text-xs font-bold">ML</div>
                                            <span class="font-medium text-gray-700">María López</span>
                                        </div>
                                        <span class="text-emerald-600 text-xs font-semibold bg-emerald-50 px-2 py-0.5 rounded">08:00</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm bg-white rounded-lg p-2.5 border border-gray-100">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-brand-100 rounded-lg flex items-center justify-center text-brand-600 text-xs font-bold">CR</div>
                                            <span class="font-medium text-gray-700">Carlos Ruiz</span>
                                        </div>
                                        <span class="text-emerald-600 text-xs font-semibold bg-emerald-50 px-2 py-0.5 rounded">08:01</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm bg-white rounded-lg p-2.5 border border-gray-100">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-red-100 rounded-lg flex items-center justify-center text-red-600 text-xs font-bold">AG</div>
                                            <span class="font-medium text-gray-700">Ana García</span>
                                        </div>
                                        <span class="text-red-600 text-xs font-semibold bg-red-50 px-2 py-0.5 rounded">08:22</span>
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

<!-- ===== ESTADÍSTICAS ===== -->
<section class="py-16 bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
            <div class="reveal">
                <div class="text-4xl sm:text-5xl font-black text-white tabular-nums" data-target="500" data-suffix="+">0+</div>
                <p class="mt-2 text-brand-200 text-sm font-medium">Empresas activas</p>
            </div>
            <div class="reveal reveal-delay-1">
                <div class="text-4xl sm:text-5xl font-black text-white tabular-nums" data-target="12000" data-suffix="+">0+</div>
                <p class="mt-2 text-brand-200 text-sm font-medium">Empleados registrados</p>
            </div>
            <div class="reveal reveal-delay-2">
                <div class="text-4xl sm:text-5xl font-black text-white tabular-nums" data-target="1000" data-suffix="K+">0K+</div>
                <p class="mt-2 text-brand-200 text-sm font-medium">Marcajes procesados</p>
            </div>
            <div class="reveal reveal-delay-3">
                <div class="text-4xl sm:text-5xl font-black text-white" data-target="99.9" data-suffix="%" data-decimal="true">0%</div>
                <p class="mt-2 text-brand-200 text-sm font-medium">Uptime garantizado</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== SERVICIOS ===== -->
<section id="servicios" class="py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <span class="text-brand-600 text-sm font-bold uppercase tracking-wider reveal">Servicios</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-black text-gray-900 reveal reveal-delay-1">
                Todo lo que necesitas para tu equipo
            </h2>
            <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto reveal reveal-delay-2">
                Centraliza el control de asistencia, turnos y permisos en un solo lugar.
            </p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Servicio 1 -->
            <div class="reveal reveal-delay-1 group bg-white rounded-2xl p-8 border border-gray-100 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Geolocalización GPS</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Valida que tus empleados registren asistencia desde la ubicación de la oficina. Define radios de tolerancia por sucursal y evita marcajes fraudulentos.
                </p>
            </div>

            <!-- Servicio 2 -->
            <div class="reveal reveal-delay-2 group bg-white rounded-2xl p-8 border border-gray-100 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Turnos inteligentes</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Define horarios de entrada, salida, tolerancias y comida por sucursal. El sistema calcula automáticamente retardos, horas extra y salidas anticipadas.
                </p>
            </div>

            <!-- Servicio 3 -->
            <div class="reveal reveal-delay-3 group bg-white rounded-2xl p-8 border border-gray-100 hover:border-purple-200 hover:shadow-xl hover:shadow-purple-50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Solicitudes y permisos</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Tus empleados solicitan vacaciones, permisos o justifican inasistencias desde la app. Los supervisores aprueban o rechazan con un clic.
                </p>
            </div>

            <!-- Servicio 4 -->
            <div class="reveal reveal-delay-1 group bg-white rounded-2xl p-8 border border-gray-100 hover:border-amber-200 hover:shadow-xl hover:shadow-amber-50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Reportes y dashboard</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Visualiza asistencia diaria, retardos y horas trabajadas en tiempo real. Exporta reportes detallados a Excel para nómina y RH.
                </p>
            </div>

            <!-- Servicio 5 -->
            <div class="reveal reveal-delay-2 group bg-white rounded-2xl p-8 border border-gray-100 hover:border-rose-200 hover:shadow-xl hover:shadow-rose-50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Notificaciones push</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Envía avisos a toda la empresa, por área o a empleados específicos directamente a la app móvil desde el panel de administración.
                </p>
            </div>

            <!-- Servicio 6 -->
            <div class="reveal reveal-delay-3 group bg-white rounded-2xl p-8 border border-gray-100 hover:border-cyan-200 hover:shadow-xl hover:shadow-cyan-50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Kiosco y PIN seguro</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Modo kiosco para que los empleados marquen entrada y salida con su código o PIN desde una tableta en recepción.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ===== CÓMO FUNCIONA ===== -->
<section id="como-funciona" class="py-24 bg-gray-50 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <span class="text-brand-600 text-sm font-bold uppercase tracking-wider reveal">Cómo funciona</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-black text-gray-900 reveal reveal-delay-1">
                Empieza en 4 pasos
            </h2>
            <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto reveal reveal-delay-2">
                Configura tu empresa en minutos, sin instalaciones complicadas.
            </p>
        </div>
        <div class="relative">
            <!-- Timeline line -->
            <div class="hidden lg:block absolute top-1/2 left-0 right-0 h-0.5 bg-gray-200 -translate-y-1/2 z-0"></div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 relative z-10">
                <!-- Paso 1 -->
                <div class="reveal reveal-delay-1 text-center">
                    <div class="w-16 h-16 bg-white rounded-2xl shadow-lg border border-gray-200 flex items-center justify-center mx-auto mb-5 group hover:border-brand-300 hover:shadow-xl transition-all duration-300">
                        <span class="text-2xl font-black text-brand-600">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Registra tu empresa</h3>
                    <p class="text-gray-500 text-sm">Crea tu cuenta en 2 minutos. Elige tu plan y obtén 14 días de prueba gratis.</p>
                </div>

                <!-- Paso 2 -->
                <div class="reveal reveal-delay-2 text-center">
                    <div class="w-16 h-16 bg-white rounded-2xl shadow-lg border border-gray-200 flex items-center justify-center mx-auto mb-5 group hover:border-brand-300 hover:shadow-xl transition-all duration-300">
                        <span class="text-2xl font-black text-brand-600">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Configura sucursales</h3>
                    <p class="text-gray-500 text-sm">Define oficinas, áreas y turnos. Ajusta geocercas y tolerancias de horario.</p>
                </div>

                <!-- Paso 3 -->
                <div class="reveal reveal-delay-3 text-center">
                    <div class="w-16 h-16 bg-white rounded-2xl shadow-lg border border-gray-200 flex items-center justify-center mx-auto mb-5 group hover:border-brand-300 hover:shadow-xl transition-all duration-300">
                        <span class="text-2xl font-black text-brand-600">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Invita a tu equipo</h3>
                    <p class="text-gray-500 text-sm">Registra empleados, asigna roles y comparte el código de empresa para la app móvil.</p>
                </div>

                <!-- Paso 4 -->
                <div class="reveal reveal-delay-4 text-center">
                    <div class="w-16 h-16 bg-white rounded-2xl shadow-lg border border-gray-200 flex items-center justify-center mx-auto mb-5 group hover:border-brand-300 hover:shadow-xl transition-all duration-300">
                        <span class="text-2xl font-black text-brand-600">4</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Monitorea en vivo</h3>
                    <p class="text-gray-500 text-sm">Visualiza asistencias, retardos y reportes desde el dashboard en tiempo real.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== PLANES ===== -->
<section id="planes" class="py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <span class="text-brand-600 text-sm font-bold uppercase tracking-wider reveal">Planes</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-black text-gray-900 reveal reveal-delay-1">
                Elige tu plan
            </h2>
            <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto reveal reveal-delay-2">
                Todos incluyen 14 días de prueba gratis. Sin tarjeta de crédito. Cancela cuando quieras.
            </p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
            @if($planes->isEmpty())
                <div class="lg:col-span-3 text-center py-16">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <p class="text-gray-400 text-lg font-medium">Próximamente publicaremos nuestros planes.</p>
                    <p class="text-gray-400 text-sm mt-1">Mientras tanto, contáctanos.</p>
                </div>
            @else
                @foreach($planes as $plan)
                    <div class="reveal reveal-delay-{{ $loop->index + 1 }} relative bg-white rounded-2xl border {{ $loop->first ? 'border-brand-200 shadow-2xl shadow-brand-100 scale-[1.03] z-10' : 'border-gray-200 shadow-sm' }} p-8 flex flex-col transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        @if($loop->first)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-5 py-1.5 bg-gradient-to-r from-brand-600 to-brand-500 text-white text-xs font-bold rounded-full shadow-lg shadow-brand-200">
                                Más popular
                            </div>
                        @endif
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-gray-900">{{ $plan->nombre }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $plan->tipo }}</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-black text-gray-900">${{ number_format($plan->precio, 2) }}</span>
                            <span class="text-gray-400 text-sm font-medium">/mes</span>
                            @if($plan->iva > 0)
                                <p class="text-xs text-gray-400 mt-1">+ IVA ({{ $plan->iva }}%)</p>
                            @endif
                        </div>
                        <ul class="space-y-3 mb-8 flex-1">
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Hasta {{ $plan->max_users ?? 'ilimitados' }} usuarios
                            </li>
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Sucursales ilimitadas
                            </li>
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                App móvil iOS y Android
                            </li>
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Geolocalización y geocercas
                            </li>
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Soporte por chat 24/7
                            </li>
                        </ul>
                        <a href="{{ route('acceso') }}#registro" class="w-full inline-flex items-center justify-center px-6 py-3.5 rounded-xl font-semibold text-sm transition-all {{ $loop->first ? 'bg-brand-600 text-white hover:bg-brand-700 shadow-lg shadow-brand-200 hover:shadow-xl' : 'border-2 border-gray-200 text-gray-700 hover:border-brand-300 hover:text-brand-600' }}">
                            {{ $loop->first ? 'Comenzar ahora' : 'Elegir plan' }}
                        </a>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>

<!-- ===== TESTIMONIOS ===== -->
<section id="testimonios" class="py-24 bg-gray-50 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <span class="text-brand-600 text-sm font-bold uppercase tracking-wider reveal">Testimonios</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-black text-gray-900 reveal reveal-delay-1">
                Lo que dicen nuestros clientes
            </h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="reveal reveal-delay-1 bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-1 mb-4">
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                </div>
                <p class="text-gray-600 text-sm leading-relaxed mb-6">"Desde que usamos AsistControl, eliminamos las hojas de registro manuales. Ahora todo está digitalizado y podemos ver quién llegó tarde en tiempo real."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-full flex items-center justify-center text-white font-bold text-sm">CG</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Carlos García</p>
                        <p class="text-xs text-gray-400">Director RH, LogiTrans S.A.</p>
                    </div>
                </div>
            </div>

            <div class="reveal reveal-delay-2 bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-1 mb-4">
                    @for ($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-gray-600 text-sm leading-relaxed mb-6">"La geolocalización nos ayudó a resolver el problema de empleados que marcaban desde fuera de la oficina. Ahora tenemos control total de 12 sucursales."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-sm">MR</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Mariana Ruiz</p>
                        <p class="text-xs text-gray-400">CEO, Grupo Retail Express</p>
                    </div>
                </div>
            </div>

            <div class="reveal reveal-delay-3 bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-1 mb-4">
                    @for ($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-gray-600 text-sm leading-relaxed mb-6">"La app móvil es súper intuitiva. Mis empleados la descargaron y empezaron a usarla sin capacitación. El panel de reportes me ahorra horas cada semana."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">AM</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Alejandro Méndez</p>
                        <p class="text-xs text-gray-400">Gerente General, TechSolutions MX</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FAQ ===== -->
<section class="py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-16">
            <span class="text-brand-600 text-sm font-bold uppercase tracking-wider reveal">FAQ</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-black text-gray-900 reveal reveal-delay-1">
                Preguntas frecuentes
            </h2>
        </div>
        <div class="space-y-3">
            @php $faqs = [
                ['q' => '¿Necesito instalar algo en mis computadoras?', 'a' => 'No. AsistControl es 100% en la nube. Solo necesitas un navegador web para el panel administrativo. Tus empleados descargan la app móvil gratuita.'],
                ['q' => '¿Funciona sin conexión a internet?', 'a' => 'La app móvil permite registrar marcajes offline que se sincronizan automáticamente cuando el dispositivo recupera conexión.'],
                ['q' => '¿Puedo cambiar de plan después?', 'a' => 'Sí. Puedes subir o bajar de plan en cualquier momento desde tu panel. Los cambios se aplican al instante y el cobro se ajusta proporcionalmente.'],
                ['q' => '¿Cómo registran asistencia mis empleados?', 'a' => 'Cada empleado descarga la app, inicia sesión con el código de tu empresa y registra su entrada/salida con un toque. El sistema valida ubicación GPS automáticamente.'],
                ['q' => '¿Tienen app móvil?', 'a' => 'Sí. La app está disponible para iOS y Android de forma gratuita para todos los empleados. Incluye marcajes, solicitudes, notificaciones y más.'],
                ['q' => '¿Qué pasa al terminar los 14 días de prueba?', 'a' => 'Te avisaremos antes de que termine tu prueba. Si decides continuar, eliges un plan y agregas tu método de pago. Si no, tu cuenta se desactiva sin compromiso.'],
            ]; @endphp

            @foreach($faqs as $index => $faq)
                <div class="faq-item reveal reveal-delay-{{ $index + 1 }} bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-brand-200 transition-colors">
                    <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left" onclick="this.parentElement.classList.toggle('active')">
                        <span class="text-sm font-semibold text-gray-900 pr-4">{{ $faq['q'] }}</span>
                        <svg class="faq-icon w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div class="faq-content">
                        <p class="px-6 pb-5 text-sm text-gray-500 leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ===== CTA FINAL ===== -->
<section class="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-3xl sm:text-4xl font-black text-white reveal">
            ¿Listo para digitalizar el control de asistencia?
        </h2>
        <p class="mt-4 text-lg text-brand-200 reveal reveal-delay-1">
            Únete a más de 500 empresas que ya confían en AsistControl. Comienza tu prueba gratis hoy.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center reveal reveal-delay-2">
            <a href="{{ route('acceso') }}#registro" class="inline-flex items-center justify-center px-8 py-4 bg-white text-brand-700 font-bold rounded-xl hover:bg-gray-50 transition-all shadow-2xl hover:shadow-white/30 hover:-translate-y-0.5">
                Crear cuenta gratis
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="{{ route('acceso') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white/30 text-white font-bold rounded-xl hover:bg-white/10 transition-all">
                Contactar a ventas
            </a>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="bg-gray-900 text-gray-400 py-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-10">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-white">AsistControl</span>
                </div>
                <p class="text-sm leading-relaxed max-w-sm">Sistema inteligente de control de asistencia laboral. Diseñado para empresas que buscan eficiencia, transparencia y control total sobre la asistencia de su equipo.</p>
                <p class="text-xs text-gray-500 mt-4">Desarrollado por <span class="text-gray-400 font-semibold">JALY SYSTEMS</span></p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Producto</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="#servicios" class="hover:text-white transition-colors">Servicios</a></li>
                    <li><a href="#planes" class="hover:text-white transition-colors">Planes</a></li>
                    <li><a href="#como-funciona" class="hover:text-white transition-colors">Cómo funciona</a></li>
                    <li><a href="{{ route('acceso') }}" class="hover:text-white transition-colors">Acceso</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Legal</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('privacidad') }}" class="hover:text-white transition-colors">Política de privacidad</a></li>
                    <li><a href="{{ route('terminos') }}" class="hover:text-white transition-colors">Términos y condiciones</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Contacto</h4>
                <ul class="space-y-2.5 text-sm">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        contacto@asistcontrol.com
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        +52 55 0000 0000
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ciudad de México
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm">&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
            <p class="text-xs text-gray-500">AsistControl es un producto de JALY SYSTEMS.</p>
        </div>
    </div>
</footer>

<script>
// ===== MOBILE MENU =====
document.getElementById('mobileMenuBtn').addEventListener('click', () => {
    document.getElementById('mobileMenu').classList.toggle('hidden');
    document.getElementById('menuIconOpen').classList.toggle('hidden');
    document.getElementById('menuIconClose').classList.toggle('hidden');
});

// ===== SCROLL REVEAL (IntersectionObserver) =====
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            // Animación de contadores
            if (entry.target.querySelector('[data-target]')) {
                animateCounters(entry.target);
            }
        }
    });
}, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ===== COUNTER ANIMATION =====
function animateCounters(container) {
    const counters = container.querySelectorAll('[data-target]');
    counters.forEach(counter => {
        if (counter.dataset.animated) return;
        counter.dataset.animated = true;
        const target = parseFloat(counter.dataset.target);
        const suffix = counter.dataset.suffix || '';
        const decimal = counter.dataset.decimal === 'true';
        const duration = 2000;
        const start = performance.now();

        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            // Easing ease-out
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;
            counter.textContent = decimal ? current.toFixed(1) + suffix : Math.floor(current) + suffix;
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    });
}

// ===== FAQ TOGGLE =====
document.querySelectorAll('.faq-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const item = this.parentElement;
        const wasActive = item.classList.contains('active');
        // Cerrar todos
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        // Abrir si no estaba activo
        if (!wasActive) item.classList.add('active');
    });
});

// ===== SMOOTH SCROLL con offset del nav =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const offset = 80; // altura del nav
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    });
});
</script>
</body>
</html>
