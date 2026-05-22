<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;
use App\Models\Company;


class UserController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return ['auth',];
    }
    public function index(){
        $users = User::with('roles')->get();
        $roles = Role::all();
        return view('system.user', compact('users', 'roles'));
    }
    public function store(Request $request){
        $request->merge([
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);
        $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'nullable|string|min:6',
            'pin'           => 'nullable|string|min:4',
            'employee_code' => 'nullable|string|max:50',
            'is_active'     => 'required|boolean',
            'roles'         => 'nullable|array',
            'company_code'  => 'nullable|string',
        ]);
        $companyId = null;
        if ($request->filled('company_code')) {
            $company = Company::where('code', $request->company_code)->first();
            if ($company) {
                $companyId = $company->id;
            } else {
                return response()->json(['error' => 'Código de empresa inválido'], 422);
            }
        }
        $user = User::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'password'      => $request->password,
            'pin'           => $request->pin,
            'employee_code' => $request->employee_code,
            'is_active'     => $request->has('is_active') ? true : false,
            'company_id'    => $companyId,
        ]);

        // Sincronizar roles – convertir IDs a enteros
        if ($request->filled('roles')) {
            $rolesIds = array_map('intval', $request->roles);
            $user->syncRoles($rolesIds);
        } else {
            $user->syncRoles([]);
        }
        return response()->json(['success' => true, 'message' => 'Usuario creado correctamente']);
    }
    public function update(Request $request){
        $user = User::findOrFail($request->userid);

        // Normalizar el valor del checkbox is_active
        $request->merge([
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        $validated = $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'password'      => 'nullable|string|min:6',
            'pin'           => 'nullable|string|min:4',
            'employee_code' => 'nullable|string|max:50',
            'is_active'     => 'required|boolean',
            'roles'         => 'nullable|array',
            'roles.*'       => 'integer|exists:roles,id',  // validación extra: solo IDs válidos
            'company_code'  => 'nullable|string',
        ]);

        // Buscar empresa si se cambió
        $companyId = $user->company_id;
        if ($request->filled('company_code')) {
            $company = Company::where('code', $request->company_code)->first();
            if ($company) {
                $companyId = $company->id;
            } else {
                return response()->json(['message' => 'Código de empresa inválido'], 422);
            }
        }

        // Actualizar datos del usuario
        $user->update([
            'first_name'    => $validated['first_name'],
            'last_name'     => $validated['last_name'],
            'email'         => $validated['email'],
            'password'      => $request->filled('password') ? bcrypt($validated['password']) : $user->password,
            'pin'           => $validated['pin'] ?? $user->pin,
            'employee_code' => $validated['employee_code'] ?? $user->employee_code,
            'is_active'     => $validated['is_active'],
            'company_id'    => $companyId,
        ]);

        // Sincronizar roles – convertir IDs a enteros
        if ($request->filled('roles')) {
            $rolesIds = array_map('intval', $validated['roles']); // convierte a enteros
            $user->syncRoles($rolesIds);
        } else {
            $user->syncRoles([]);
        }

        return response()->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    }
}
