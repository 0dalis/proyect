<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    // Mostrar todos los permisos
    public function index()
    {
        $permissions = Permission::all(); // Obtener todos los permisos
        return view('system.rolandpermision.permision', compact('permissions'));
    }
    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);
        try {
            $permission = Permission::create([
                'name' => $request->name,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Permiso creado correctamente',
                'permission' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el permiso: ' . $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,',
            'permissionId' => 'required|integer',
        ]);
        $id = $request->permissionId;
        try {
            $permission = Permission::findOrFail($id);
            $permission->name = $request->name;
            $permission->save();

            return response()->json([
                'success' => true,
                'message' => 'Permiso actualizado correctamente',
                'permission' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el permiso: ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroy(Request $request){
        $request->validate([
            'permissionId' => 'required|integer',
        ]);
        $id = $request->permissionId;
        try {
            $permission = Permission::findOrFail($id);
            $permission->delete();
            return response()->json([
                'success' => true,
                'message' => 'Permiso eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el permiso: ' . $e->getMessage()
            ], 500);
        }
    }
}
