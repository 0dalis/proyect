<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y Condiciones — AsistControl</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

<header class="bg-white border-b border-gray-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
        <a href="{{ route('landing') }}" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <span class="text-lg font-bold text-gray-900">AsistControl</span>
        </a>
        <a href="{{ route('landing') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">← Volver al inicio</a>
    </div>
</header>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 sm:p-12">
        <p class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-8">
            Estos términos y condiciones son temporales y serán reemplazados por una versión legal definitiva próximamente.
        </p>

        <h1 class="text-3xl font-black text-gray-900 mb-2">Términos y Condiciones</h1>
        <p class="text-sm text-gray-400 mb-8">Última actualización: {{ date('d/m/Y') }}</p>

        <div class="prose prose-gray max-w-none space-y-6 text-sm leading-relaxed">
            <h2 class="text-lg font-bold text-gray-900 mt-8">1. Aceptación de los términos</h2>
            <p class="text-gray-600">Al registrarte y utilizar AsistControl, aceptas estos términos y condiciones en su totalidad. Si no estás de acuerdo con alguna parte, no debes utilizar el servicio.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">2. Descripción del servicio</h2>
            <p class="text-gray-600">AsistControl es un sistema SaaS (Software as a Service) de control de asistencia laboral que permite a las empresas gestionar registros de entrada y salida de empleados, turnos, vacaciones, permisos y reportes a través de un panel web y una aplicación móvil.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">3. Periodo de prueba</h2>
            <p class="text-gray-600">Ofrecemos un periodo de prueba gratuito de 14 días para nuevos clientes. Durante este periodo, tienes acceso a todas las funcionalidades del plan seleccionado sin costo. Al finalizar el periodo de prueba, deberás elegir un plan de pago para continuar utilizando el servicio.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">4. Pagos y facturación</h2>
            <p class="text-gray-600">Los planes se facturan mensualmente de forma anticipada. Los precios publicados no incluyen impuestos aplicables. Puedes cancelar tu suscripción en cualquier momento desde el panel de administración; la cancelación será efectiva al final del periodo de facturación actual.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">5. Cancelación</h2>
            <p class="text-gray-600">Puedes cancelar tu cuenta en cualquier momento. Al cancelar, tus datos permanecerán en nuestros servidores durante 30 días por si decides reactivar tu cuenta, después de lo cual serán eliminados permanentemente.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">6. Propiedad intelectual</h2>
            <p class="text-gray-600">AsistControl es un producto desarrollado y mantenido por JALY SYSTEMS. El software, diseño, marca y todos los derechos de propiedad intelectual relacionados son propiedad exclusiva de JALY SYSTEMS.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">7. Contacto</h2>
            <p class="text-gray-600">Para cualquier duda sobre estos términos, contáctanos en: <a href="mailto:legal@asistcontrol.com" class="text-brand-600 hover:underline">legal@asistcontrol.com</a></p>
        </div>
    </div>
</main>

<footer class="border-t border-gray-200 py-6 text-center">
    <p class="text-xs text-gray-400">&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
</footer>
</body>
</html>
