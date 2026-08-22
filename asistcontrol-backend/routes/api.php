<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;

Route::post('/login', [PublicController::class, 'loginWeb'])->middleware('throttle:login-web');
Route::post('/mobile', [PublicController::class, 'loginmobile'])->middleware('throttle:login-mobile');

Route::middleware(['auth:sanctum', 'check.inactivity'])->prefix('web')->group(function () {
    require base_path('routes/web/route.php');
});

Route::middleware('auth:sanctum')->prefix('mobile')->group(function () {
    require base_path('routes/mobile/route.php');
});

