<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Support\Facades\Route;

/*
| Oficinas (geocerca y zona horaria), turnos, áreas y días festivos: organization.manage
| Consultar los festivos: cualquier usuario (el empleado los ve al pedir vacaciones)
*/

Route::get('holidays', [HolidayController::class, 'index'])->name('festivos.index');

Route::middleware('can:organization.manage')->group(function () {
    Route::apiResource('offices', OfficeController::class);
    Route::apiResource('shifts', ShiftController::class)->except('show');
    Route::apiResource('areas', AreaController::class)->except('show');
    Route::put('areas/{area}/managers', [AreaController::class, 'syncManagers'])->name('areas.gerentes');
    Route::post('holidays', [HolidayController::class, 'store'])->name('festivos.store');
    Route::post('holidays/official', [HolidayController::class, 'loadOfficial'])->name('festivos.oficiales');
    Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('festivos.destroy');
});
