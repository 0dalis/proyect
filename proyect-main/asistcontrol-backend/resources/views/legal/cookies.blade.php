<!DOCTYPE html>
<html lang="es" class="scroll-smooth antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Cookies — AsistControl</title>
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
            Esta política de cookies es temporal y será reemplazada por una versión legal definitiva próximamente.
        </p>

        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">Política de Cookies</h1>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-8">Última actualización: {{ date('d/m/Y') }}</p>

        <div class="space-y-6 text-sm leading-relaxed">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">1. ¿Qué son las cookies?</h2>
                <p class="text-slate-600 dark:text-slate-400">Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas un sitio web o utilizas una aplicación. Nos permiten recordar tus preferencias, mantener tu sesión activa y garantizar el correcto funcionamiento del sistema. Algunas tecnologías equivalentes, como el almacenamiento local del navegador, cumplen funciones similares y se rigen por esta misma política.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">2. Cookies que utilizamos</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">AsistControl utiliza únicamente cookies técnicas y funcionales. No utilizamos cookies publicitarias ni de seguimiento de terceros.</p>

                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left bg-slate-50 dark:bg-slate-900/60">
                                <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Nombre</th>
                                <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Tipo</th>
                                <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Finalidad</th>
                                <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Duración</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-600 dark:text-slate-400">
                            <tr class="border-t border-slate-200 dark:border-slate-800">
                                <td class="px-4 py-3 font-mono">laravel_session</td>
                                <td class="px-4 py-3">Técnica</td>
                                <td class="px-4 py-3">Mantener la sesión autenticada del usuario.</td>
                                <td class="px-4 py-3">Sesión</td>
                            </tr>
                            <tr class="border-t border-slate-200 dark:border-slate-800">
                                <td class="px-4 py-3 font-mono">XSRF-TOKEN</td>
                                <td class="px-4 py-3">Técnica</td>
                                <td class="px-4 py-3">Proteger contra ataques de falsificación de peticiones (CSRF).</td>
                                <td class="px-4 py-3">Sesión</td>
                            </tr>
                            <tr class="border-t border-slate-200 dark:border-slate-800">
                                <td class="px-4 py-3 font-mono">ac_prefs</td>
                                <td class="px-4 py-3">Funcional</td>
                                <td class="px-4 py-3">Recordar tus preferencias de tema, modo (claro/oscuro) y tipografía.</td>
                                <td class="px-4 py-3">1 año</td>
                            </tr>
                            <tr class="border-t border-slate-200 dark:border-slate-800">
                                <td class="px-4 py-3 font-mono">cookie_consent</td>
                                <td class="px-4 py-3">Funcional</td>
                                <td class="px-4 py-3">Registrar que aceptaste el aviso de uso de cookies.</td>
                                <td class="px-4 py-3">1 año</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">3. Cookies técnicas o necesarias</h2>
                <p class="text-slate-600 dark:text-slate-400">Son indispensables para el funcionamiento del sistema. Permiten mantener tu sesión iniciada, proteger la plataforma y garantizar la seguridad de las operaciones. Sin ellas, el servicio no puede prestarse correctamente.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">4. Cookies funcionales</h2>
                <p class="text-slate-600 dark:text-slate-400">Permiten recordar tus preferencias de personalización, como el tema de color, el modo claro u oscuro y la tipografía seleccionada, para que no tengas que configurarlas cada vez que ingreses. También registran la aceptación del aviso de cookies.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">5. Cómo gestionar o deshabilitar las cookies</h2>
                <p class="text-slate-600 dark:text-slate-400">Puedes eliminar o bloquear las cookies desde la configuración de tu navegador. Ten en cuenta que, si deshabilitas las cookies técnicas, es posible que no puedas iniciar sesión ni utilizar el sistema correctamente. Para más información, consulta la ayuda de tu navegador (Chrome, Firefox, Safari, Edge, entre otros).</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">6. Consentimiento</h2>
                <p class="text-slate-600 dark:text-slate-400">Al continuar utilizando AsistControl y aceptar el aviso de cookies, consientes el uso de las cookies descritas en esta política. Puedes retirar tu consentimiento en cualquier momento eliminando las cookies de tu navegador.</p>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-8 mb-3">7. Contacto</h2>
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
