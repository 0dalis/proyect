<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidad — AsistControl</title>
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
            Esta política de privacidad es temporal y será reemplazada por una versión legal definitiva próximamente.
        </p>

        <h1 class="text-3xl font-black text-gray-900 mb-2">Política de Privacidad</h1>
        <p class="text-sm text-gray-400 mb-8">Última actualización: {{ date('d/m/Y') }}</p>

        <div class="prose prose-gray max-w-none space-y-6 text-sm leading-relaxed">
            <h2 class="text-lg font-bold text-gray-900 mt-8">1. Información que recopilamos</h2>
            <p class="text-gray-600">Recopilamos información que nos proporcionas directamente al registrarte, como nombre, correo electrónico, nombre de la empresa y datos de facturación. También recopilamos datos de uso de la plataforma, como registros de asistencia, ubicaciones GPS autorizadas por los empleados durante el marcaje, y datos técnicos del dispositivo para fines de seguridad y mejora del servicio.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">2. Uso de la información</h2>
            <p class="text-gray-600">Utilizamos tu información para: proveer y mantener el servicio de AsistControl, procesar pagos y facturación, enviar notificaciones relacionadas con el servicio, mejorar y personalizar la experiencia del usuario, y cumplir con obligaciones legales aplicables.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">3. Datos de geolocalización</h2>
            <p class="text-gray-600">La funcionalidad de geolocalización solo se activa en el momento exacto en que un empleado registra su entrada o salida. No rastreamos la ubicación de los empleados en segundo plano ni almacenamos historiales de ubicación continua.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">4. Protección de datos</h2>
            <p class="text-gray-600">Implementamos medidas de seguridad técnicas y organizativas para proteger tus datos contra acceso no autorizado, alteración, divulgación o destrucción. Todas las comunicaciones entre tu navegador y nuestros servidores están encriptadas mediante TLS.</p>

            <h2 class="text-lg font-bold text-gray-900 mt-8">5. Contacto</h2>
            <p class="text-gray-600">Si tienes preguntas sobre esta política, contáctanos en: <a href="mailto:privacidad@asistcontrol.com" class="text-brand-600 hover:underline">privacidad@asistcontrol.com</a></p>
        </div>
    </div>
</main>

<footer class="border-t border-gray-200 py-6 text-center">
    <p class="text-xs text-gray-400">&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
</footer>
</body>
</html>
