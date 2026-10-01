<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas que usa el panel web (Angular)
|--------------------------------------------------------------------------
|
| Se cargan desde routes/api.php, así que todas quedan bajo /api. Cada
| módulo vive en su propio archivo de esta carpeta. Para agregar uno:
|   1. Crea routes/web/mi-modulo.php con sus rutas y su `can:` correspondiente.
|   2. Agrégalo abajo, en el grupo que le corresponda.
|
| Capas de seguridad de las rutas protegidas:
|   auth:sanctum  sesión (cookie + CSRF desde el panel) o token (app)
|   tenant        conecta la BD de la empresa, bloquea usuarios o empresas inactivas
|                 y el acceso web de empleados si el dueño lo desactivó. Mientras la
|                 empresa elige plan, solo deja pasar onboarding.php y la sesión
|   seats         empleados fuera del límite del plan: se ven, pero no se modifican
|   can:...       permiso del rol (el dueño los tiene todos); ver cada archivo
|
*/

// Sin sesión: landing, registro y login
Route::name('web.publico.')->group(__DIR__.'/publico.php');

// Pantalla del kiosko (token del dispositivo, no de usuario)
Route::name('web.')->group(__DIR__.'/kiosko.php');

// Con sesión y empresa activa
Route::middleware(['auth:sanctum', 'tenant', 'seats'])->name('web.')->group(function () {
    Route::name('sesion.')->group(__DIR__.'/sesion.php');
    Route::name('onboarding.')->group(__DIR__.'/onboarding.php');
    Route::group([], __DIR__.'/inicio.php');
    Route::name('empleados.')->group(__DIR__.'/empleados.php');
    Route::name('historico.')->group(__DIR__.'/historico.php');
    Route::name('asistencia.')->group(__DIR__.'/asistencia.php');
    Route::name('solicitudes.')->group(__DIR__.'/solicitudes.php');
    Route::name('avisos.')->group(__DIR__.'/avisos.php');
    Route::name('notificaciones.')->group(__DIR__.'/notificaciones.php');
    Route::name('reportes.')->group(__DIR__.'/reportes.php');
    Route::name('nomina.')->group(__DIR__.'/nomina.php');
    Route::name('organizacion.')->group(__DIR__.'/organizacion.php');
    Route::name('usuarios.')->group(__DIR__.'/usuarios.php');
    Route::name('kioskos.')->group(__DIR__.'/kioskos.php');
    Route::name('empresa.')->group(__DIR__.'/empresa.php');
});
