<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activa tu cuenta - AsistControl</title>
    <!-- Importación de Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f2; font-family: 'Montserrat', 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748;">

    <!-- Tabla Principal de Fondo -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f2; padding: 40px 12px;">
        <tr>
            <td align="center">

                <!-- Contenedor Principal (Blanco impoluto con toques Verde Cristal) -->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; max-width: 600px; width: 100%; border: 1px solid #e1e9e0; box-shadow: 0 10px 30px rgba(42, 54, 24, 0.08);">

                    <!-- Header: Fondo obscuro con cristal tintado en verde -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2A3618 0%, #546B30 100%); padding: 36px 32px; text-align: center;">
                            <!-- Insignia superior translucida estilo cristal -->
                            <div style="display: inline-block; background-color: rgba(139, 174, 83, 0.25); border: 1px solid rgba(139, 174, 83, 0.5); border-radius: 20px; padding: 5px 16px; margin-bottom: 14px;">
                                <span style="font-size: 11px; font-weight: 700; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">ASISTCONTROL</span>
                            </div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">
                                ¡Bienvenido a AsistControl!
                            </h1>
                            <p style="color: #dbe8cd; margin: 8px 0 0 0; font-size: 13px; font-weight: 500;">
                                Gestión inteligente de asistencia y talento para tu empresa
                            </p>
                        </td>
                    </tr>

                    <!-- Cuerpo del Correo (Fondo Blanco Limpio) -->
                    <tr>
                        <td style="padding: 36px 36px 28px 36px; background-color: #ffffff;">

                            <!-- Saludo Personalizado -->
                            <p style="font-size: 16px; line-height: 1.6; margin: 0 0 16px 0; color: #1e2711; font-weight: 700;">
                                Hola, <span style="color: #546B30;">{{ $nombreCompleto }}</span>
                            </p>

                            <p style="font-size: 14px; line-height: 1.7; color: #4a5568; margin: 0 0 28px 0; font-weight: 400;">
                                Nos alegra integrar a <strong style="color: #1e2711; font-weight: 600;">{{ $nombreEmpresa }}</strong> a nuestra plataforma. Tu cuenta de administrador ha sido creada exitosamente. Para comenzar a gestionar a tus colaboradores y activar la plataforma, confirma tu dirección de correo electrónico a continuación:
                            </p>

                            <!-- Call to Action Principal (Degradado Verde Base a Sombra) -->
                            <div style="text-align: center; margin: 32px 0 20px 0;">
                                <a href="{{ $verificationUrl }}" target="_blank" style="background: linear-gradient(135deg, #546B30 0%, #2A3618 100%); color: #ffffff; padding: 16px 36px; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px; display: inline-block; letter-spacing: 0.5px; text-transform: uppercase; box-shadow: 0 4px 14px rgba(42, 54, 24, 0.25); border: 1px solid #8BAE53;">
                                    Activar mi cuenta de empresa
                                </a>
                            </div>

                            <!-- Expiración / Alerta sutil -->
                            <div style="text-align: center; margin-bottom: 32px;">
                                <span style="font-size: 12px; color: #546B30; font-weight: 600; background-color: #f3f7ef; padding: 6px 16px; border-radius: 20px; border: 1px solid #d4e2c3; display: inline-block;">
                                    Enlace de activación válido por <strong>24 horas</strong>
                                </span>
                            </div>

                            <!-- Separador Elegante -->
                            <div style="height: 1px; background: linear-gradient(90deg, rgba(84,107,48,0.05) 0%, rgba(139,174,83,0.4) 50%, rgba(84,107,48,0.05) 100%); margin: 32px 0;"></div>

                            <!-- Sección Oferta de Servicios / Capacidades de AsistControl -->
                            <h3 style="font-size: 15px; color: #1e2711; margin: 0 0 18px 0; font-weight: 700; letter-spacing: -0.2px;">
                                Lo que podrás gestionar desde tu panel de AsistControl:
                            </h3>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #546B30; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Control Multicanal y Modo Kiosko</strong>
                                        Marcaje mediante app móvil con validación GPS, plataforma web o modalidad Kiosko para estaciones físicas dentro de tus instalaciones.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #8BAE53; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Gestión Multisucursal</strong>
                                        Registra e interconecta múltiples oficinas y centros de trabajo a nivel nacional e internacional sin complicaciones.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #546B30; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Cálculo Automatizado de Nómina y Prestaciones</strong>
                                        Computa incidencias, bonos, horas extra y días de vacaciones de forma configurable según las políticas de tu empresa.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #8BAE53; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Notificaciones Push Instantáneas</strong>
                                        Comunicación directa y avisos en tiempo real directo al dispositivo móvil de tus colaboradores.
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback Enlace -->
                            <div style="margin-top: 32px; background-color: #f8faf7; padding: 16px; border-radius: 8px; border: 1px solid #e2ebd8;">
                                <p style="font-size: 11px; color: #64748b; margin: 0 0 6px 0; font-weight: 500;">
                                    Si el botón de arriba no funciona, copia y pega la siguiente URL en tu navegador:
                                </p>
                                <p style="font-size: 11px; color: #546B30; word-break: break-all; margin: 0; font-family: monospace; font-weight: 600;">
                                    {{ $verificationUrl }}
                                </p>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f3f7ef; padding: 24px 36px; text-align: center; border-top: 1px solid #e1e9e0;">
                            <p style="font-size: 11px; color: #64748b; margin: 0 0 6px 0; line-height: 1.5;">
                                Has recibido este correo porque tu empresa registró una cuenta en <a href="{{ route('landing') }}" target="_blank" style="color: #546B30; text-decoration: underline; font-weight: 600;">AsistControl</a>.
                            </p>

                            <!-- Aclaración de seguridad y borrado automático -->
                            <p style="font-size: 10.5px; color: #788c5d; margin: 0 0 10px 0; line-height: 1.4; font-weight: 500;">
                                Si tú no solicitaste este registro, puedes ignorar este mensaje. Las cuentas no verificadas son eliminadas automáticamente de nuestro sistema tras <strong>30 días</strong>.
                            </p>

                            <p style="font-size: 11px; color: #64748b; margin: 0 0 12px 0;">
                                <a href="{{ route('terminos') }}" target="_blank" style="color: #546B30; text-decoration: underline; font-weight: 600;">Términos y Condiciones</a>
                                &nbsp;&bull;&nbsp;
                                <a href="{{ route('privacidad') }}" target="_blank" style="color: #546B30; text-decoration: underline; font-weight: 600;">Política de Privacidad</a>
                            </p>

                            <!-- Crédito de Desarrollo -->
                            <p style="font-size: 10px; color: #94a3b8; margin: 0; line-height: 1.4; letter-spacing: 0.2px;">
                                Desarrollado por <strong>JALY Systems S.A. DE C.V.</strong> &bull; Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
