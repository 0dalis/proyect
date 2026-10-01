<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\DeviceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas exclusivas de la app móvil (Flutter)
|--------------------------------------------------------------------------
|
| Se cargan desde routes/api.php (quedan bajo /api). La app también usa las
| rutas comunes de routes/web (perfil, solicitudes, avisos...) con su token.
| Aquí solo va lo que el panel web no usa: registrar el celular y checar.
|
*/

Route::middleware(['auth:sanctum', 'tenant', 'seats'])->name('mobile.')->group(function () {
    // El celular registra su llave pública para firmar las checadas
    Route::post('devices', [DeviceController::class, 'store'])->middleware('throttle:10,1')->name('celulares.registrar');

    // Checada con huella / Face ID (firma) o PIN, validando la geocerca
    Route::post('attendance/punch', [AttendanceController::class, 'punch'])->middleware('throttle:20,1')->name('checar');
});
