<!DOCTYPE html>
<html lang="es" class="h-full font-sans antialiased selection:bg-brand-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso — AsistControl</title>
    <meta name="description" content="Contacta a AsistControl o registra tu empresa y comienza tu prueba gratis de 14 días.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        /* Fondo con cuadrícula adaptativa */
        .bg-grid-light {
            background-image: radial-gradient(rgba(79, 70, 229, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .dark .bg-grid-light {
            background-image: radial-gradient(rgba(99, 102, 241, 0.15) 1px, transparent 1px);
        }

        /* Desktop Container Sliding Mechanics */
        @media (min-width: 768px) {
            .acceso-container {
                position: relative;
                width: 100%;
                max-width: 960px;
                min-height: 700px;
            }

            .form-panel {
                position: absolute;
                top: 0;
                height: 100%;
                width: 50%;
                overflow-y: auto;
                transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
            }

            .panel-contacto {
                left: 0;
                z-index: 2;
                opacity: 1;
            }

            .panel-registro {
                left: 0;
                z-index: 1;
                opacity: 0;
            }

            .overlay-panel {
                position: absolute;
                top: 0;
                height: 100%;
                width: 50%;
                left: 50%;
                z-index: 100;
                transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .overlay-content-left,
            .overlay-content-right {
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2.5rem;
                text-align: center;
                transition: opacity 0.4s ease, transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .overlay-content-left {
                transform: translateX(-20%);
                opacity: 0;
                pointer-events: none;
            }

            .overlay-content-right {
                transform: translateX(0);
                opacity: 1;
                pointer-events: auto;
            }

            /* Estado Activo: Mostrar Registro */
            .right-panel-active .panel-contacto {
                transform: translateX(100%);
                opacity: 0;
            }

            .right-panel-active .panel-registro {
                transform: translateX(100%);
                z-index: 5;
                opacity: 1;
            }

            .right-panel-active .overlay-panel {
                transform: translateX(-100%);
            }

            .right-panel-active .overlay-content-left {
                transform: translateX(0);
                opacity: 1;
                pointer-events: auto;
            }

            .right-panel-active .overlay-content-right {
                transform: translateX(20%);
                opacity: 0;
                pointer-events: none;
            }
        }

        /* Inputs adaptables Claro / Oscuro */
        .form-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            color: #0f172a;
            transition: all 0.2s ease;
            outline: none;
        }
        .dark .form-input {
            background-color: rgba(15, 23, 42, 0.6);
            border-color: rgba(51, 65, 85, 0.8);
            color: #f8fafc;
        }
        .form-input::placeholder {
            color: #94a3b8;
        }
        .dark .form-input::placeholder {
            color: #64748b;
        }
        .form-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
            background-color: #ffffff;
        }
        .dark .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background-color: rgba(15, 23, 42, 0.9);
        }

        /* Botón Fantasma para el Overlay */
        .ghost-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            padding: 0.625rem 2rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
        }
        .ghost-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col items-center justify-center p-4 sm:p-6 bg-grid-light relative overflow-x-hidden transition-colors duration-300">

    <!-- Glows de fondo -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-brand-500/10 dark:bg-brand-600/15 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="w-full max-w-5xl flex flex-col items-center relative z-10">

        <!-- Header Brand -->
        <a href="{{ route('landing') }}" class="flex items-center gap-2.5 mb-6 group">
            <div class="w-8 h-8 bg-gradient-to-br from-brand-600 to-indigo-700 rounded-lg flex items-center justify-center shadow-lg shadow-brand-500/20 group-hover:scale-105 transition-transform">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Asist<span class="text-brand-600 dark:text-brand-400">Control</span></span>
        </a>

        <!-- Switcher para Pantallas Chicas (Mobile Tabs) -->
        <div class="flex md:hidden w-full max-w-sm bg-slate-200 dark:bg-slate-900 p-1 border border-slate-300 dark:border-slate-800 rounded-lg mb-4">
            <button id="tabContacto" class="flex-1 py-1.5 text-xs font-semibold rounded-md text-slate-600 dark:text-slate-300 transition-all">Contacto</button>
            <button id="tabRegistro" class="flex-1 py-1.5 text-xs font-semibold rounded-md text-slate-600 dark:text-slate-300 transition-all">Registro</button>
        </div>

        <!-- ===== MAIN CARD CONTAINER ===== -->
        <div class="acceso-container bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl shadow-xl dark:shadow-2xl backdrop-blur-xl overflow-hidden w-full transition-colors duration-300" id="accesoContainer">

            <!-- ===== PANEL CONTACTO ===== -->
            <div class="form-panel panel-contacto w-full md:w-1/2 p-6 sm:p-10 flex flex-col justify-center" id="mobilePanelContacto">
                <form id="contactoForm" class="space-y-3.5">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Contáctanos</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Déjanos tus datos y respondemos en menos de 24 horas.</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre completo</label>
                        <input type="text" name="nombre" required class="form-input" placeholder="Juan Pérez">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Empresa</label>
                            <input type="text" name="empresa" required class="form-input" placeholder="Mi Empresa S.A.">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
                            <input type="tel" name="telefono" class="form-input" placeholder="+52 55 1234 5678">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Correo electrónico</label>
                        <input type="email" name="email" required class="form-input" placeholder="juan@empresa.com">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Mensaje</label>
                        <textarea name="mensaje" required rows="2" class="form-input resize-none" placeholder="Cuéntanos las necesidades de tu empresa..."></textarea>
                    </div>

                    <div id="contactoMsg" class="hidden text-xs rounded-lg p-2.5"></div>

                    <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs rounded-lg shadow-md shadow-brand-600/20 transition-all hover:-translate-y-0.5">
                        Enviar mensaje
                    </button>
                </form>
            </div>

            <!-- ===== PANEL REGISTRO ===== -->
            <div class="form-panel panel-registro w-full md:w-1/2 p-6 sm:p-10 hidden md:flex flex-col justify-center" id="mobilePanelRegistro">
                <form id="registroForm" class="space-y-3.5">
                    @csrf
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Crea tu cuenta</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Prueba Premium gratis por {{ $daysTrial }} días.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre</label>
                            <input type="text" name="nombre" required class="form-input" placeholder="Juan">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Apellido</label>
                            <input type="text" name="apellido" required class="form-input" placeholder="Pérez">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre de la empresa</label>
                        <input type="text" name="nombre_empresa" required class="form-input" placeholder="Mi Empresa S.A.">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Correo corporativo</label>
                        <input type="email" name="email" required class="form-input" placeholder="juan@empresa.com">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">Contraseña</label>
                        <div class="relative">
                            <input type="password" id="passwordInput" name="password" required class="form-input pr-10" placeholder="Mínimo 8 caracteres">
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <!-- Icono Ojo Abierto -->
                                <svg id="eyeOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <!-- Icono Ojo Cerrado (oculto por defecto) -->
                                <svg id="eyeClosed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908A8.962 8.962 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21f-9 9 0 00-9-9m9 9L3 3" />
                                </svg>
                            </button>
                        </div>
                        <p id="passwordError" class="hidden text-[10px] text-rose-500 mt-1"></p>
                    </div>

                    {{-- Términos --}}
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="terminos" id="terminos" required class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500/20">
                        <label for="terminos" class="text-[11px] text-slate-500 dark:text-slate-400">
                            Acepto los <a href="{{ route('terminos') }}" target="_blank" class="text-brand-600 dark:text-brand-400 hover:underline">Términos</a> y <a href="{{ route('privacidad') }}" target="_blank" class="text-brand-600 dark:text-brand-400 hover:underline">Privacidad</a>.
                        </label>
                    </div>

                    <input type="hidden" name="recaptcha_token" id="recaptchaToken">
                    {{-- Campo Honeypot disfrazado --}}
                    <div style="display: none !important; opacity: 0; position: absolute; left: -9999px;" aria-hidden="true">
                        <input type="text" name="plan" tabindex="-1" autocomplete="off">
                    </div>

                    <div id="registroMsg" class="hidden text-xs rounded-lg p-2.5"></div>
                    <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs rounded-lg shadow-md shadow-brand-600/20 transition-all hover:-translate-y-0.5">
                        Comenzar prueba gratis de {{ $daysTrial }} días
                    </button>
                </form>
            </div>

            <!-- ===== OVERLAY PANEL (DESKTOP ONLY) ===== -->
            <div class="overlay-panel hidden md:block">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-600 via-indigo-700 to-slate-900 opacity-95"></div>
                <div class="relative z-10 h-full flex items-center justify-center">

                    <!-- Overlay Derecho (visible por defecto) -->
                    <div class="overlay-content-right">
                        <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">¿Listo para empezar?</h2>
                        <p class="text-xs text-indigo-100/80 mb-8 max-w-xs leading-relaxed">
                            Crea la cuenta de tu empresa y optimiza tu control de asistencia hoy mismo.
                        </p>
                        <button id="toRegistro" class="ghost-btn">Registrar empresa</button>
                    </div>

                    <!-- Overlay Izquierdo (visible en modo registro) -->
                    <div class="overlay-content-left">
                        <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">¿Tienes alguna duda?</h2>
                        <p class="text-xs text-indigo-100/80 mb-8 max-w-xs leading-relaxed">
                            Si prefieres asesoría sobre planes o requerimientos personalizados, escríbenos.
                        </p>
                        <button id="toContacto" class="ghost-btn">Contáctanos</button>
                    </div>

                </div>
            </div>

        </div>

        <p class="text-xs text-slate-500 dark:text-slate-400 mt-6">
            <a href="{{ route('landing') }}" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al sitio principal
            </a>
        </p>

    </div>

<script>
    // ===== LÓGICA MODO CLARO / OSCURO (lee localStorage) =====
    $(function() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            $('html').addClass('dark');
        } else {
            $('html').removeClass('dark');
        }
    });
    // ===== MOSTRAR / OCULTAR CONTRASEÑA =====
    $(function() {
        $('#togglePassword').on('click', function() {
            const $input = $('#passwordInput');
            const $eyeOpen = $('#eyeOpen');
            const $eyeClosed = $('#eyeClosed');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $eyeOpen.addClass('hidden');
                $eyeClosed.removeClass('hidden');
            } else {
                $input.attr('type', 'password');
                $eyeClosed.addClass('hidden');
                $eyeOpen.removeClass('hidden');
            }
        });
    });
    // ===== VALIDACIÓN DE CONTRASEÑA =====
    function validarPassword(pwd) {
        if (/\s/.test(pwd)) return "La contraseña no debe contener espacios.";
        if (pwd.length < 8) return "Debe tener al menos 8 caracteres.";
        if (!/[A-Z]/.test(pwd)) return "Debe contener al menos una letra mayúscula.";
        if (!/[a-z]/.test(pwd)) return "Debe contener al menos una letra minúscula.";
        if (!/[0-9]/.test(pwd)) return "Debe contener al menos un número.";
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) return "Debe contener al menos un carácter especial.";

        // Validar números consecutivos mayores a 3 dígitos (ej: 1234 o 4321)
        const matches = pwd.match(/\d+/g);
        if (matches) {
            for (const numStr of matches) {
                if (numStr.length > 3) {
                    for (let i = 0; i <= numStr.length - 4; i++) {
                        const d1 = parseInt(numStr[i]);
                        const d2 = parseInt(numStr[i+1]);
                        const d3 = parseInt(numStr[i+2]);
                        const d4 = parseInt(numStr[i+3]);

                        // Ascendentes (ej: 1,2,3,4) o Descendentes (ej: 4,3,2,1)
                        if ((d2 === d1 + 1 && d3 === d2 + 1 && d4 === d3 + 1) ||
                            (d2 === d1 - 1 && d3 === d2 - 1 && d4 === d3 - 1)) {
                            return "Los números no pueden tener secuencias consecutivas de más de 3 dígitos (ej: 1234).";
                        }
                    }
                }
            }
        }

        return null; // Todo correcto
    }
    // ===== SLIDING & MOBILE TABS MECHANICS =====
    $(function() {
        const $container = $('#accesoContainer');
        const $mobContacto = $('#mobilePanelContacto');
        const $mobRegistro = $('#mobilePanelRegistro');
        const $tabContacto = $('#tabContacto');
        const $tabRegistro = $('#tabRegistro');

        function updateMobileTabs(isRegistro) {
            if (window.innerWidth < 768) {
                if (isRegistro) {
                    $mobContacto.addClass('hidden');
                    $mobRegistro.removeClass('hidden').addClass('flex');
                    $tabRegistro.attr('class', 'flex-1 py-1.5 text-xs font-semibold rounded-md bg-brand-600 text-white shadow-sm');
                    $tabContacto.attr('class', 'flex-1 py-1.5 text-xs font-semibold rounded-md text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200');
                } else {
                    $mobRegistro.addClass('hidden').removeClass('flex');
                    $mobContacto.removeClass('hidden');
                    $tabContacto.attr('class', 'flex-1 py-1.5 text-xs font-semibold rounded-md bg-brand-600 text-white shadow-sm');
                    $tabRegistro.attr('class', 'flex-1 py-1.5 text-xs font-semibold rounded-md text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200');
                }
            }
        }

        function showRegistro() {
            $container.addClass('right-panel-active');
            updateMobileTabs(true);
            if (window.location.hash !== '#registro') {
                history.replaceState(null, null, '#registro');
            }
        }

        function showContacto() {
            $container.removeClass('right-panel-active');
            updateMobileTabs(false);
            if (window.location.hash !== '') {
                history.replaceState(null, null, window.location.pathname + window.location.search);
            }
        }

        if (window.location.hash === '#registro') {
            showRegistro();
        } else {
            showContacto();
        }

        $('#tabRegistro').on('click', showRegistro);
        $('#tabContacto').on('click', showContacto);
        $('#toRegistro').on('click', showRegistro);
        $('#toContacto').on('click', showContacto);
    });

    // ===== AJAX CONTACTO =====
    $(function() {
        $('#contactoForm').on('submit', async function(e) {
            e.preventDefault();
            const $btn = $(this).find('button[type="submit"]');
            const $msg = $('#contactoMsg');
            $btn.prop('disabled', true).text('Enviando...');

            try {
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                const res = await fetch('{{ route("landing.contacto") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                $msg.attr('class', 'text-xs rounded-lg p-2.5 ' + (json.success ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'));
                $msg.removeClass('hidden').text(json.message);
                if (json.success) this.reset();
            } catch(err) {
                $msg.attr('class', 'text-xs rounded-lg p-2.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20');
                $msg.removeClass('hidden').text('Error al enviar el mensaje. Inténtalo de nuevo.');
            }
            $btn.prop('disabled', false).text('Enviar mensaje');
        });
    });

    // ===== AJAX REGISTRO =====
    $(function() {
        $('#registroForm').on('submit', function(e) {
            e.preventDefault();

            const form = this;
            const $passwordInput = $('#passwordInput');
            const $passwordError = $('#passwordError');
            const passwordVal = $passwordInput.val();

            const errorMsg = validarPassword(passwordVal);
            if (errorMsg) {
                $passwordError.text(errorMsg).removeClass('hidden');
                $passwordInput.addClass('border-rose-500').focus();
                return;
            } else {
                $passwordError.addClass('hidden');
                $passwordInput.removeClass('border-rose-500');
            }

            const $btn = $(form).find('button[type="submit"]');
            const $msg = $('#registroMsg');
            $btn.prop('disabled', true).text('Creando cuenta...');

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ config("services.recaptcha.site_key") }}', { action: 'registro' })
                    .then(async function(token) {
                        $('#recaptchaToken').val(token);
                        try {
                            const formData = new FormData(form);
                            const data = Object.fromEntries(formData);
                            const res = await fetch('{{ route("landing.registro") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(data)
                            });
                            const json = await res.json();
                            $msg.attr('class', 'text-xs rounded-lg p-2.5 ' + (json.success ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'));
                            $msg.removeClass('hidden').text(json.message);
                            if (json.success) {
                                form.reset();
                            }
                        } catch(err) {
                            $msg.attr('class', 'text-xs rounded-lg p-2.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20');
                            $msg.removeClass('hidden').text('Error al registrar la cuenta. Inténtalo de nuevo.');
                        } finally {
                            $btn.prop('disabled', false).text('Comenzar prueba gratis');
                        }
                    })
                    .catch(function(error) {
                        $msg.attr('class', 'text-xs rounded-lg p-2.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20');
                        $msg.removeClass('hidden').text('Error en la verificación de seguridad reCAPTCHA.');
                        $btn.prop('disabled', false).text('Comenzar prueba gratis');
                    });
            });
        });

        $('#passwordInput').on('input', function() {
            $('#passwordError').addClass('hidden');
            $(this).removeClass('border-rose-500');
        });
    });
</script>
</body>
</html>
