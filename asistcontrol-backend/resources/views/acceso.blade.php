<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso — AsistControl</title>
    <meta name="description" content="Contacta a AsistControl o registra tu empresa y comienza tu prueba gratis de 14 días.">
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
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; }

        .acceso-container {
            position: relative;
            width: 100%;
            max-width: 960px;
            min-height: 620px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .form-panel {
            position: absolute;
            top: 0;
            height: 100%;
            width: 50%;
            transition: transform 0.6s ease-in-out, opacity 0.3s ease-in-out;
        }

        .panel-contacto {
            left: 0;
            z-index: 2;
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
            transition: transform 0.6s ease-in-out;
        }

        .overlay-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 50%, #1e3a8a 100%);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
            color: white;
            transition: transform 0.6s ease-in-out;
        }

        .overlay-content-left,
        .overlay-content-right {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
            transition: opacity 0.3s ease-in-out, transform 0.6s ease-in-out;
        }

        .overlay-content-left {
            transform: translateX(-20%);
            opacity: 0;
        }

        .overlay-content-right {
            transform: translateX(0);
            opacity: 1;
        }

        /* ESTADO ACTIVO (Registro visible) */
        .right-panel-active .panel-contacto {
            transform: translateX(100%);
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
        }

        .right-panel-active .overlay-content-right {
            transform: translateX(20%);
            opacity: 0;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
            background: #fff;
        }

        .ghost-btn {
            background: transparent;
            border: 2px solid rgba(255,255,255,0.7);
            color: white;
            padding: 0.75rem 2.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.025em;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
        }
        .ghost-btn:hover {
            background: rgba(255,255,255,0.15);
            border-color: white;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-brand-50 min-h-screen flex items-center justify-center p-4 font-sans">

<div class="w-full flex flex-col items-center gap-4">
    <a href="{{ route('landing') }}" class="flex items-center gap-2 mb-2">
        <div class="w-9 h-9 bg-brand-600 rounded-xl flex items-center justify-center shadow-lg shadow-brand-200">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <span class="text-xl font-bold text-gray-900 tracking-tight">AsistControl</span>
    </a>

    <div class="acceso-container" id="accesoContainer">
        <!-- ===== PANEL CONTACTO (izquierda) ===== -->
        <div class="form-panel panel-contacto flex items-center">
            <form id="contactoForm" class="w-full px-10 py-8 space-y-4">
                <div class="mb-6">
                    <h2 class="text-2xl font-black text-gray-900">Contáctanos</h2>
                    <p class="text-sm text-gray-500 mt-1">Déjanos tus datos y te respondemos en menos de 24h</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nombre completo</label>
                    <input type="text" name="nombre" required class="form-input" placeholder="Juan Pérez">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Empresa</label>
                    <input type="text" name="empresa" required class="form-input" placeholder="Mi Empresa S.A.">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Correo electrónico</label>
                    <input type="email" name="email" required class="form-input" placeholder="juan@empresa.com">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Teléfono</label>
                    <input type="tel" name="telefono" class="form-input" placeholder="+52 55 1234 5678">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Plan de interés</label>
                    <select name="plan_interes" class="form-input">
                        <option value="">Selecciona un plan</option>
                        @foreach($planes as $plan)
                            <option value="{{ $plan->nombre }}">{{ $plan->nombre }} — ${{ number_format($plan->precio, 2) }}/mes</option>
                        @endforeach
                        <option value="No estoy seguro">No estoy seguro, necesito asesoría</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Mensaje</label>
                    <textarea name="mensaje" required rows="3" class="form-input resize-none" placeholder="Cuéntanos sobre tu empresa..."></textarea>
                </div>
                <div id="contactoMsg" class="hidden text-sm rounded-xl p-3"></div>
                <button type="submit" class="w-full py-3 bg-brand-600 text-white font-semibold rounded-xl hover:bg-brand-700 transition-all shadow-lg shadow-brand-200 hover:shadow-xl active:scale-[0.98]">
                    Enviar mensaje
                </button>
            </form>
        </div>

        <!-- ===== PANEL REGISTRO (derecha) ===== -->
        <div class="form-panel panel-registro flex items-center">
            <form id="registroForm" class="w-full px-10 py-8 space-y-3.5">
                <div class="mb-4">
                    <h2 class="text-2xl font-black text-gray-900">Crear cuenta</h2>
                    <p class="text-sm text-gray-500 mt-1">14 días de prueba gratis. Sin tarjeta.</p>
                </div>
                <div class="bg-brand-50 border border-brand-100 rounded-xl p-3 flex items-start gap-2">
                    <svg class="w-4 h-4 text-brand-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-brand-800">Obtienes <strong>14 días de prueba gratis</strong> con acceso completo a todas las funcionalidades.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nombre de la empresa</label>
                    <input type="text" name="nombre_empresa" required class="form-input" placeholder="Mi Empresa S.A. de C.V.">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tu nombre</label>
                        <input type="text" name="nombre" required class="form-input" placeholder="Juan">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tu apellido</label>
                        <input type="text" name="apellido" required class="form-input" placeholder="Pérez">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Correo electrónico</label>
                        <input type="email" name="email" required class="form-input" placeholder="juan@empresa.com">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Teléfono</label>
                        <input type="tel" name="telefono" class="form-input" placeholder="+52 55 1234 5678">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Contraseña</label>
                        <input type="password" name="password" required minlength="8" class="form-input" placeholder="Mínimo 8 caracteres">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Confirmar</label>
                        <input type="password" name="password_confirmation" required minlength="8" class="form-input" placeholder="Repite tu contraseña">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Plan</label>
                    <select name="plan_id" class="form-input">
                        <option value="">Probar sin plan (elige después)</option>
                        @foreach($planes as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->nombre }} — ${{ number_format($plan->precio, 2) }}/mes</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-start gap-2">
                    <input type="checkbox" name="terminos" required class="mt-0.5 w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                    <label class="text-xs text-gray-500">Acepto los <a href="{{ route('terminos') }}" target="_blank" class="text-brand-600 hover:underline">Términos</a> y la <a href="{{ route('privacidad') }}" target="_blank" class="text-brand-600 hover:underline">Política de Privacidad</a>.</label>
                </div>
                <div id="registroMsg" class="hidden text-sm rounded-xl p-3"></div>
                <button type="submit" class="w-full py-3 bg-brand-600 text-white font-semibold rounded-xl hover:bg-brand-700 transition-all shadow-lg shadow-brand-200 hover:shadow-xl active:scale-[0.98]">
                    Crear cuenta gratis
                </button>
            </form>
        </div>

        <!-- ===== OVERLAY PANEL ===== -->
        <div class="overlay-panel">
            <div class="overlay-bg"></div>
            <div class="overlay-content">
                <div class="overlay-content-right">
                    <svg class="w-12 h-12 text-white/40 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <h2 class="text-2xl font-black mb-3">¿Listo para empezar?</h2>
                    <p class="text-sm text-white/70 mb-8 leading-relaxed">
                        Crea tu cuenta empresarial y comienza a usar AsistControl hoy mismo. 14 días de prueba sin compromiso.
                    </p>
                    <button id="toRegistro" class="ghost-btn">Registrarme</button>
                </div>
                <div class="overlay-content-left">
                    <svg class="w-12 h-12 text-white/40 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h2 class="text-2xl font-black mb-3">¿Solo tienes una duda?</h2>
                    <p class="text-sm text-white/70 mb-8 leading-relaxed">
                        Ponte en contacto con nuestro equipo. Un asesor te responderá en menos de 24 horas.
                    </p>
                    <button id="toContacto" class="ghost-btn">Contactar</button>
                </div>
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">
        <a href="{{ route('landing') }}" class="hover:text-brand-600 transition-colors">← Volver al inicio</a>
    </p>
</div>

<script>
const container = document.getElementById('accesoContainer');

document.getElementById('toRegistro').addEventListener('click', () => {
    container.classList.add('right-panel-active');
});

document.getElementById('toContacto').addEventListener('click', () => {
    container.classList.remove('right-panel-active');
});

// ===== AJAX CONTACTO =====
document.getElementById('contactoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const msg = document.getElementById('contactoMsg');
    btn.disabled = true;
    btn.textContent = 'Enviando...';

    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        const res = await fetch('{{ route("landing.contacto") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        msg.className = 'text-sm rounded-xl p-3 ' + (json.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200');
        msg.classList.remove('hidden');
        msg.textContent = json.message;
        if (json.success) this.reset();
    } catch(err) {
        msg.className = 'text-sm rounded-xl p-3 bg-red-50 text-red-700 border border-red-200';
        msg.classList.remove('hidden');
        msg.textContent = 'Error al enviar. Intenta de nuevo.';
    }
    btn.disabled = false;
    btn.textContent = 'Enviar mensaje';
});

// ===== AJAX REGISTRO =====
document.getElementById('registroForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const msg = document.getElementById('registroMsg');
    btn.disabled = true;
    btn.textContent = 'Creando cuenta...';

    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        const res = await fetch('{{ route("landing.registro") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        msg.className = 'text-sm rounded-xl p-3 ' + (json.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200');
        msg.classList.remove('hidden');
        msg.textContent = json.message;
        if (json.success) this.reset();
    } catch(err) {
        msg.className = 'text-sm rounded-xl p-3 bg-red-50 text-red-700 border border-red-200';
        msg.classList.remove('hidden');
        msg.textContent = 'Error al registrar. Intenta de nuevo.';
    }
    btn.disabled = false;
    btn.textContent = 'Crear cuenta gratis';
});
</script>
</body>
</html>
