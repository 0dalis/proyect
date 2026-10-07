<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Support\Facades\Route;

/*
| Sesión y perfil del usuario actual. Cualquier usuario con sesión válida;
| cada quien solo ve y modifica lo suyo.
*/

Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');

// Opinión sobre AsistControl: solo dueño y administradores
Route::middleware('can:admin-or-owner')->prefix('rating')->name('opinion.')->group(function () {
    Route::get('/', [RatingController::class, 'show'])->name('ver');
    Route::put('/', [RatingController::class, 'store'])->middleware('throttle:10,1')->name('guardar');
});

// Angular lo llama si el usuario sigue activo (mouse, teclado) sin hacer
// peticiones, para que la cookie no venza mientras lee o llena un formulario.
Route::get('session/ping', fn () => response()->json([
    'idle_minutes' => (int) config('session.lifetime'),
]))->middleware('throttle:30,1')->name('ping');

Route::prefix('me')->name('perfil.')->group(function () {
    Route::get('/', [AuthController::class, 'me'])->name('actual');
    Route::get('profile', [ProfileController::class, 'show'])->name('datos');
    Route::put('profile', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('datos.guardar');
    Route::post('sessions/close', [ProfileController::class, 'destroyOtherSessions'])->middleware('throttle:6,1')->name('sesiones.cerrar');
    Route::get('summary', [ProfileController::class, 'summary'])->name('resumen');
    Route::put('password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('password');
    Route::put('pin', [ProfileController::class, 'updatePin'])->middleware('throttle:6,1')->name('pin');
    // Primer acceso con contraseña temporal: nueva contraseña + PIN de 6 dígitos
    Route::post('first-access', [ProfileController::class, 'firstAccess'])->middleware('throttle:6,1')->name('primer-acceso');

    // Foto de la cuenta (navbar y Mi perfil). El GET entrega los bytes.
    Route::get('avatar', [ProfileController::class, 'avatar'])->name('avatar.ver');
    Route::post('avatar', [ProfileController::class, 'updateAvatar'])->middleware('throttle:20,1')->name('avatar.guardar');
    Route::delete('avatar', [ProfileController::class, 'destroyAvatar'])->name('avatar.quitar');
});
