<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyDeletionController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PublicConfigController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Support\Facades\Route;

/*
| Rutas públicas: landing, registro, verificación de correo e inicio de sesión.
| No requieren sesión; tienen límite de intentos por minuto. Registro, login y
| confirmación de cuenta validan reCAPTCHA v3.
*/

Route::get('config', PublicConfigController::class)->name('configuracion');
Route::get('plans', [PlanController::class, 'index'])->name('planes.index');
// Opiniones que el Super Admin eligió mostrar en la landing
Route::get('testimonials', [RatingController::class, 'testimonials'])->middleware('throttle:60,1')->name('opiniones');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
    Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('auth.resend');

    // Olvidé mi contraseña (panel y app)
    Route::post('password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1')->name('password.forgot');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.reset');

    // Enlace del correo (firmado). Nombre completo: web.publico.verification.verify
    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('verification.verify');
    Route::post('email/verify/{id}/{hash}', [EmailVerificationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('verification.confirm');
});

// Baja de empresa: descarga de datos (48 horas) y valoración final
Route::get('exports/{token}', [CompanyDeletionController::class, 'download'])
    ->middleware('throttle:10,1')
    ->name('exportacion.descargar');
Route::get('feedback/{token}', [FeedbackController::class, 'show'])->middleware('throttle:20,1')->name('valoracion.ver');
Route::post('feedback/{token}', [FeedbackController::class, 'store'])->middleware('throttle:5,1')->name('valoracion.guardar');
