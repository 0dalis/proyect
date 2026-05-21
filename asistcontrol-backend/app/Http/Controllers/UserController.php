<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return ['auth',];
    }

    // Mostrar todos los usuarios
    public function index(){
        $users = User::with('roles')->get(); // Trae todos los usuarios
        $roles = Role::all(); // Trae todos los roles
        return view('system.user', compact('users', 'roles')); // Enviamos a la vista
    }
}
