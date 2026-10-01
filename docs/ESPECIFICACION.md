# AsistControl — Especificación funcional

SaaS multiempresa para control de asistencia, vacaciones, permisos, pre-nómina y bonos.

## Stack

| Parte | Tecnología |
|---|---|
| Backend / API / Super Admin | Laravel (última versión), PHP 8.3 (Laragon) |
| Base de datos | MySQL 8.4 (Laragon) |
| Panel web + landing | Angular |
| App móvil | Flutter |
| Pagos | Stripe (Laravel Cashier) |
| Push | Firebase Cloud Messaging |
| Correo (desarrollo) | Mailpit (Laragon) |

## Planes y bases de datos

- Se cobra por **empleados activos** (el Owner no cuenta; los usuarios no cuentan).
- Extras: bloques de empleados, oficinas adicionales, usuarios adicionales.
- Días de prueba configurables desde el Super Admin.
- Aislamiento de datos:
  - **Free / Básico**: base de datos compartida (pool `basic`).
  - **Plus**: base de datos compartida solo entre empresas Plus (pool `plus`).
  - **Premium**: base de datos dedicada por empresa.
- Todas las tablas de empresa llevan `company_id` y se filtran siempre por él; la conexión se elige según la empresa.

## Registro

1. Formulario en la landing (empresa + correo maestro).
2. La empresa queda `pending`; se envía un enlace firmado y con caducidad.
3. Al validar el correo se activa la prueba, se crea la oficina y el turno por defecto, y el usuario pasa a **Owner**.

## Roles

| Acción | Owner | Admin (RH) | Gerente | Empleado |
|---|:-:|:-:|:-:|:-:|
| Todos los permisos de la empresa | ✅ | — | — | — |
| Modificar permisos de roles | ✅ | ❌ | ❌ | ❌ |
| Nombrar Admins | ✅ | ❌ | ❌ | ❌ |
| Asignar Gerente / Empleado | ✅ | ✅ | ❌ | ❌ |
| Ver empleados | Todos | Todos (menos Owner) | Sus áreas | Él mismo |
| Aprobar justificaciones / retardos / salidas anticipadas | ✅ | ✅ | ✅ (configurable por Owner) | ❌ |
| Aprobar vacaciones | ✅ | ✅ | ❌ fijo | ❌ |
| Bloquear acceso de usuario | ✅ | ✅ | ❌ | ❌ |
| Notificaciones / noticias | ✅ | ✅ | configurable | ❌ |
| Sueldos, bonos y reglas | ✅ | ✅ | ❌ | ❌ |

- Un solo Owner por empresa. Transferible con confirmación por correo.
- El Super Admin vive en la BD central con otro guard; nunca es asignable desde una empresa.
- Admin y Gerente también son empleados (tienen área, oficina y turno, y registran asistencia).
- Un Gerente pertenece a una área pero puede supervisar varias.
- El Owner decide si los empleados pueden entrar a la web (con límites de su rol) o solo a la app.
- Bloquear acceso revoca sesiones y dispositivos; el registro de empleado se conserva.

## Empleado vs. usuario

- **Empleado**: persona en nómina (asistencia, turno, bonos, credencial). Puede no tener usuario (temporales, operadores).
- **Usuario**: cuenta con login, opcionalmente ligada a un empleado.
- Cada empleado está ligado a **oficina + turno + área** (obligatorios).

## Oficinas y geocerca

- Siempre existe una oficina por defecto (no eliminable si es la última).
- Punto en mapa + radio **de 10 a 100 m** con slider y círculo visible.
- Cada checada guarda coordenadas del empleado, precisión del GPS y distancia a la oficina; el Admin las ve en mapa.
- Excepciones:
  - Home office por días (rango de fechas o días de la semana): no valida geocerca.
  - Home office permanente: nunca valida geocerca.
  - En ambos casos siguen aplicando turno, tolerancia y retardos, y se guardan las coordenadas.

## Turnos

- Cada oficina tiene al menos un turno.
- Hora de entrada / salida, días de la semana activos.
- Reglas: minutos de tolerancia (asistencia), límite de retardo, después falta.
- Soporta turnos nocturnos que cruzan medianoche.

## Registro de asistencia

| Canal | Quién | Verificación |
|---|---|---|
| App biometría | Empleados con usuario | Huella / Face ID / PIN del celular (`local_auth`) + dispositivo registrado (llave en Keystore / Secure Enclave) + geocerca |
| App PIN | Empleados con usuario | PIN del sistema + dispositivo registrado + geocerca |
| Kiosko QR | Cualquier empleado | Credencial con token firmado + foto |
| Kiosko PIN | Cualquier empleado | PIN del sistema + foto |

- El QR es exclusivo del kiosko.
- Credencial imprimible (CR80) con foto, datos y QR; revocable, reimprimible y con vencimiento para temporales.
- Todas las checadas van a una sola tabla con su canal; se ignoran duplicados dentro de pocos minutos.
- El kiosko es un dispositivo autorizado ligado a una oficina.

## Solicitudes

- Justificaciones de retardo/falta, llegadas tarde y salidas anticipadas avisadas con anticipación, vacaciones, permisos.

## Notificaciones

- Destinatarios: toda la empresa, áreas, roles, oficinas o personas.
- Push al celular + bandeja "Notificaciones" en la app.
- Opción de publicar en **Noticias empresa** (tablero del kiosko y sección en la app).
- Enlaces, imágenes, envío programado y acuse de lectura.

## Pre-nómina y bonos (módulos opcionales por empresa)

- Sueldo por empleado, deducciones por faltas.
- Bonos generales o particulares con reglas configurables (ej. puntualidad: menos de 3 retardos no justificados en el periodo).
- La nómina fiscal (ISR, IMSS, CFDI) queda fuera del alcance inicial.
