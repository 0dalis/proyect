<?php

use App\Http\Controllers\Api\PanelNotificationController;
use Illuminate\Support\Facades\Route;

/*
| Campana del panel. Cualquier usuario; cada quien solo ve las suyas:
| - Dueño, administradores y gerentes: solicitudes que les toca revisar.
| - Quien pidió algo: si se aprobó o rechazó y por qué.
| - Gerentes y empleados: avisos de la empresa.
*/

Route::prefix('panel-notifications')->group(function () {
    Route::get('/', [PanelNotificationController::class, 'index'])->name('index');
    Route::get('unread', [PanelNotificationController::class, 'unread'])->middleware('throttle:120,1')->name('sin-leer');
    Route::post('read-all', [PanelNotificationController::class, 'markAllRead'])->name('leer-todas');
    Route::post('{notification}/read', [PanelNotificationController::class, 'markRead'])->whereNumber('notification')->name('leer');
});
