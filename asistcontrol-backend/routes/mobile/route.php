<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\mobile\AttendanceController;
use App\Http\Controllers\mobile\ConfigController;
use App\Http\Controllers\mobile\CredentialController;
use App\Http\Controllers\mobile\NotificationController;
use App\Http\Controllers\mobile\RequestController;

// Dentro de /api/mobile/ y protegido por Sanctum.
Route::post('/logout', [PublicController::class, 'logoutmobile']);

Route::post('/asistencia/registrar', [AttendanceController::class, 'registrar']);
Route::get('/attendance/history', [AttendanceController::class, 'history']);

Route::get('/requests', [RequestController::class, 'index']);
Route::post('/requests', [RequestController::class, 'store']);

Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

Route::post('/empresa/fonts', [ConfigController::class, 'fonts']);
Route::post('/device-token', [ConfigController::class, 'deviceToken']);

Route::get('/credential', [CredentialController::class, 'show']);
Route::get('/credential/pdf', [CredentialController::class, 'pdf']);
