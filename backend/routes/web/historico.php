<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\EmployeeProfileController;
use Illuminate\Support\Facades\Route;

/*
| Histórico (bitácora).
| - General:      audit.view (por defecto solo el dueño; puede darlo a administradores)
| - Por empleado: employees.view y que el empleado sea visible para quien consulta
*/

Route::get('activity', [ActivityController::class, 'index'])
    ->middleware('can:audit.view')
    ->name('general');

Route::get('employees/{employee}/activity', [EmployeeProfileController::class, 'activity'])
    ->middleware('can:employees.view')
    ->name('empleado');
