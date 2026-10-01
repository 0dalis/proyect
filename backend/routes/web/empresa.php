<?php

use App\Http\Controllers\Api\CompanyDeletionController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\Api\PlanSelectionController;
use Illuminate\Support\Facades\Route;

/*
| Datos de la empresa.
| - Configuración (módulos, acceso web de empleados): solo el dueño
| - Suscripción y consumo del plan: dueño o administrador
| - Cambiar de plan y registrar tarjeta: solo el dueño
| - Eliminar la empresa de AsistControl: solo el dueño
*/

Route::patch('company/settings', [CompanySettingsController::class, 'update'])
    ->middleware('can:owner')
    ->name('configuracion');

// Logotipo: todos lo ven, solo el dueño lo sube o lo quita
Route::get('company/logo', [CompanySettingsController::class, 'logo'])->name('logo.ver');
Route::post('company/logo', [CompanySettingsController::class, 'updateLogo'])->middleware(['can:owner', 'throttle:20,1'])->name('logo.guardar');
Route::delete('company/logo', [CompanySettingsController::class, 'destroyLogo'])->middleware('can:owner')->name('logo.quitar');

Route::get('company/subscription', [CompanySettingsController::class, 'subscription'])
    ->middleware('can:admin-or-owner')
    ->name('suscripcion');

Route::prefix('company/billing')->middleware('can:owner')->name('plan.')->group(function () {
    Route::get('plans', [PlanSelectionController::class, 'show'])->name('opciones');
    Route::post('setup-intent', [PlanSelectionController::class, 'setupIntent'])->middleware('throttle:10,1')->name('tarjeta');
    Route::post('subscribe', [PlanSelectionController::class, 'subscribe'])->middleware('throttle:10,1')->name('cambiar');
});

Route::post('company/deletion', [CompanyDeletionController::class, 'store'])
    ->middleware(['can:owner', 'throttle:3,1'])
    ->name('eliminar');
