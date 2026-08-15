<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Activada — AsistControl</title>
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

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f2; padding: 40px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; max-width: 600px; width: 100%; border: 1px solid #e1e9e0; box-shadow: 0 10px 30px rgba(42, 54, 24, 0.08);">

                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); padding: 36px 32px; text-align: center;">
                            <div style="display: inline-block; background-color: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; padding: 5px 16px; margin-bottom: 14px;">
                                <span style="font-size: 11px; font-weight: 700; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">ASISTCONTROL</span>
                            </div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">
                                ¡Cuenta verificada exitosamente!
                            </h1>
                            <p style="color: #c7d2fe; margin: 8px 0 0 0; font-size: 13px; font-weight: 500;">
                                Todo está listo para que comiences a usar la plataforma
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 36px 36px 28px 36px; background-color: #ffffff;">

                            <p style="font-size: 16px; line-height: 1.6; margin: 0 0 16px 0; color: #1e2711; font-weight: 700;">
                                Hola, <span style="color: #4f46e5;">{{ $nombreCompleto }}</span>
                            </p>

                            <p style="font-size: 14px; line-height: 1.7; color: #4a5568; margin: 0 0 28px 0; font-weight: 400;">
                                La cuenta de <strong style="color: #1e2711; font-weight: 600;">{{ $nombreEmpresa }}</strong> ha sido verificada. Ahora tienes acceso completo a <strong style="color: #4f46e5;">AsistControl</strong> para gestionar la asistencia de tu equipo de forma inteligente.
                            </p>

                            <div style="background-color: #f5f3ff; border: 1px solid #e0e7ff; border-radius: 12px; padding: 20px 24px; margin: 0 0 28px 0;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td style="padding-bottom: 10px;">
                                            <span style="font-size: 12px; color: #6366f1; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Tu periodo de prueba</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 6px;">
                                            <span style="font-size: 14px; color: #1e2711; font-weight: 600;">
                                                Disfruta de <strong style="color: #4f46e5; font-size: 22px;">{{ $daysTrial }} días</strong> gratis
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span style="font-size: 12px; color: #64748b;">
                                                con todas las funcionalidades del plan <strong style="color: #4f46e5;">{{ $nombrePlan }}</strong>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div style="text-align: center; margin: 32px 0 20px 0;">
                                <a href="{{ $loginUrl }}" target="_blank" style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); color: #ffffff; padding: 16px 36px; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px; display: inline-block; letter-spacing: 0.5px; text-transform: uppercase; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25); border: 1px solid #6366f1;">
                                    Iniciar sesión en AsistControl
                                </a>
                            </div>

                            <div style="height: 1px; background: linear-gradient(90deg, rgba(79,70,229,0.05) 0%, rgba(99,102,241,0.4) 50%, rgba(79,70,229,0.05) 100%); margin: 32px 0;"></div>

                            <h3 style="font-size: 15px; color: #1e2711; margin: 0 0 18px 0; font-weight: 700; letter-spacing: -0.2px;">
                                Todo lo que puedes hacer con AsistControl:
                            </h3>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #4f46e5; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Control de Asistencia con Geolocalización</strong>
                                        Registro de entrada y salida con validación GPS desde la app móvil para evitar suplantaciones.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #818cf8; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Gestión de Turnos Rotativos</strong>
                                        Configura horarios flexibles y turnos rotativos. Cálculo automático de retardos, horas extra y salidas anticipadas.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #4f46e5; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Reportes de Nómina en Excel/CSV</strong>
                                        Genera pre-nóminas consolidadas listas para importar en tu sistema contable en un solo clic.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #818cf8; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Notificaciones Push y Comunicados</strong>
                                        Envía avisos organizacionales a todo el personal o grupos específicos desde el panel de administración.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #4f46e5; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Permisos, Incidencias y Vacaciones</strong>
                                        Tus colaboradores pueden solicitar permisos, enviar justificantes con foto y consultar su saldo de vacaciones desde la app.
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align: center; margin: 32px 0 0 0;">
                                <p style="font-size: 12px; color: #64748b; margin: 0;">
                                    ¿Tienes dudas? Escríbenos a nuestro
                                    <a href="https://wa.me/{{ config('app.whatsapp_number') }}" target="_blank" style="color: #4f46e5; text-decoration: underline; font-weight: 600;">WhatsApp</a>
                                    o responde este correo.
                                </p>
                            </div>

                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f3f7ef; padding: 24px 36px; text-align: center; border-top: 1px solid #e1e9e0;">
                            <p style="font-size: 11px; color: #64748b; margin: 0 0 6px 0; line-height: 1.5;">
                                Has recibido este correo porque verificaste tu cuenta en <a href="{{ route('landing') }}" target="_blank" style="color: #4f46e5; text-decoration: underline; font-weight: 600;">AsistControl</a>.
                            </p>

                            <p style="font-size: 10.5px; color: #788c5d; margin: 0 0 10px 0; line-height: 1.4; font-weight: 500;">
                                Tu periodo de prueba de <strong>{{ $daysTrial }} días</strong> del plan <strong>{{ $nombrePlan }}</strong> comienza hoy. Sin compromiso ni tarjeta de crédito.
                            </p>

                            <p style="font-size: 11px; color: #64748b; margin: 0 0 12px 0;">
                                <a href="{{ route('terminos') }}" target="_blank" style="color: #4f46e5; text-decoration: underline; font-weight: 600;">Términos y Condiciones</a>
                                &nbsp;&bull;&nbsp;
                                <a href="{{ route('privacidad') }}" target="_blank" style="color: #4f46e5; text-decoration: underline; font-weight: 600;">Política de Privacidad</a>
                            </p>

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
