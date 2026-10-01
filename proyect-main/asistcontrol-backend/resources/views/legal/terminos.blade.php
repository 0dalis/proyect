<!DOCTYPE html>
<html lang="es" class="scroll-smooth antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y Condiciones — AsistControl</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
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
</head>
<body class="bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans transition-colors duration-300">

@include('partials.public-navbar')

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pt-28">
    <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-xl p-8 sm:p-12">
        <p class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-lg px-4 py-3 mb-8">
            Estos términos y condiciones son temporales y serán reemplazados por una versión legal definitiva próximamente.
        </p>

        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">Términos y Condiciones</h1>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-8">Última actualización: {{ date('d/m/Y') }}</p>

        <div class="space-y-6 text-sm leading-relaxed">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">1. Aceptación de los términos</h2>
                <p class="text-slate-600 dark:text-slate-400">Al registrarte y utilizar AsistControl, aceptas estos términos y condiciones en su totalidad. Si no estás de acuerdo con alguna parte, no debes utilizar el servicio.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">2. Descripción del servicio</h2>
                <p class="text-slate-600 dark:text-slate-400">AsistControl es un sistema SaaS (Software as a Service) de control de asistencia laboral que permite a las empresas gestionar registros de entrada y salida de empleados, turnos, vacaciones, permisos y reportes a través de un panel web y una aplicación móvil.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">3. Periodo de prueba</h2>
                <p class="text-slate-600 dark:text-slate-400">Ofrecemos un periodo de prueba gratuito para nuevos clientes. Durante este periodo, tienes acceso a todas las funcionalidades del plan seleccionado sin costo. Al finalizar el periodo de prueba, deberás elegir un plan de pago para continuar utilizando el servicio.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">4. Pagos y facturación</h2>
                <p class="text-slate-600 dark:text-slate-400">Los planes se facturan mensualmente de forma anticipada. Los precios publicados no incluyen impuestos aplicables. Puedes cancelar tu suscripción en cualquier momento desde el panel de administración; la cancelación será efectiva al final del periodo de facturación actual.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">5. Cancelación</h2>
                <p class="text-slate-600 dark:text-slate-400">Puedes cancelar tu cuenta en cualquier momento. Al cancelar, tus datos permanecerán en nuestros servidores durante 30 días por si decides reactivar tu cuenta, después de lo cual serán eliminados permanentemente.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">6. Propiedad intelectual</h2>
                <p class="text-slate-600 dark:text-slate-400">AsistControl es un producto desarrollado y mantenido por JALY SYSTEMS. El software, diseño, marca y todos los derechos de propiedad intelectual relacionados son propiedad exclusiva de JALY SYSTEMS.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">7. Contacto</h2>
                <p class="text-slate-600 dark:text-slate-400">Para cualquier duda sobre estos términos, contáctanos en: <a href="mailto:legal@asistcontrol.com" class="text-brand-600 dark:text-brand-400 hover:underline">legal@asistcontrol.com</a></p>
            </div>
        </div>
    </div>
</main>

<footer class="border-t border-slate-200 dark:border-slate-800/80 py-8 text-center transition-colors duration-300">
    <p class="text-xs text-slate-400 dark:text-slate-500">&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
</footer>
</body>
</html>
