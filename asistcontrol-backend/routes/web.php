<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\StripeWebhookController;

// ===== Landing Page (pública) =====
Route::get('/', [LandingController::class, 'index'])->name('landing');

// ===== Webhook de Stripe (público, firma verificada por Cashier) =====
Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');
Route::get('/acceso', [LandingController::class, 'acceso'])->name('acceso');
Route::get('/privacidad', [LandingController::class, 'privacidad'])->name('privacidad');
Route::get('/terminos', [LandingController::class, 'terminos'])->name('terminos');
Route::get('/sistema', [LandingController::class, 'sistema'])->name('sistema');
Route::get('/planes-detalle', [LandingController::class, 'planesDetalle'])->name('planes-detalle');
Route::post('/contacto', [LandingController::class, 'contacto'])->name('landing.contacto')->middleware('throttle:contacto');
Route::post('/registro', [LandingController::class, 'registro'])->name('landing.registro')->middleware('throttle:registro');
Route::get('/activar-cuenta/{id}', [LandingController::class, 'activarCuenta'])->name('activar.cuenta');
Route::post('/verificar-cuenta/{id}', [LandingController::class, 'verificarCuenta'])->name('verificar.cuenta');

// ===== Panel de administración (requiere auth) =====
Route::middleware(['auth', 'check.inactivity', 'role:super-admin'])->prefix('api/web/services/1')->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])                                 ->name('dashboard');

    // Perfil oculto
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])                                     ->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])                                 ->name('profile.update');
        //Route::delete('/', [ProfileController::class, 'destroy'])                               ->name('profile.destroy');
    });
    Route::get('users', [UserController::class, 'index'])                                       ->name('users.index');
    Route::post('users', [UserController::class, 'store'])                                      ->name('users.store');
    Route::post('usersupd', [UserController::class, 'update'])                                  ->name('users.update');

    Route::get('employees', [EmployeeController::class, 'index'])                               ->name('employees.index');
    Route::post('employees', [EmployeeController::class, 'store'])                              ->name('employees.store');
    Route::post('employeesupd', [EmployeeController::class, 'update'])                          ->name('employees.update');


    Route::get('companies', [CompanyController::class, 'index'])                                ->name('companies.index');
    Route::post('companies', [CompanyController::class, 'store'])                               ->name('companies.store');
    Route::put('companies', [CompanyController::class, 'update'])                               ->name('companies.update');


    Route::get('roles', [RoleController::class, 'index'])                                       ->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])                                      ->name('roles.store');
    Route::post('rolesupd', [RoleController::class, 'update'])                                  ->name('roles.update');
    Route::delete('roles', [RoleController::class, 'destroy'])                                  ->name('roles.destroy');


    Route::get('permissions', [PermissionController::class, 'index'])                           ->name('permissions.index');
    Route::post('permissions', [PermissionController::class, 'store'])                          ->name('permissions.store');
    Route::post('permissionsupd', [PermissionController::class, 'update'])                      ->name('permissions.update');
    Route::delete('permissions', [PermissionController::class, 'destroy'])                      ->name('permissions.destroy');


    Route::get('planes', [PlanController::class, 'index'])                                      ->name('planes.index');
    Route::post('planes', [PlanController::class, 'store'])                                     ->name('planes.store');
    Route::put('planes', [PlanController::class, 'update'])                                     ->name('planes.update');
    Route::patch('/planes/toggle', [PlanController::class, 'togglePublic'])                     ->name('planes.toggle');

});
/*Route::middleware(['auth', 'role:super-admin'])->prefix('api/web/services/1')->group(function () {

    // Dashboard oculto
    Route::get('dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Perfil oculto
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
    });

    // Users y companies
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');

    // Roles
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::post('rolesupd', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Permissions
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::post('permissionsupd', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('permissions', [PermissionController::class, 'destroy'])->name('permissions.destroy');

});*/
// Cargar rutas
require __DIR__.'/auth.php';
