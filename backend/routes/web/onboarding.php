<?php

use App\Http\Controllers\Api\PlanSelectionController;
use Illuminate\Support\Facades\Route;

/*
| Modal de bienvenida del dueño, la primera vez que entra (empresa en estado
| "onboarding"). Mientras no termine, InitializeTenancy solo deja pasar estas
| rutas y las de sesión. Solo el dueño.
*/

Route::prefix('onboarding')->middleware('can:owner')->group(function () {
    Route::get('/', [PlanSelectionController::class, 'show'])->name('estado');
    Route::post('accept', [PlanSelectionController::class, 'accept'])->name('aceptar');
    Route::post('setup-intent', [PlanSelectionController::class, 'setupIntent'])->middleware('throttle:10,1')->name('tarjeta');
    Route::post('subscribe', [PlanSelectionController::class, 'subscribe'])->middleware('throttle:10,1')->name('plan');
});
