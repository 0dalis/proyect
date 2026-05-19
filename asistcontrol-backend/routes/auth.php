<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('api/web/services/1/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('api/web/services/1/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {

    // Logout
    Route::post('api/web/services/1/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Opcional: cambiar contraseña
    // Route::put('api/web/services/1/password', [PasswordController::class, 'update'])->name('password.update');
});
