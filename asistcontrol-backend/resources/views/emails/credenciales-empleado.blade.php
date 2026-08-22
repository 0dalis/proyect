<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tus credenciales de acceso - AsistControl</title>
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
                                ¡Bienvenido a AsistControl!
                            </h1>
                            <p style="color: #c7d2fe; margin: 8px 0 0 0; font-size: 13px; font-weight: 500;">
                                Estas son tus credenciales de acceso a la aplicación
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 36px 36px 28px 36px; background-color: #ffffff;">

                            <p style="font-size: 16px; line-height: 1.6; margin: 0 0 16px 0; color: #1e2711; font-weight: 700;">
                                Hola, <span style="color: #4f46e5;">{{ $nombreCompleto }}</span>
                            </p>

                            <p style="font-size: 14px; line-height: 1.7; color: #4a5568; margin: 0 0 28px 0; font-weight: 400;">
                                Tu empleador ha registrado tu cuenta en <strong style="color: #1e2711; font-weight: 600;">{{ $nombreEmpresa }}</strong> para que puedas registrar tu asistencia. A continuación encontrarás tus datos de acceso:
                            </p>

                            <div style="background-color: #f5f3ff; border: 1px solid #e0e7ff; border-radius: 12px; padding: 20px 24px; margin: 0 0 28px 0;">
                                <p style="font-size: 12px; color: #6366f1; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 16px 0;">Tus credenciales de acceso</p>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 3px;">Código de empresa</span>
                                            <span style="font-size: 14px; color: #1e2711; font-weight: 700; font-family: monospace; background-color: #ede9fe; padding: 3px 10px; border-radius: 4px; letter-spacing: 1px;">{{ $codigoEmpresa }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #e0e7ff;">
                                            <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 3px;">Código de empleado</span>
                                            <span style="font-size: 14px; color: #1e2711; font-weight: 700; font-family: monospace; background-color: #ede9fe; padding: 3px 10px; border-radius: 4px; letter-spacing: 1px;">{{ $employee->employee_code }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #e0e7ff;">
                                            <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 3px;">Correo electrónico</span>
                                            <span style="font-size: 14px; color: #1e2711; font-weight: 600; word-break: break-all;">{{ $user->email }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #e0e7ff;">
                                            <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 3px;">Contraseña</span>
                                            <span style="font-size: 14px; color: #1e2711; font-weight: 700; font-family: monospace; background-color: #ede9fe; padding: 3px 10px; border-radius: 4px; letter-spacing: 1px;">{{ $password }}</span>
                                            <span style="font-size: 11px; color: #6366f1; font-weight: 500; display: block; margin-top: 4px;">Puedes cambiarla en cualquier momento desde tu perfil.</span>
                                        </td>
                                    </tr>
                                </table>

                                <p style="font-size: 12px; color: #64748b; line-height: 1.6; margin: 14px 0 0 0; font-weight: 500; border-top: 1px solid #e0e7ff; padding-top: 12px;">
                                    Necesitarás el <strong style="color: #1e2711;">código de empresa</strong> y tu <strong style="color: #1e2711;">código de empleado</strong> para iniciar sesión desde la aplicación móvil y vincular tu cuenta con tu perfil de empleado.
                                </p>
                            </div>

                            <div style="height: 1px; background: linear-gradient(90deg, rgba(79,70,229,0.05) 0%, rgba(99,102,241,0.4) 50%, rgba(79,70,229,0.05) 100%); margin: 32px 0;"></div>

                            <h3 style="font-size: 15px; color: #1e2711; margin: 0 0 18px 0; font-weight: 700; letter-spacing: -0.2px;">
                                Todo lo que puedes hacer con AsistControl:
                            </h3>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #4f46e5; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Registro de Asistencia con Geolocalización</strong>
                                        Marca tu entrada y salida desde la app móvil con validación GPS en tiempo real.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #818cf8; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Permisos, Incidencias y Vacaciones</strong>
                                        Solicita permisos, envía justificantes con foto y consulta tu saldo de vacaciones.
                                    </td>
                                </tr>
                                <tr><td height="10"></td></tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #334155; background-color: #f8faf7; border-left: 4px solid #4f46e5; border-radius: 0 8px 8px 0;">
                                        <strong style="color: #1e2711; font-weight: 700; display: block; margin-bottom: 2px;">Notificaciones Push en Tiempo Real</strong>
                                        Recibe avisos y comunicados directamente en tu dispositivo móvil.
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color: #f5f3ff; padding: 16px; border-radius: 8px; border: 1px solid #e0e7ff; margin-top: 32px;">
                                <p style="font-size: 11px; color: #64748b; margin: 0; line-height: 1.5; font-weight: 500;">
                                    <strong style="color: #4f46e5;">Nota:</strong> Por seguridad, te recomendamos cambiar tu contraseña la primera vez que inicies sesión.
                                </p>
                            </div>

                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f5f3ff; padding: 24px 36px; text-align: center; border-top: 1px solid #e0e7ff;">
                            <p style="font-size: 11px; color: #64748b; margin: 0 0 6px 0; line-height: 1.5;">
                                Has recibido este correo porque tu empleador te registró en <a href="{{ route('landing') }}" target="_blank" style="color: #4f46e5; text-decoration: underline; font-weight: 600;">AsistControl</a>.
                            </p>

                            <p style="font-size: 10.5px; color: #788c5d; margin: 0 0 10px 0; line-height: 1.4; font-weight: 500;">
                                Si no esperabas este correo, ignóralo o contacta al administrador de tu empresa.
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
