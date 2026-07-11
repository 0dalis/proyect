<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;

// Ya estás dentro de /api/web/ y estás protegido por Sanctum
Route::get('/user-permissions', [PublicController::class, 'getUserPermissions']);
Route::post('/logout', [PublicController::class, 'logout']);
Route::prefix('v1')->group(function () {

});
