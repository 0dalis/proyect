<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\UserAccessController;
use Illuminate\Support\Facades\Route;

/*
| Usuarios, roles, permisos y celulares registrados.
| - Acceso de usuarios y celulares: users.manage
| - Asignar roles:                   roles.assign (el controlador impide tocar al dueño
|                                    y que un admin nombre o quite admins)
| - Editar permisos de los roles:    solo el dueño
| - Ver celulares propios:           cualquier usuario (solo ve los suyos)
*/

Route::get('devices', [DeviceController::class, 'index'])->name('celulares.index');

Route::middleware('can:users.manage')->group(function () {
    Route::get('users', [UserAccessController::class, 'index'])->name('index');
    Route::patch('users/{user}/access', [UserAccessController::class, 'updateAccess'])->name('acceso');
    Route::post('devices/{device}/approve', [DeviceController::class, 'approve'])->name('celulares.autorizar');
    Route::post('devices/{device}/revoke', [DeviceController::class, 'revoke'])->name('celulares.revocar');
});

Route::middleware('can:roles.assign')->group(function () {
    Route::get('roles', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::put('users/{user}/roles', [UserAccessController::class, 'updateRoles'])->name('roles.asignar');
});

Route::put('roles/{role}/permissions', [RolePermissionController::class, 'update'])
    ->middleware('can:owner')
    ->name('roles.permisos');
