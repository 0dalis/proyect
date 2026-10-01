<?php

use App\Http\Controllers\Api\EmployeeRequestController;
use Illuminate\Support\Facades\Route;

/*
| Solicitudes (justificaciones, llegadas tarde, salidas anticipadas, vacaciones, permisos).
| - Consultar y crear: cualquier usuario ligado a un empleado (cada quien ve lo suyo o su equipo).
| - Revisar: requests.approve o vacations.approve; el controlador además valida el
|   tipo (vacaciones) y que el empleado sea de su equipo.
*/

Route::get('requests', [EmployeeRequestController::class, 'index'])->name('index');
Route::post('requests', [EmployeeRequestController::class, 'store'])->middleware('throttle:20,1')->name('store');

Route::post('requests/{employeeRequest}/review', [EmployeeRequestController::class, 'review'])
    ->middleware('can:review-requests')
    ->name('revisar');
