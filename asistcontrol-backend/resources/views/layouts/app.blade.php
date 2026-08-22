<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- El CSS para los estilos visuales -->
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

        <!-- El script de la librería -->
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @keyframes shimmer {
                0% { background-position: -200px 0; }
                100% { background-position: calc(200px + 100%) 0; }
            }
            .skeleton-shimmer {
                background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
                background-size: 200px 100%;
                animation: shimmer 1.5s ease-in-out infinite;
            }
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(12px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .animate-fade-in-up {
                animation: fadeInUp 0.4s ease-out forwards;
            }
            .loader-overlay {
                position: fixed;
                inset: 0;
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(2px);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                gap: 12px;
            }
            .loader-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid #e5e7eb;
                border-top-color: #6366f1;
                border-radius: 50%;
                animation: loader-spin 0.7s linear infinite;
            }
            @keyframes loader-spin {
                to { transform: rotate(360deg); }
            }
            /* NProgress-style top bar */
            .page-loader-bar {
                position: fixed;
                top: 0;
                left: 0;
                width: 0%;
                height: 3px;
                background: linear-gradient(90deg, #6366f1, #8b5cf6);
                z-index: 10000;
                transition: width 0.3s ease;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <!-- Barra de progreso superior -->
        <div id="pageLoaderBar" class="page-loader-bar" style="display:none;"></div>

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Overlay de carga global -->
        <div id="globalLoader" class="loader-overlay" style="display:none;">
            <div class="loader-spinner"></div>
            <span class="text-sm text-neutral-500 font-medium">Cargando...</span>
        </div>

        @stack('scripts')

        <script>
        // Loader global para peticiones AJAX y navegación
        (function() {
            const $bar = $('#pageLoaderBar');
            const $loader = $('#globalLoader');
            let activeRequests = 0;
            let barTimer = null;
            let progress = 0;

            function showBar() {
                progress = 0;
                $bar.css({ width: '0%', display: 'block' });
                simulateProgress();
            }

            function simulateProgress() {
                if (progress < 90) {
                    progress += (90 - progress) * 0.2 + Math.random() * 5;
                    $bar.css('width', Math.min(progress, 90) + '%');
                    barTimer = setTimeout(simulateProgress, 300);
                }
            }

            function hideBar() {
                progress = 100;
                $bar.css('width', '100%');
                clearTimeout(barTimer);
                setTimeout(function() {
                    $bar.css({ width: '0%', display: 'none' });
                }, 200);
            }

            function showLoader(msg) {
                if (msg) {
                    $loader.find('span').text(msg);
                } else {
                    $loader.find('span').text('Cargando...');
                }
                $loader.fadeIn(150);
            }

            function hideLoader() {
                $loader.fadeOut(150);
            }

            // Interceptar AJAX global (jQuery)
            $(document).ajaxStart(function() {
                activeRequests++;
                showBar();
            }).ajaxStop(function() {
                activeRequests--;
                if (activeRequests <= 0) {
                    activeRequests = 0;
                    hideBar();
                }
            });

            // Interceptar clicks en enlaces para mostrar barra
            $(document).on('click', 'a:not([target="_blank"]):not([download]):not([data-no-loader])', function(e) {
                const href = $(this).attr('href');
                if (href && href !== '#' && !href.startsWith('javascript:') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                    showBar();
                }
            });

            // Exponer API global
            window.AppLoader = {
                show: showLoader,
                hide: hideLoader,
                showBar: showBar,
                hideBar: hideBar
            };
        })();

        // ===== Cierre de sesión por inactividad =====
        (function() {
            var TIMEOUT = {{ (int) config('session.lifetime', 60) }} * 60 * 1000;
            var logoutTimer = null;
            var activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

            function submitLogout() {
                var csrf = document.querySelector('meta[name="csrf-token"]');
                var token = csrf ? csrf.getAttribute('content') : '';
                fetch('{{ route('logout') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                }).finally(function () {
                    window.location.href = '{{ route('login') }}';
                });
            }

            function onInactivity() {
                Swal.fire({
                    title: 'Sesión expirada',
                    text: 'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.',
                    icon: 'warning',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                }).then(function () {
                    submitLogout();
                });
            }

            function resetTimer() {
                clearTimeout(logoutTimer);
                logoutTimer = setTimeout(onInactivity, TIMEOUT);
            }

            activityEvents.forEach(function (evt) {
                document.addEventListener(evt, resetTimer);
            });

            resetTimer();
        })();
        </script>
    </body>
</html>
