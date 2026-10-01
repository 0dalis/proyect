<?php

use App\Http\Controllers\Api\KioskController;
use Illuminate\Support\Facades\Route;

/*
| Alta y administración de kioskos desde el panel: kiosks.manage
| (La pantalla del kiosko usa routes/web/kiosko.php.)
*/

Route::middleware('can:kiosks.manage')->group(function () {
    Route::apiResource('kiosks', KioskController::class)->except('show');
    Route::post('kiosks/{kiosk}/token', [KioskController::class, 'regenerateToken'])->name('kiosks.codigo');
});
