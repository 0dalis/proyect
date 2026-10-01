<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| Tablero de inicio. Todos los roles; los datos se limitan a los empleados
| que cada quien puede ver (dueño y admin todos, gerente sus áreas, empleado él mismo).
*/

Route::get('dashboard', DashboardController::class)->name('inicio');
