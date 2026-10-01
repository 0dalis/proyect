<?php

use App\Http\Controllers\Api\AnnouncementController;
use Illuminate\Support\Facades\Route;

/*
| Avisos y noticias.
| - Bandeja y noticias: cualquier usuario (solo recibe lo que le enviaron).
| - Enviar y ver enviados: announcements.send
*/

Route::get('notifications', [AnnouncementController::class, 'inbox'])->name('bandeja');
Route::post('notifications/{announcement}/read', [AnnouncementController::class, 'markRead'])->name('leido');
Route::get('news', [AnnouncementController::class, 'news'])->name('noticias');

Route::middleware('can:announcements.send')->group(function () {
    Route::get('announcements', [AnnouncementController::class, 'index'])->name('index');
    Route::post('announcements', [AnnouncementController::class, 'store'])->middleware('throttle:20,1')->name('store');
});
