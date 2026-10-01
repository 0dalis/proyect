<!DOCTYPE html>
<html lang="es" class="scroll-smooth antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidad — AsistControl</title>
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
            Esta política de privacidad es temporal y será reemplazada por una versión legal definitiva próximamente.
        </p>

        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">Política de Privacidad</h1>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-8">Última actualización: {{ date('d/m/Y') }}</p>

        <div class="space-y-6 text-sm leading-relaxed">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">1. Información que recopilamos</h2>
                <p class="text-slate-600 dark:text-slate-400">Recopilamos información que nos proporcionas directamente al registrarte, como nombre, correo electrónico, nombre de la empresa y datos de facturación. También recopilamos datos de uso de la plataforma, como registros de asistencia, ubicaciones GPS autorizadas por los empleados durante el marcaje, y datos técnicos del dispositivo para fines de seguridad y mejora del servicio.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">2. Uso de la información</h2>
                <p class="text-slate-600 dark:text-slate-400">Utilizamos tu información para: proveer y mantener el servicio de AsistControl, procesar pagos y facturación, enviar notificaciones relacionadas con el servicio, mejorar y personalizar la experiencia del usuario, y cumplir con obligaciones legales aplicables.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">3. Datos de geolocalización</h2>
                <p class="text-slate-600 dark:text-slate-400">La funcionalidad de geolocalización solo se activa en el momento exacto en que un empleado registra su entrada o salida. No rastreamos la ubicación de los empleados en segundo plano ni almacenamos historiales de ubicación continua.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">4. Protección de datos</h2>
                <p class="text-slate-600 dark:text-slate-400">Implementamos medidas de seguridad técnicas y organizativas para proteger tus datos contra acceso no autorizado, alteración, divulgación o destrucción. Todas las comunicaciones entre tu navegador y nuestros servidores están encriptadas mediante TLS.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">5. Contacto</h2>
                <p class="text-slate-600 dark:text-slate-400">Si tienes preguntas sobre esta política, contáctanos en: <a href="mailto:privacidad@asistcontrol.com" class="text-brand-600 dark:text-brand-400 hover:underline">privacidad@asistcontrol.com</a></p>
            </div>
        </div>
    </div>
</main>

<footer class="border-t border-slate-200 dark:border-slate-800/80 py-8 text-center transition-colors duration-300">
    <p class="text-xs text-slate-400 dark:text-slate-500">&copy; {{ date('Y') }} JALY SYSTEMS. Todos los derechos reservados.</p>
</footer>
</body>
</html>
