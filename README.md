# AsistControl

SaaS de control de asistencia, vacaciones, permisos, pre-nómina y bonos. La especificación funcional está en [`docs/ESPECIFICACION.md`](docs/ESPECIFICACION.md).

| Carpeta | Qué es |
|---|---|
| `backend/` | Laravel 13 (API + panel Super Admin con Filament 5) |
| `frontend/` | Angular 21 (landing, registro, panel de empresa, modo kiosko) |
| `bin/` | Atajos para usar el PHP 8.3 de Laragon sin tocar el PHP global |

## Requisitos

- Laragon con PHP 8.3, MySQL 8.4 y Mailpit encendidos.
- Node 22.

El PHP global (8.1) no se usa. `bin/php.cmd` y `bin/composer.cmd` llaman al PHP de Laragon y activan `zip` solo para este proyecto.

## Arranque

```bash
# Backend (desde backend/)
cp .env.example .env              # solo la primera vez
../bin/composer.cmd install
../bin/php.cmd artisan key:generate
../bin/php.cmd artisan migrate:fresh --seed   # imprime el token del kiosko demo
../bin/php.cmd artisan serve --port=8010

# Frontend (desde frontend/)
npx -y npm@11 install             # el npm 10.9.2 tiene un bug al instalar
npx ng serve --port 4200
```

`migrate:fresh` no borra las bases de empresa (`asist_pool_*`, `asist_tenant_*`). Para reiniciar la demo, bórralas antes desde HeidiSQL.

El seeder crea, además de la empresa demo, 10 empresas de ejemplo en distintos planes y estados, con 14 a 30 días de checadas, para que el tablero del Super Admin tenga datos. Tarda unos 3 minutos.

Si cambias `resources/css/filament/superadmin/theme.css` (tema del Super Admin), recompílalo con `npx vite build` desde `backend/`.

### Tareas programadas

Las bajas, los cobros vencidos y la limpieza de registros corren con el programador de Laravel. En desarrollo déjalo abierto en otra terminal; en producción, agrega el cron `* * * * * php artisan schedule:run`.

```bash
../bin/php.cmd artisan schedule:work
```

| Comando | Cada | Qué hace |
|---|---|---|
| `billing:downgrade-overdue` | hora | Pasa a Free las empresas con el pago vencido desde hace 3 días |
| `companies:purge-deleted` | hora | Borra por completo las empresas que pidieron su baja hace 30 días |
| `accounts:prune-unverified` | día | Elimina los registros que no confirmaron su correo en 30 días |
| `billing:trial-reminders` | hora | Avisa al dueño 3 días antes del primer cobro y el día del cobro |
| `telescope:prune --hours=48` | día | Limpia Telescope |

### Claves en `backend/.env`

- **reCAPTCHA v3** (registro, login y confirmación de cuenta): `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY`, de https://www.google.com/recaptcha/admin. Da de alta el tipo "puntuación (v3)" con los dominios `localhost` y el de producción. Angular toma la clave del sitio de `GET /api/config`. Sin claves, el captcha se omite en local, pero en producción se rechaza todo.
- **Stripe:** `STRIPE_KEY`, `STRIPE_SECRET` y `STRIPE_WEBHOOK_SECRET`. Después corre `../bin/php.cmd artisan plans:sync-stripe`, que crea en Stripe el producto y los precios mensual y anual (11 meses) de cada plan. Configura el webhook hacia `{APP_URL}/stripe/webhook` con los eventos `invoice.paid`, `invoice.payment_failed` y `customer.subscription.*`. Sin claves, en local el modal de planes funciona en "modo demo": no pide tarjeta ni cobra.
- **Soporte:** `SUPPORT_EMAIL` aparece en los correos de baja de empresa.

## Panel del Super Admin

Está en `/intern/web/services/1` (la misma ruta que en la versión anterior), con su propio usuario y guard (`super_admin`).

- **Verificación en dos pasos (opcional):** cada Super Admin la activa en **Perfil** → *Autenticación con app* escaneando el QR con Google Authenticator, Microsoft Authenticator, Authy o 1Password, y guarda sus códigos de recuperación. Desde ese momento se la pide en cada inicio de sesión. No usa servicios externos ni claves de API: el código se calcula en el celular (TOTP) y el secreto se guarda cifrado con `APP_KEY`. En **Ajustes → Verificación en dos pasos** se puede exigir a todos; quien no la tenga solo podrá entrar a su Perfil hasta configurarla. Para encenderla, primero debes activarla en tu propio Perfil.
- **Tablero** (mismo estilo que el sistema anterior):
  - Cinco tarjetas con icono: empresas (activas | inactivas), usuarios (activos en 30 días), empleados (checadas de hoy), oficinas (kioskos) y áreas.
  - Cinco tarjetas de suscripción: activa, en prueba, por vencer (pruebas en 7 días), pago vencido y sin plan de pago (Free o sin elegir plan).
  - Gráficas (Chart.js): pasteles de ingresos mensuales estimados por plan (con el ingreso en riesgo y lo que sumarían las pruebas), empresas por plan y empresas por estado; líneas de crecimiento de empresas y usuarios en 12 meses y de checadas por día.
  - Distribución de planes, detalle de las últimas 15 empresas (usuarios, empleados contra el límite, oficinas, suscripción y estado), opiniones de los clientes, bases de datos y salud del servidor.
- **Empresas:** además del plan y el estado, muestra usuarios, empleados (contra el límite) y oficinas de cada empresa.
- **Bitácora:** todo lo que hace un Super Admin: inicios de sesión, 2FA, ediciones de empresas, pruebas extendidas, suspensiones y reactivaciones, reenvíos de exportación, cambios a planes, ajustes y Super Admins, y opiniones publicadas u ocultas. Se filtra por acción, Super Admin, empresa y fecha; nadie puede editarla ni borrarla.
- **Opiniones:** el dueño y los administradores califican AsistControl de 1 a 5 estrellas, con medias (por ejemplo 4.5), y un comentario opcional. El modal aparece una sola vez tras unos segundos en el panel ("Ahora no" lo pospone 14 días) y siempre está en el menú del usuario → *Calificar AsistControl*. En el tablero y en **Opiniones** eliges cuáles se muestran en la landing; solo se pueden publicar las que el usuario autorizó y traen comentario. En la página aparecen el nombre, la inicial del apellido, el rol y la empresa. Si el usuario cambia su opinión, vuelve a revisión.
- **Roles y permisos:** matriz de lo que trae cada rol al crear una empresa (y lo que nunca se le puede dar), usuarios por rol en toda la plataforma, empresas que personalizaron sus roles y los permisos actuales de los roles de cualquier empresa. Es de solo lectura; el dueño ajusta los de su empresa desde su panel.

## Accesos de la demo

Todas las contraseñas son `password`.

| Dónde | Usuario | Rol |
|---|---|---|
| http://localhost:8010/intern/web/services/1 | superadmin@asistcontrol.test | Super Admin |
| http://localhost:4200/login | owner@demo.test | Dueño |
| http://localhost:4200/login | rh@demo.test | Administrador (RH) |
| http://localhost:4200/login | gerente@demo.test | Gerente de Ventas |
| http://localhost:4200/login | empleado@demo.test | Empleado |

- **Otras empresas de ejemplo:** `owner0@empresas.test` … `owner8@empresas.test`.
- **Kiosko:** abre http://localhost:4200/kiosko y pega el token que imprimió el seeder, o crea uno en Panel → Kioskos.
- **Código de la empresa demo:** `DEMO2026`. La app entra con correo, este código y contraseña.
- **PIN de los empleados demo:** `123456`. Los números de empleado van del `00001` al `00007`. Todos los PIN son de 6 dígitos, también en el kiosko.
- **Correos** (como la verificación del registro): http://localhost:8025 (Mailpit).
- **Telescope** (peticiones, consultas, errores, correos): http://localhost:8010/telescope. Solo entra un Super Admin. Si no has iniciado sesión, te lleva al login de `/intern/web/services/1`; los usuarios de empresa, incluido el dueño, no tienen acceso.

  En producción déjalo apagado (`TELESCOPE_ENABLED=false`) y enciéndelo solo para diagnosticar. Los registros se borran a las 48 horas (`telescope:prune`, en `routes/console.php`). Contraseñas, PIN, cookies y tokens nunca se guardan.

## Bases de datos por plan

| Plan | Base |
|---|---|
| Free / Básico | `asist_pool_basic` (compartida) |
| Plus | `asist_pool_plus` (compartida solo entre Plus) |
| Premium | `asist_tenant_{id}` (dedicada) |

Los usuarios, roles, planes y suscripciones viven en `asist_central`. Todo lo demás vive en la base de la empresa y siempre se filtra por `company_id`.

Si agregas migraciones en `database/migrations/tenant`, córrelas en todas las bases con:

```bash
../bin/php.cmd artisan tenants:migrate
```

## Estructura del frontend (Angular)

```
src/app/
├── core/
│   ├── models/          *.model.ts: interfaces por dominio (index.ts las reexporta)
│   ├── services/        *.service.ts: un servicio por dominio; solo ellos hablan con la API
│   ├── guards/          authGuard, guestGuard y accessGuard (permiso, rol, módulo)
│   ├── interceptors/    token + canal web, y cierre de sesión ante 401 / cuenta bloqueada
│   └── utils/           mensajes de error de Laravel
├── layout/
│   ├── panel-layout/    compone menú lateral + menú superior + <router-outlet>
│   ├── sidebar/         menú lateral (usa navigation.ts)
│   ├── topbar/          ubicación, campana de avisos y menú del usuario
│   └── navigation.ts    enlaces del menú con las mismas reglas de acceso que las rutas
├── pages/
│   ├── landing/  auth/login  auth/register  kiosk/
│   └── panel/           una carpeta por pantalla + panel.routes.ts
└── shared/
    ├── components/      app-modal, app-page-header, app-status-chart, app-geofence-map, app-period-picker
    ├── constants/       etiquetas en español (estados, canales, días)
    └── utils/           periodos de quincena y mes
```

Cada componente tiene su `.component.ts`, `.html` y `.scss`, y `ng generate` ya está configurado para crearlos así. Los estilos son Tailwind: las clases compartidas (`glass`, `card`, `btn`, `badge`, `field`, …) están en `src/styles.css`.

La URL de la API se configura en `src/environments/environment*.ts`.

### Notificaciones y confirmaciones

**`ToastService`** (`core/services/toast.service.ts`) muestra avisos que no piden decisión. El contenedor `<app-toast-container>` ya está en `AppComponent`.

```ts
private readonly toast = inject(ToastService);

this.toast.success('Empleado guardado');
this.toast.error(errorMessage(error), { title: 'No se guardó' });
this.toast.info('Checada registrada', {
  position: 'bottom-center',   // top-start | top-center | top-end | middle-start | middle-end
                               // bottom-start | bottom-center | bottom-end (el centro no existe)
  display: 'icon',             // auto (icono en pantallas chicas) | full | icon
  icon: 'fingerprint',         // cualquier nombre de Bootstrap Icons
  duration: 0,                 // 0 = hasta que la cierren
});
```

**`DialogService`** (`core/services/dialog.service.ts`) usa SweetAlert2 para lo que pide una decisión:

```ts
if (await this.dialog.confirm({ title: '¿Bloquear a Ana?', confirmText: 'Bloquear', variant: 'danger' })) { … }
const motivo = await this.dialog.prompt({ title: 'Rechazar solicitud', inputLabel: 'Motivo' }); // null = canceló
```

Los iconos son de [Bootstrap Icons](https://icons.getbootstrap.com): `<i class="bi bi-people"></i>`.

La paleta corporativa está en `src/styles.css` como roles (`primary`, `ink`, `muted`, `canvas`, `success`, `warning`, `danger`, `info`). Úsalos como utilidades de Tailwind: `bg-primary`, `text-ink`, `text-danger-text`.

Para agregar una pantalla al panel:
1. `npx ng g c pages/panel/mi-pantalla`.
2. Agrégala a `pages/panel/panel.routes.ts`, con `data.access` si requiere permiso.
3. Agrega el enlace en `layout/navigation.ts` con las mismas reglas.

## Rutas de la API (backend)

`routes/api.php` solo carga dos archivos; todo queda bajo `/api`:

```
routes/
├── api.php                  carga web/rutasweb.php y mobile/rutasmobile.php
├── web/
│   ├── rutasweb.php         arma los grupos (públicas, kiosko, protegidas) y carga cada módulo
│   ├── publico.php          planes, registro, verificación de correo, login
│   ├── kiosko.php           pantalla del kiosko (token del dispositivo)
│   ├── sesion.php           logout y "mi perfil"
│   ├── inicio.php           tablero
│   ├── empleados.php        lista, detalle, estadísticas, reporte PDF, alta/edición, credenciales
│   ├── historico.php        bitácora general y por empleado
│   ├── asistencia.php       consulta, registro manual, justificar
│   ├── solicitudes.php      permisos, vacaciones, justificaciones
│   ├── avisos.php           bandeja, noticias, enviar avisos
│   ├── reportes.php         reporte de asistencia
│   ├── nomina.php           pre-nómina y bonos
│   ├── organizacion.php     oficinas, turnos, áreas
│   ├── usuarios.php         usuarios, roles, permisos, celulares
│   ├── kioskos.php          alta de kioskos
│   └── empresa.php          configuración y suscripción
└── mobile/
    └── rutasmobile.php      solo app: registrar celular y checar
```

Cada archivo documenta arriba qué permiso exige. Las rutas protegidas pasan por `auth:sanctum` y `tenant`, y además por `can:`, con permisos del rol (`employees.view`…) o reglas propias: `owner`, `admin-or-owner` y `review-requests` (en `AppServiceProvider`). La prueba `RoutePermissionsTest` recorre cada ruta con los cuatro roles y verifica que quien no tiene permiso reciba 403.

Para agregar un módulo nuevo:
1. Crea `routes/web/mi-modulo.php` con su `can:`.
2. Regístralo en `rutasweb.php` con `Route::name('mi-modulo.')->group(__DIR__.'/mi-modulo.php')`.
3. Agrega sus casos a `RoutePermissionsTest`.

## Seguridad entre Angular y Laravel

- **Panel web:** sesión de Sanctum en una cookie `HttpOnly` (`asistcontrol-session`) más token CSRF (`XSRF-TOKEN` → cabecera `X-XSRF-TOKEN`). No hay token en `localStorage`.
- **Mismo origen:** `ng serve` reenvía `/api` y `/sanctum` a Laravel (`frontend/proxy.conf.json`). En producción, sirve ambos bajo el mismo dominio con un proxy inverso.
- **Web vs. app:** el login web solo abre sesión si viene del panel (`SANCTUM_STATEFUL_DOMAINS`). La app móvil recibe un token con la habilidad `app`, y el kiosko usa su propio token.
- **CORS:** solo acepta los orígenes de `CORS_ALLOWED_ORIGINS`. Además, las respuestas llevan cabeceras de seguridad (`nosniff`, `DENY`, CSP y `no-store`).

### Sesión por inactividad (2 horas)

- **Laravel:** la sesión dura `SESSION_LIFETIME=120` minutos sin peticiones, y cada petición renueva el plazo. Al vencer, cualquier ruta de `/api` responde `401` con `{"code": "session_expired"}` (`bootstrap/app.php`).
- **Angular:** `IdleService` (`core/services/idle.service.ts`) cuenta como actividad el mouse, el teclado, el scroll, los toques y la navegación entre vistas.
  - Si el usuario sigue activo pero no hace peticiones, llama a `GET /api/session/ping` cada 5 minutos como máximo, para que la cookie no venza.
  - 5 minutos antes del cierre muestra un aviso.
  - A las 2 horas cierra la sesión en Laravel, limpia la de Angular y lleva al login.
  - Las pestañas abiertas comparten la actividad y se cierran juntas.
- **Respaldo:** si la cookie vence por otra vía, el interceptor `session.interceptor.ts` recibe el 401, limpia la sesión y lleva al login.

Para cambiar el plazo, cambia `SESSION_LIFETIME` en `backend/.env` y `session.idleMinutes` en `frontend/src/environments/environment*.ts`. Deben coincidir.

## Alta de empresa, planes y cobros

1. **Registro** (`/registro`): nombre de la empresa, del dueño, correo y contraseña, con reCAPTCHA. No pide plan; la empresa queda en Free hasta elegir.
2. **Confirmación** (`/verificar-cuenta/...`, enlace del correo, 24 h):
   - Si la cuenta ya está activa, lleva al login con el aviso.
   - Si el enlace venció, Laravel envía otro y la página pide usar el más reciente.
   - Si no, muestra el botón "Confirmar mi cuenta", con reCAPTCHA.
3. **Primera vez del dueño:** un modal que no se puede cerrar. Mientras tanto, Laravel solo acepta las rutas de `routes/web/onboarding.php`.
   1. Autoriza el uso y confirma que leyó el aviso de privacidad y los términos.
   2. Elige uno de los 4 planes, mensual o anual (el anual cobra 11 meses), y registra la tarjeta con Stripe (Payment Element; la tarjeta nunca pasa por Laravel).
   3. Ve la confirmación: la prueba empezó y el primer cobro es un día antes de que termine. Free no tiene prueba ni pre-nómina.
4. **Cobro fallido** (webhook de Stripe `invoice.payment_failed`): la empresa pasa a "Pago vencido", el dueño recibe un correo y el panel muestra un aviso. Si a los 3 días sigue sin pagarse, `billing:downgrade-overdue` la pasa a Free y se lo avisa por correo.
5. **Un solo límite: empleados.** Cada empleado activo puede tener, o no, usuario para la app, sin costo aparte. Así, los usuarios nunca superan el límite de empleados del plan.
   - En el alta o en la ficha del empleado, el interruptor "Acceso a la app" solo pide su correo. El empleado recibe el **código de empresa** (único, de 8 caracteres, generado al crear la empresa; se ve en Configuración) y una **contraseña temporal**.
   - La app entra con correo, código de empresa y contraseña. En el primer acceso (app o web), el empleado debe cambiar la contraseña y crear su **PIN de 6 dígitos**. Hasta hacerlo, Laravel responde `password_change_required` a todo lo demás.
   - Sin app, el empleado checa y consulta sus últimos 14 días en el kiosko con su número y su PIN, o con su credencial QR. También sirve si tiene la app pero se quedó sin batería.
6. **Límite del plan:** si hay más empleados activos de los que permite el plan, solo los primeros (por fecha de alta) funcionan. El resto aparece en las listas con el candado "Bloqueado por plan": no checa, no entra y no se puede modificar (middleware `seats` y `PlanSeats`). Se desbloquean al mejorar el plan desde Suscripción.

Al cambiar de plan, la empresa se queda en su base de datos actual. Mover datos entre bases (por ejemplo, de Básico a Premium) sigue pendiente.

## Eliminar una empresa

El dueño lo pide en Mi perfil → "Eliminar perfil de empresa de AsistControl", con su contraseña, el motivo y la frase `eliminar datos de {empresa}`.

- **Al instante:**
  - Se cierra el acceso de todos: sesiones, tokens de la app y kioskos.
  - Se cancela Stripe.
  - Se genera un ZIP con CSV (empleados, histórico de asistencia, solicitudes, áreas, oficinas y turnos).
  - El dueño recibe un enlace de descarga que dura **48 horas**.
- **Durante 30 días:** en Super Admin → **Eliminaciones → En proceso** se ven los días restantes y hay un botón para **reenviar el enlace** si venció.
- **Día 30 (`companies:purge-deleted`):**
  - Borrado total, no lógico. En una base dedicada se elimina la base completa; en un pool compartido, solo las filas de esa empresa.
  - Se borran también los usuarios, los roles, las fotos del kiosko y la exportación.
  - El dueño recibe el último correo, con un enlace único para valorar AsistControl (`/valoracion/{token}`).
- **Queda en "Eliminadas":** nombre de la empresa, último plan, tiempo en el sistema (del registro a la solicitud), motivo y valoración, sin datos personales. También se guarda el id del cliente en Stripe, por las facturas.

## Horas y zonas horarias

- **Se guarda en UTC:** Laravel (`APP_TIMEZONE=UTC`) y MySQL (`timezone => '+00:00'` en `config/database.php`) guardan cada checada como un instante UTC.
- **Se muestra en la zona de la oficina:** cada oficina tiene su zona (Mi empresa → Oficinas), y la de la empresa es la predeterminada para las oficinas nuevas.
  - El turno de 9:00 significa 9:00 de esa oficina: una checada a las 9:00 en Cancún (UTC−5) y otra a las 9:00 en CDMX (UTC−6) son instantes distintos, pero ambas llegan a tiempo.
  - Yucatán y Campeche usan la misma hora que CDMX; Quintana Roo va una hora adelante.
- **En Angular:** el pipe `tzDate` (`shared/pipes/tz-date.pipe.ts`) muestra cada hora con la zona de su oficina, sin importar dónde esté quien la consulte. La API manda `recorded_at` en UTC, y además `local_time` y `timezone`.
- **"Hoy" también es el de la oficina:** para saber si alguien faltó o si el día ya terminó.

## Tiempo extra, festivos y vacaciones

- **Horario especial por día:** un turno puede tener días activos con otro horario (Turnos y áreas → "Agregar día especial"). Por ejemplo, lunes a jueves de 9 a 18 y viernes de 9 a 17: el viernes, salir a las 17:00 es "a tiempo" y el tiempo extra empieza a contar a esa hora. Se guarda en `shifts.day_schedules`.
- **Sueldo por día:** el sueldo en México es por día, así que no se calculan horas trabajadas. Llegar antes no cuenta; solo cuenta el tiempo extra.
- **Tiempo extra:** lo trabajado después del fin del turno, en bloques completos de 30 minutos (`tenancy.overtime_block_minutes`). En la pre-nómina, las primeras 9 horas de la semana se pagan al doble y las demás al triple (arts. 67 y 68 LFT).
- **Días festivos:** se administran en Días festivos, donde un botón carga los descansos obligatorios del art. 74 LFT del año. Un festivo sin checada no cuenta como falta; uno trabajado se paga doble adicional (art. 75).
- **Vacaciones:** van por año de servicio (art. 76, reforma 2023): 12 días al cumplir el primer año, 2 más por año hasta 20 en el quinto, y después 2 más cada 5 años.
  - Se cuentan solo los días laborables del turno, sin festivos. Las solicitudes pendientes apartan días.
  - No se puede pedir más de lo disponible, y antes del primer aniversario no hay días.
  - El saldo se ve en la ficha del empleado y al pedir vacaciones.
- **Periodicidad del sueldo:** diario, semanal, quincenal o mensual. El valor de la hora extra es el sueldo diario entre las horas de la jornada (salida − entrada − comida, promedio de la semana).

## Periodos de pre-nómina cerrados

- **Cerrar:** "Cerrar periodo" guarda el cálculo de cada empleado (`payroll_periods` y `payroll_items`). Solo se pueden cerrar fechas que ya terminaron y que no se crucen con otro periodo cerrado.
- **Qué se bloquea:** justificar, registrar checadas manuales y aprobar o crear solicitudes en esas fechas. Así no cambia una nómina que ya se pagó.
- **Consultar:** un periodo cerrado muestra las cifras guardadas, no un recálculo, y se exporta igual a CSV.
- **Reabrir:** solo el dueño; se borra lo guardado y las fechas vuelven a poder modificarse. Queda en la bitácora.

## Apariencia

En Mi perfil → Apariencia cada usuario elige, en su navegador:
- **Modo:** claro, oscuro o el del sistema.
- **Color de acento:** azul corporativo (predeterminado), índigo, morado, turquesa, verde, naranja o rosa.
- **Tipografía:** Inter, Poppins, Montserrat, Nunito o Lora.

El acento solo cambia el color de acción; los colores de estado (a tiempo, retardo, falta) no cambian. Se aplica con `data-accent` y `data-font` en `<html>` (estilos en `styles.css`), y `index.html` lo aplica antes de cargar Angular para que no parpadee.

## Correos y recuperar contraseña

- **Plantilla de marca:** todos los correos usan la misma plantilla (`resources/views/vendor/mail`): encabezado AsistControl, botón en el azul corporativo, textos en español y pie con JALY SYSTEMS, soporte y enlaces legales. Para cambiar colores, edita `resources/views/vendor/mail/html/themes/default.css`; puedes ver los correos en Mailpit (http://localhost:8025).
- **Olvidé mi contraseña** (`/olvide-contrasena`, con reCAPTCHA):
  - Envía un enlace que vence en 60 minutos y sirve una sola vez. La respuesta es la misma exista o no el correo.
  - Al restablecer (`/restablecer-contrasena`) se cierran todas las sesiones y tokens de la app, y una contraseña temporal deja de serlo.
  - La app móvil usa los mismos endpoints: `POST /api/auth/password/forgot` y `POST /api/auth/password/reset`.

## Carga masiva de empleados

1. **Importar** (Empleados → Carga masiva): pega las columnas desde Excel o sube un CSV (hay plantilla para descargar). Van nombre, apellidos y sueldo opcional: `M:9500` mensual o `D:450` diario.
   - La vista previa marca los errores por fila, y no se importa nada hasta que todo está correcto.
   - Respeta el límite de empleados del plan.
2. **Organizar** (`/panel/empleados/organizar`): los importados quedan "Sin organizar" (sin oficina, turno, área ni tipo) y no pueden checar.
   - Cuando hay más de 2 pendientes, Empleados muestra el enlace "Organizar empleados".
   - En esa tabla se seleccionan varios y se les aplica oficina, turno (de esa oficina), área y tipo; cada uno puede tener la app con su correo.
   - Al guardar a todos, regresa a Empleados y el enlace desaparece. Con 1 o 2 pendientes se organizan desde la ficha de cada empleado.
3. **API:** `POST /api/employees/import`, `GET /api/employees/setup` y `POST /api/employees/organize` (permiso `employees.manage`).

## Inicio de la empresa

Dueño, administradores y gerentes (cada quien con los empleados que puede ver) tienen en el inicio:

- **Tarjetas del día:** asistencia de hoy (quiénes checaron de los que les toca trabajar), cuántos están en la oficina ahora, retardos y solicitudes pendientes por tipo.
- **Gráficas:** todas con Chart.js: de línea para lo que cambia en el tiempo y de pastel para las distribuciones. Siguen el tema claro/oscuro y el color de acento, y cada una tiene *Ver tabla*.
- **Así va el día:** gráfica de pastel con el estado de cada empleado activo, en la hora local de su oficina:
  - *A tiempo* y *Con retardo*: ya checaron entrada.
  - *Sin registro*: ya pasó su hora de entrada más la tolerancia y no ha checado.
  - *Vacaciones* y *Permiso*: tienen una solicitud aprobada que cubre hoy.
  - *Aún no es su hora*: su turno empieza más tarde.
  - *Descanso*, *Festivo* y *Sin turno*: no les tocaba trabajar; no cuentan en el "19 de 20".
  - Junto a la gráfica: quién no ha llegado (con su hora de entrada), quién llegó tarde (con minutos) y quién está fuera hoy.
- **Por oficina y por turno:** una barra por cada uno con el mismo desglose. Solo aparece si la empresa tiene más de una oficina o más de un turno.
- **Asistencia del mes:** pastel con los días laborables del mes a tiempo, con retardo, con falta sin justificar y con vacaciones o permiso.
- **Entradas de los últimos 14 días:** línea por estado (a tiempo, retardo, falta).
- **Este mes:** asistencia, puntualidad de 30 días (con la diferencia contra los 30 anteriores), retardos, faltas sin justificar, horas extra y días de vacaciones o permiso.
- Los 5 empleados con más retardos del mes, las vacaciones en curso y las de los próximos 14 días, las entradas de los últimos 14 días, las últimas checadas y las solicitudes por revisar.

## Notificaciones (campana)

La barra superior y el menú lateral se quedan fijos al hacer scroll. La campana de la barra superior muestra un contador de lo que no has leído, las 8 más recientes y un enlace a **Notificaciones** con todo el historial (filtro *Sin leer* y *Marcar todas como leídas*). Revisa si hay algo nuevo cada minuto.

| Quién | Qué le llega |
|---|---|
| Dueño y administradores | Solicitudes nuevas que les toca revisar (justificaciones, permisos, vacaciones, llegadas tarde, salidas anticipadas). No reciben avisos: su campana es solo para lo que requiere su revisión. |
| Gerentes | Solicitudes de su área que pueden revisar, los avisos de la empresa y la respuesta a sus propias solicitudes. |
| Empleados | Los avisos de la empresa y si su solicitud se aprobó o rechazó, con el motivo que escribió quien la revisó. |

- Una solicitud le llega a quien tenga el permiso para revisarla (`vacations.approve` para vacaciones y `requests.approve` para las demás) y pueda ver al empleado. El dueño siempre la recibe.
- Cuando alguien la revisa, deja de aparecer como pendiente en la campana de los demás.
- Un aviso leído desde la campana también queda leído en *Notificaciones → Avisos*.
- API: `GET /api/panel-notifications` (con `?unread=1`), `GET /api/panel-notifications/unread`, `POST /api/panel-notifications/{id}/read` y `POST /api/panel-notifications/read-all`.

## Bitácora

Toda acción importante queda registrada con quién la hizo, cuándo y el valor anterior y el nuevo de cada campo. Cada registro está ligado a un empleado (justificaciones, cambios a su expediente, descargas de su reporte) o es general (exportes, configuración, permisos). Por defecto solo el dueño la ve (permiso `audit.view`), y puede otorgársela a administradores.

## Legal

Las páginas `/legal/terminos`, `/legal/privacidad` y `/legal/cookies` son una **plantilla**. Antes de publicar:

1. Completa los datos pendientes en `frontend/src/app/shared/constants/legal.ts` (correo de privacidad y de soporte, domicilio, jurisdicción).
2. Haz que un abogado revise los textos.

Al cambiar los textos, sube la versión en `legal.ts` y en `backend/config/legal.php`: se volverá a pedir el consentimiento de cookies.

## Pruebas

```bash
# Backend: usa bases asist_test_* separadas
../bin/php.cmd artisan test

# Frontend
npx ng test --watch=false
```
