<?php

use App\Http\Controllers\Api\EmployeeBulkController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeProfileController;
use Illuminate\Support\Facades\Route;

/*
| Empleados: lista, detalle, estadísticas, reporte PDF, alta y edición.
| - Ver:      employees.view  (el gerente solo ve sus áreas; lo valida el controlador)
| - Editar:   employees.manage (incluye el interruptor "Acceso a la app")
| - Credenciales: kiosks.manage
*/

Route::middleware('can:employees.view')->group(function () {
    Route::get('employees', [EmployeeController::class, 'index'])->name('index');
    // {employee} es un token cifrado (base64url); se excluyen las palabras fijas
    // de otras rutas (setup, import, organize) para que no las capture.
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->where('employee', '(?!setup|import|organize)[A-Za-z0-9_=-]+')
        ->name('show');
    Route::get('employees/{employee}/stats', [EmployeeProfileController::class, 'stats'])->name('estadisticas');
    // El reporte viaja por POST para poder traer la captura del calendario.
    Route::post('employees/{employee}/report.pdf', [EmployeeProfileController::class, 'report'])->name('reporte');
    // Foto del empleado (ficha y credencial); lo protege el controlador
    Route::get('employees/{employee}/photo', [EmployeeController::class, 'photo'])->name('foto.ver');
});

Route::middleware('can:employees.manage')->group(function () {
    // Carga masiva: importar (nombre, apellidos, sueldo) y luego organizar
    Route::post('employees/import', [EmployeeBulkController::class, 'import'])->middleware('throttle:10,1')->name('importar');
    Route::get('employees/setup', [EmployeeBulkController::class, 'pending'])->name('sin-organizar');
    Route::post('employees/organize', [EmployeeBulkController::class, 'organize'])->name('organizar');

    Route::post('employees', [EmployeeController::class, 'store'])->name('store');
    Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
    Route::put('employees/{employee}/app-access', [EmployeeController::class, 'appAccess'])->name('app.cambiar');
    Route::post('employees/{employee}/app-access/resend', [EmployeeController::class, 'resendAppAccess'])
        ->middleware('throttle:5,1')
        ->name('app.reenviar');
    Route::post('employees/{employee}/remote-periods', [EmployeeController::class, 'storeRemotePeriod'])->name('home-office.store');
    Route::delete('employees/{employee}/remote-periods/{period}', [EmployeeController::class, 'destroyRemotePeriod'])->name('home-office.destroy');
    Route::post('employees/{employee}/photo', [EmployeeController::class, 'updatePhoto'])->middleware('throttle:20,1')->name('foto.guardar');
    Route::delete('employees/{employee}/photo', [EmployeeController::class, 'destroyPhoto'])->name('foto.quitar');
});

Route::middleware('can:kiosks.manage')->group(function () {
    Route::post('employees/{employee}/badge', [EmployeeController::class, 'reissueBadge'])->name('credencial.reemitir');
    Route::get('employees/{employee}/badge.pdf', [EmployeeController::class, 'badge'])->name('credencial.pdf');
    Route::post('employees/{employee}/badge.pdf', [EmployeeController::class, 'badgeCapture'])->name('credencial.capturar');
    Route::post('badges.pdf', [EmployeeController::class, 'badges'])->name('credenciales.pdf');
});
