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
    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        try {
            $role = Role::create(['name' => $request->name]);

            if ($request->filled('permissions')) {
                // Convertimos IDs a Permission models
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente',
                'role' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request){
        $request->validate([
            'roleId' => 'required|integer|exists:roles,id',
            'name' => 'required|string|max:255|unique:roles,name,' . $request->roleId,
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        try {
            $role = Role::findOrFail($request->roleId);
            $role->name = $request->name;
            $role->save();

            // Convertimos IDs a Permission models
            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
            $role->syncPermissions($permissions);

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente',
                'role' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el rol: ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroy(Request $request){
        $request->validate([
            'roleId' => 'required|integer|exists:roles,id'
        ]);
        $id = $request->roleId;
    try {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar el rol: ' . $e->getMessage()
        ], 500);
    }
}
}
