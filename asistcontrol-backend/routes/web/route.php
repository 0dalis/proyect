<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\web\CompanyCompleteController;

Route::get('/user-permissions', [PublicController::class, 'getUserPermissions']);
Route::post('/logout', [PublicController::class, 'logout']);

Route::prefix('billing')->group(function () {
    Route::post('/checkout', [StripeController::class, 'checkout']);
    Route::get('/status', [StripeController::class, 'status']);
    Route::get('/portal', [StripeController::class, 'billingPortal']);
    Route::post('/cancel', [StripeController::class, 'cancel']);
});

Route::prefix('company-setup')->group(function () {
    Route::get('/status', [CompanyCompleteController::class, 'status']);
    Route::get('/limits', [CompanyCompleteController::class, 'limits']);
    Route::put('/profile', [CompanyCompleteController::class, 'updateProfile']);
    Route::get('/offices', [CompanyCompleteController::class, 'offices']);
    Route::post('/offices', [CompanyCompleteController::class, 'storeOffice']);
    Route::put('/offices/{id}', [CompanyCompleteController::class, 'updateOffice']);
    Route::delete('/offices/{id}', [CompanyCompleteController::class, 'deleteOffice']);
    Route::get('/areas', [CompanyCompleteController::class, 'areas']);
    Route::post('/areas', [CompanyCompleteController::class, 'storeArea']);
    Route::put('/areas/{id}', [CompanyCompleteController::class, 'updateArea']);
    Route::delete('/areas/{id}', [CompanyCompleteController::class, 'deleteArea']);
    Route::get('/employees', [CompanyCompleteController::class, 'employees']);
    Route::post('/employees', [CompanyCompleteController::class, 'storeEmployee']);
    Route::put('/employees/{id}', [CompanyCompleteController::class, 'updateEmployee']);
    Route::delete('/employees/{id}', [CompanyCompleteController::class, 'deleteEmployee']);
    Route::post('/next-step', [CompanyCompleteController::class, 'nextStep']);
    Route::post('/previous-step', [CompanyCompleteController::class, 'previousStep']);
    Route::post('/complete', [CompanyCompleteController::class, 'complete']);
});

Route::prefix('v1')->group(function () {

});
