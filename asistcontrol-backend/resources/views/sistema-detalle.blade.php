<!DOCTYPE html>
<html lang="es" class="scroll-smooth antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Gestión de Asistencia — AsistControl</title>
    <meta name="description" content="Conozca a detalle cómo funciona el sistema de control de asistencia laboral con geolocalización GPS, gestión de turnos y reportes automatizados.">
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
<section class="pt-28 pb-16 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 tracking-widest uppercase text-center">Plataforma Integral</p>
        <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight text-center">
            Sistema de gestión de asistencia laboral
        </h1>
        <p class="mt-4 text-sm sm:text-base text-slate-500 dark:text-slate-400 text-center max-w-3xl mx-auto leading-relaxed">
            Una solución integral que combina geolocalización GPS, gestión de turnos, control de incidencias y reportes automatizados para eliminar los procesos manuales de control de personal.
        </p>
    </div>
</section>

<!-- ===== ARQUITECTURA DEL SISTEMA ===== -->
<section class="py-16 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-200 dark:border-slate-800/80 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-12">Cómo funciona la plataforma</h2>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="w-12 h-12 rounded-lg bg-brand-50 dark:bg-brand-950/40 border border-brand-100 dark:border-brand-900 text-brand-600 dark:text-brand-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Aplicación Móvil</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Los empleados registran su entrada y salida desde la App con validación GPS en tiempo real.</p>
            </div>

            <div class="text-center">
                <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Procesamiento en la Nube</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Los marcajes se procesan en servidores seguros con validación de geocercas, turnos y reglas de negocio.</p>
            </div>

            <div class="text-center">
                <div class="w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900 text-purple-600 dark:text-purple-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Panel Administrativo</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Los gerentes de RH acceden a dashboards, reportes exportables y configuración de políticas desde el navegador.</p>
            </div>

            <div class="text-center">
                <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Exportación a Nómina</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Genere archivos Excel y CSV con el concentrado de asistencias listo para importar en su sistema contable.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== MÓDULOS DETALLADOS ===== -->
<section class="py-20 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-14">Módulos del sistema</h2>

        <div class="space-y-16">
            <!-- Módulo 1: Geolocalización -->
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="w-10 h-10 rounded-md bg-brand-50 dark:bg-brand-950/40 border border-brand-100 dark:border-brand-900 text-brand-600 dark:text-brand-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-3">Validación por Geolocalización</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-3">
                        El núcleo del sistema de control de asistencia se basa en la validación geográfica de cada marcaje. Cada sucursal se configura con coordenadas GPS precisas y un radio de tolerancia personalizado.
                    </p>
                    <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                        <li class="flex items-start gap-2">
                            <span class="text-brand-600 dark:text-brand-400 mt-0.5">—</span>
                            <span>Defina múltiples sucursales con geocercas independientes.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-brand-600 dark:text-brand-400 mt-0.5">—</span>
                            <span>Configure el radio de tolerancia en metros para cada ubicación.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-brand-600 dark:text-brand-400 mt-0.5">—</span>
                            <span>Reciba alertas automáticas cuando un marcaje ocurre fuera del perímetro autorizado.</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-lg p-6">
                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Especificaciones técnicas</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Precisión GPS</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">5–15 metros</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Radio configurable</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">50–5000 m</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Modo sin conexión</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Almacenamiento local cifrado</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-500 dark:text-slate-400">Sucursales soportadas</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Ilimitadas</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Módulo 2: Turnos -->
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div class="order-2 md:order-1 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-lg p-6">
                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Tipos de turno soportados</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Turnos fijos</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Matutino, Vespertino, Nocturno</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Turnos rotativos</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Ciclos de rotación personalizables</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Tolerancia de entrada</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Configurable por puesto</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-500 dark:text-slate-400">Horas extra automáticas</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Cálculo automático con reglas</span>
                        </div>
                    </div>
                </div>
                <div class="order-1 md:order-2">
                    <div class="w-10 h-10 rounded-md bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-3">Gestión Inteligente de Turnos</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-3">
                        Configure horarios fijos o rotativos con reglas de negocio adaptables a cualquier estructura organizacional. El sistema calcula automáticamente retardos, salidas anticipadas y horas extra.
                    </p>
                    <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 dark:text-emerald-400 mt-0.5">—</span>
                            <span>Asigne turnos a empleados individualmente o por departamento completo.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 dark:text-emerald-400 mt-0.5">—</span>
                            <span>Defina tolerancias de entrada, horarios de comida y descansos obligatorios.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 dark:text-emerald-400 mt-0.5">—</span>
                            <span>Visualice la programación semanal en un calendario interactivo.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Módulo 3: Incidencias -->
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="w-10 h-10 rounded-md bg-purple-50 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-3">Control de Incidencias y Permisos</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-3">
                        El módulo de incidencias permite a los empleados solicitar permisos, vacaciones y justificar ausencias directamente desde la aplicación móvil, adjuntando documentación fotográfica cuando sea necesario.
                    </p>
                    <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 dark:text-purple-400 mt-0.5">—</span>
                            <span>Solicitud de vacaciones con flujo de aprobación configurable.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 dark:text-purple-400 mt-0.5">—</span>
                            <span>Justificación de faltas con carga de fotografías de comprobantes.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 dark:text-purple-400 mt-0.5">—</span>
                            <span>Notificaciones automáticas a supervisores cuando se registra una ausencia no programada.</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-lg p-6">
                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Tipos de incidencia</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Vacaciones</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Con aprobación de supervisor</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Permisos personales</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Por horas o días completos</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Incapacidades</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Adjuntar justificante médico</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-500 dark:text-slate-400">Retardos y faltas</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Detección automática</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Módulo 4: Reportes -->
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div class="order-2 md:order-1 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-lg p-6">
                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Formatos de exportación</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Excel (.xlsx)</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Formato compatible con cualquier ERP</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">CSV</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Importación directa a sistemas contables</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-800/50">
                            <span class="text-slate-500 dark:text-slate-400">Periodo de reporte</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Diario, Semanal, Quincenal, Mensual</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-500 dark:text-slate-400">Columnas personalizables</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">Seleccione los campos a exportar</span>
                        </div>
                    </div>
                </div>
                <div class="order-1 md:order-2">
                    <div class="w-10 h-10 rounded-md bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-3">Reportes de Nómina Automatizados</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-3">
                        Genere concentrados de asistencia listos para procesar la nómina. El sistema consolida horas trabajadas, retardos, horas extra, ausencias e incidencias en un solo reporte exportable.
                    </p>
                    <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 dark:text-amber-400 mt-0.5">—</span>
                            <span>Reportes agrupados por empleado, departamento o sucursal.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 dark:text-amber-400 mt-0.5">—</span>
                            <span>Filtro por rango de fechas con resumen de totales.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 dark:text-amber-400 mt-0.5">—</span>
                            <span>Exportación programada automática a su equipo de contabilidad.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FUNCIONALIDADES ADICIONALES ===== -->
<section class="py-20 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-200 dark:border-slate-800/80 transition-colors duration-300">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-12">Funcionalidades complementarias</h2>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg p-5">
                <div class="w-8 h-8 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1.5">Notificaciones Push</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Comunique avisos organizacionales a todo el personal o grupos específicos. Recordatorios de turno, alertas de marcaje pendiente y anuncios generales.</p>
            </div>

            <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg p-5">
                <div class="w-8 h-8 rounded bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-100 dark:border-cyan-900 text-cyan-600 dark:text-cyan-400 flex items-center justify-center mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1.5">Modo Kiosco</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Convierta cualquier tableta en una estación fija de marcaje. Los empleados ingresan su PIN o código para registrar asistencia sin necesidad de teléfono propio.</p>
            </div>

            <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg p-5">
                <div class="w-8 h-8 rounded bg-brand-50 dark:bg-brand-950/40 border border-brand-100 dark:border-brand-900 text-brand-600 dark:text-brand-400 flex items-center justify-center mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1.5">Seguridad de Datos</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Conexiones cifradas con SSL/TLS. Datos almacenados en servidores seguros con respaldo automático. Cumplimiento con normativas de protección de datos personales.</p>
            </div>

            <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg p-5">
                <div class="w-8 h-8 rounded bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1.5">Roles y Permisos</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Defina perfiles de acceso granulares: super administrador, gerente de RH, supervisor de sucursal y empleado estándar. Cada rol con permisos específicos.</p>
            </div>

            <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg p-5">
                <div class="w-8 h-8 rounded bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1.5">Multisucursal</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Administre múltiples ubicaciones desde un solo panel. Cada sucursal con sus propias geocercas, horarios y personal asignado.</p>
            </div>

            <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg p-5">
                <div class="w-8 h-8 rounded bg-purple-50 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1.5">Calendario de Días Festivos</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Configure días festivos y descansos obligatorios por región. El sistema ajusta automáticamente las reglas de asistencia durante fechas especiales.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="py-20 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Optimice el control de asistencia de su empresa</h2>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Comience hoy con una prueba gratuita y descubra cómo AsistControl puede transformar sus operaciones de recursos humanos.</p>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('acceso') }}#registro" class="inline-flex items-center justify-center px-8 py-3 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-sm rounded-lg transition-colors">
                Prueba gratuita de {{ $daysTrial ?? 14 }} días
            </a>
            <a href="{{ route('planes-detalle') }}" class="inline-flex items-center justify-center px-8 py-3 bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 font-semibold text-sm rounded-lg transition-colors">
                Ver planes y precios
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

</body>
</html>
