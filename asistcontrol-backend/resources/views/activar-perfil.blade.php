<!DOCTYPE html>
<html lang="es" class="scroll-smooth antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activar Perfil — AsistControl</title>
    <meta name="description" content="Verifica tu perfil y activa tu cuenta de AsistControl para comenzar a gestionar tu empresa.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
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

<!-- ===== HERO DE ACTIVACIÓN ===== -->
<section class="pt-28 pb-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-brand-500 to-indigo-700 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-brand-500/20">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Activa tu perfil de administrador
        </h1>
        <p class="mt-3 text-sm sm:text-base text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
            Bienvenido, <strong class="text-slate-700 dark:text-slate-200">{{ $nombreCompleto }}</strong>.
            Tu empresa <strong class="text-slate-700 dark:text-slate-200">{{ $nombreEmpresa }}</strong> está lista para comenzar a operar en AsistControl.
        </p>
    </div>
</section>

<!-- ===== QUÉ PUEDES HACER ===== -->
<section class="py-12 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-200 dark:border-slate-800/80 transition-colors duration-300">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white text-center mb-8">Como administrador podrás:</h2>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg">
                <div class="w-8 h-8 rounded-md bg-brand-50 dark:bg-brand-950/40 border border-brand-100 dark:border-brand-900 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-900 dark:text-white">Gestión de empleados</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Registra colaboradores, asigna turnos y monitorea asistencias en tiempo real.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg">
                <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-900 dark:text-white">Reportes de nómina</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Genera pre-nóminas en Excel o CSV listas para tu sistema contable.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg">
                <div class="w-8 h-8 rounded-md bg-purple-50 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-900 dark:text-white">Control de sucursales</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Administra múltiples ubicaciones con geocercas independientes desde un solo panel.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg">
                <div class="w-8 h-8 rounded-md bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-900 dark:text-white">Turnos y horarios</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Configura horarios fijos y rotativos con reglas automáticas de retardos y horas extra.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg">
                <div class="w-8 h-8 rounded-md bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-900 dark:text-white">Notificaciones push</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Envía avisos a todo tu personal o grupos específicos en tiempo real.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-lg">
                <div class="w-8 h-8 rounded-md bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-100 dark:border-cyan-900 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-900 dark:text-white">Permisos e incidencias</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Gestiona vacaciones, incapacidades y justificantes con flujo de aprobación.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== VERIFICACIÓN ===== -->
<section class="py-16 bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-xl p-8">
            <svg class="w-10 h-10 text-brand-600 dark:text-brand-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Verificación de seguridad</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
                Para proteger tu cuenta, necesitamos verificar que eres una persona real. Al hacer clic en el botón se ejecutará una validación automática.
            </p>

            <div id="verificacionMsg" class="hidden text-xs rounded-lg p-3 mb-4"></div>

            <button id="btnVerificar" data-token="{{ $activationToken }}"
                    class="inline-flex items-center justify-center gap-2 w-full py-3 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-sm rounded-lg shadow-sm shadow-brand-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="btnIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="btnText">Verificar mi Perfil</span>
            </button>

            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-4">
                Este enlace es válido por 24 horas. Si ha expirado, deberás registrarte nuevamente.
            </p>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="border-t border-slate-200 dark:border-slate-800/80 py-8 text-center transition-colors duration-300">
    <p class="text-xs text-slate-400 dark:text-slate-500">&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
</footer>

<script>
    const userId = @json($user->id);
    const siteKey = @json(config('services.recaptcha.site_key'));

    function showToast(message, type) {
        const bg = type === 'success'
            ? 'linear-gradient(135deg, #059669 0%, #047857 100%)'
            : 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';
        Toastify({
            text: message,
            duration: 4000,
            gravity: 'top',
            position: 'right',
            stopOnFocus: true,
            style: { background: bg, borderRadius: '8px', fontSize: '13px', padding: '12px 20px' }
        }).showToast();
    }

    function setButtonLoading(loading) {
        const btn = document.getElementById('btnVerificar');
        const icon = document.getElementById('btnIcon');
        const text = document.getElementById('btnText');
        if (loading) {
            btn.disabled = true;
            icon.innerHTML = '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>';
            icon.classList.add('animate-spin');
            text.textContent = 'Verificando...';
        } else {
            btn.disabled = false;
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            icon.classList.remove('animate-spin');
            text.textContent = 'Verificar mi Perfil';
        }
    }

    document.getElementById('btnVerificar').addEventListener('click', function() {
        setButtonLoading(true);
        const activationToken = this.dataset.token;

        grecaptcha.ready(function() {
            grecaptcha.execute(siteKey, { action: 'verificar_perfil' })
                .then(function(token) {
                    fetch('{{ route("verificar.cuenta", $user->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ recaptcha_token: token, activation_token: activationToken })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            showToast(data.message, 'success');
                            if (data.redirect) {
                                setTimeout(function() {
                                    window.location.href = data.redirect;
                                }, 2000);
                            }
                        } else {
                            showToast(data.message || 'Error al verificar tu perfil.', 'error');
                            setButtonLoading(false);
                        }
                    })
                    .catch(function() {
                        showToast('Error de conexión. Inténtalo de nuevo.', 'error');
                        setButtonLoading(false);
                    });
                })
                .catch(function() {
                    showToast('Error en la verificación de seguridad. Recarga la página e inténtalo de nuevo.', 'error');
                    setButtonLoading(false);
                });
        });
    });
</script>

<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
</body>
</html>
