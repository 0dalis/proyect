<?php

use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
| Asistencia.
| - Consultar: cualquier usuario; cada quien ve solo los empleados que le corresponden.
| - Registro manual y justificar: attendance.manage
*/

Route::get('attendance', [AttendanceController::class, 'index'])->name('index');

Route::middleware('can:attendance.manage')->group(function () {
    Route::post('attendance/manual', [AttendanceController::class, 'storeManual'])->name('manual');
    Route::patch('attendance/{record}/justify', [AttendanceController::class, 'justify'])->name('justificar');
});
