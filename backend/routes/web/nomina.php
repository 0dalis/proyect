<?php

use App\Http\Controllers\Api\BonusRuleController;
use App\Http\Controllers\Api\PayrollController;
use Illuminate\Support\Facades\Route;

/*
| Pre-nómina y bonos. Además del permiso, el plan debe incluirlos y el módulo estar activo.
| - Pre-nómina y cerrar periodos: payroll.manage + módulo payroll
| - Reabrir un periodo cerrado:    solo el dueño
| - Bonos:                         bonuses.manage + módulo bonuses
*/

Route::middleware(['can:payroll.manage', 'module:payroll'])->group(function () {
    Route::get('payroll', [PayrollController::class, 'index'])->name('prenomina');
    Route::get('payroll/periods', [PayrollController::class, 'periods'])->name('periodos.index');
    Route::post('payroll/periods', [PayrollController::class, 'close'])->name('periodos.cerrar');
    Route::get('payroll/periods/{period}', [PayrollController::class, 'show'])->name('periodos.ver');
    Route::delete('payroll/periods/{period}', [PayrollController::class, 'reopen'])
        ->middleware('can:owner')
        ->name('periodos.reabrir');
});

Route::middleware(['can:bonuses.manage', 'module:bonuses'])->group(function () {
    Route::apiResource('bonus-rules', BonusRuleController::class)->except('show');
});
