<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| Todo queda bajo /api. Las rutas se organizan por cliente:
|   routes/web/rutasweb.php        panel web (Angular), un archivo por módulo
|   routes/mobile/rutasmobile.php  lo exclusivo de la app móvil (Flutter)
|
*/

Route::group([], __DIR__.'/web/rutasweb.php');
Route::group([], __DIR__.'/mobile/rutasmobile.php');
