<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller{
    public function index() {
        $roles = Role::all();
        $permissions = Permission::all();
        return view('system.rolandpermision.role', compact('roles', 'permissions'));
    }
}
