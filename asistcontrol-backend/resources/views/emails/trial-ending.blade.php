<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu prueba está por terminar - AsistControl</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f2; font-family: 'Montserrat', 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f2; padding: 40px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; max-width: 600px; width: 100%; border: 1px solid #e1e9e0; box-shadow: 0 10px 30px rgba(42, 54, 24, 0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); padding: 36px 32px; text-align: center;">
                            <div style="display: inline-block; background-color: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; padding: 5px 16px; margin-bottom: 14px;">
                                <span style="font-size: 11px; font-weight: 700; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">ASISTCONTROL</span>
                            </div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">
                                Tu prueba está por terminar
                            </h1>
                            <p style="color: #ffedd5; margin: 8px 0 0 0; font-size: 13px; font-weight: 500;">
                                Mantén el acceso a todas las funciones de tu plan
                            </p>
                        </td>
                    </tr>

                    <!-- Cuerpo -->
                    <tr>
                        <td style="padding: 36px 36px 28px 36px; background-color: #ffffff;">

                            <p style="font-size: 16px; line-height: 1.6; margin: 0 0 16px 0; color: #1e2711; font-weight: 700;">
                                Hola, <span style="color: #ea580c;">{{ $nombreOwner }}</span>
                            </p>

                            <p style="font-size: 14px; line-height: 1.7; color: #4a5568; margin: 0 0 24px 0; font-weight: 400;">
                                El periodo de prueba de <strong style="color: #1e2711; font-weight: 600;">{{ $nombreEmpresa }}</strong> en el plan
                                <strong style="color: #1e2711; font-weight: 600;">{{ $planNombre }}</strong> termina el
                                <strong style="color: #ea580c; font-weight: 700;">{{ $fechaFinTrial }}</strong>.
                            </p>

                            <p style="font-size: 14px; line-height: 1.7; color: #4a5568; margin: 0 0 28px 0; font-weight: 400;">
                                Si deseas seguir disfrutando de las ventajas de tu plan por
                                <strong style="color: #1e2711;">{{ $precio }} al mes</strong>, solo tienes que continuar con el pago.
                                Tu información y la de tus colaboradores se conserva íntegramente.
                            </p>

                            <!-- Call to Action -->
                            <div style="text-align: center; margin: 32px 0 20px 0;">
                                <a href="{{ url('/billing/plans') }}" target="_blank" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: #ffffff; padding: 16px 36px; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px; display: inline-block; letter-spacing: 0.5px; text-transform: uppercase; box-shadow: 0 4px 14px rgba(234, 88, 12, 0.25); border: 1px solid #f97316;">
                                    Continuar con mi plan
                                </a>
                            </div>

                            <!-- Alerta -->
                            <div style="text-align: center; margin-bottom: 32px;">
                                <span style="font-size: 12px; color: #ea580c; font-weight: 600; background-color: #fff7ed; padding: 6px 16px; border-radius: 20px; border: 1px solid #fed7aa; display: inline-block;">
                                    Si no realizas el pago, al día siguiente tu empresa se moverá al <strong>Plan Gratis</strong>
                                </span>
                            </div>

                            <!-- Qué pierdes -->
                            <div style="height: 1px; background: linear-gradient(90deg, rgba(234,88,12,0.05) 0%, rgba(245,158,11,0.4) 50%, rgba(234,88,12,0.05) 100%); margin: 32px 0;"></div>

                            <h3 style="font-size: 15px; color: #1e2711; margin: 0 0 18px 0; font-weight: 700; letter-spacing: -0.2px;">
                                Al continuar con {{ $planNombre }} conservas:
                            </h3>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Todas tus funciones</strong>
                                        Sin límites de usuarios ni oficinas dentro de tu plan.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #fb923c; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Tus datos intactos</strong>
                                        Historial de asistencia, reportes y configuración se mantienen sin cambios.
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback -->
                            <div style="margin-top: 32px; background-color: #fff7ed; padding: 16px; border-radius: 8px; border: 1px solid #fed7aa;">
                                <p style="font-size: 11px; color: #64748b; margin: 0 0 6px 0; font-weight: 500;">
                                    Si el botón de arriba no funciona, abre directamente esta URL en tu navegador:
                                </p>
                                <p style="font-size: 11px; color: #ea580c; word-break: break-all; margin: 0; font-family: monospace; font-weight: 600;">
                                    {{ url('/billing/plans') }}
                                </p>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #fff7ed; padding: 24px 36px; text-align: center; border-top: 1px solid #fed7aa;">
                            <p style="font-size: 11px; color: #64748b; margin: 0 0 6px 0; line-height: 1.5;">
                                Has recibido este correo porque tienes una cuenta en <a href="{{ url('/') }}" target="_blank" style="color: #ea580c; text-decoration: underline; font-weight: 600;">AsistControl</a>.
                            </p>
                            <p style="font-size: 11px; color: #64748b; margin: 0 0 12px 0;">
                                <a href="{{ url('/terminos') }}" target="_blank" style="color: #ea580c; text-decoration: underline; font-weight: 600;">Términos y Condiciones</a>
                                &nbsp;&bull;&nbsp;
                                <a href="{{ url('/privacidad') }}" target="_blank" style="color: #ea580c; text-decoration: underline; font-weight: 600;">Política de Privacidad</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
