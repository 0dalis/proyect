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

Route::get('/', function () {
    // Mensajes de error
    $messages = [
        ["message" => "Access Denied", "severity" => "high"],
        ["message" => "Resource not found", "severity" => "medium"],
        ["message" => "Service unavailable", "severity" => "critical"],
        ["message" => "Unauthorized access", "severity" => "high"],
        ["message" => "Invalid request format", "severity" => "low"],
        ["message" => "Session expired", "severity" => "medium"],
        ["message" => "Permission error", "severity" => "high"],
        ["message" => "Connection timeout", "severity" => "medium"],
        ["message" => "Operation not allowed", "severity" => "high"]
    ];

    // Servidores simulados
    $servers = [
        "Local Server Alpha",
        "Node-7 Cluster",
        "Mainframe-3",
        "Backend Gateway",
        "Proxy Server X",
        "Database Node 12"
    ];

    // Ubicaciones simuladas
    $locations = [
        "New York, USA",
        "London, UK",
        "Tokyo, JP",
        "Berlin, DE",
        "Sydney, AU",
        "Mexico City, MX"
    ];

    // Estados de sesión aleatorios
    $sessionStates = [
        "active",
        "inactive",
        "locked",
        "expired",
        "pending_verification"
    ];

    // Obtener IP real del visitante
    $ip = request()->ip();

    // Elegir datos aleatorios
    $error = $messages[array_rand($messages)];
    $server = $servers[array_rand($servers)];
    $location = $locations[array_rand($locations)];
    $sessionState = $sessionStates[array_rand($sessionStates)];
    $time = now()->format('Y-m-d H:i:s');

    // Crear JSON de respuesta
    $response = [
        "error" => $error['message'],
        "code" => rand(400, 503),
        "severity_level" => $error['severity'],
        "details" => [
            "server_state" => $server,
            "local_time" => $time,
            "location_detected" => $location,
            "ip_access" => $ip,
            "session_status" => $sessionState,
            "suggested_action" => "Please contact your system supervisor or administrator to review permissions"
        ]
    ];

    return response()->json($response, (int) $response['code']);
});
Route::middleware('auth')->prefix('api/web/services/1')->group(function () {

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
require __DIR__.'/api.php';
require __DIR__.'/console.php';
