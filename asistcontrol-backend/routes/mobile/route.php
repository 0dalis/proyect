<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;

// Ya estás dentro de /api/mobile/ y estás protegido por Sanctum
Route::post('/logout', [PublicController::class, 'logout']);
