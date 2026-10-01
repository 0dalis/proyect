<?php

use App\Http\Controllers\Api\KioskTerminalController;
use Illuminate\Support\Facades\Route;

/*
| Pantalla del kiosko (tableta o PC de la entrada). No usa sesión de usuario:
| se autentica con el encabezado X-Kiosk-Token del dispositivo.
*/

Route::prefix('kiosk')->middleware(['kiosk', 'throttle:120,1'])->name('kiosko.')->group(function () {
    Route::get('me', [KioskTerminalController::class, 'show'])->name('info');
    Route::post('punch', [KioskTerminalController::class, 'punch'])->name('checar');
    // Consulta de asistencia para quien no tiene la app
    Route::post('my-attendance', [KioskTerminalController::class, 'myAttendance'])->name('mi-asistencia');
    Route::get('news', [KioskTerminalController::class, 'news'])->name('noticias');
});
