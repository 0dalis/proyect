<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;

Route::post('/login', [PublicController::class, 'loginWeb']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user-permissions', [PublicController::class, 'getUserPermissions']);
    Route::post('/logout', [PublicController::class, 'logout']);
});