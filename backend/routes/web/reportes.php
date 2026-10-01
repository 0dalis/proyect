<?php

use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
| Reportes de asistencia (y su exportación a CSV): reports.view
*/

Route::get('reports/attendance', [ReportController::class, 'attendance'])
    ->middleware('can:reports.view')
    ->name('asistencia');
