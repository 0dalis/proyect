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
    public function update(Request $request) {
        // 1. Encontrar al usuario
        $user = User::findOrFail($request->userid);

        // 2. Homologar el estado del toggle antes de validar
        $request->merge([
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        // 3. Validar los datos entrantes
        $validated = $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'password'      => 'nullable|string|min:6',
            'pin'           => 'nullable|string|min:8',
            'employee_code' => 'nullable|string|max:50',
            'is_active'     => 'required|in:0,1',
            'roles'         => 'nullable|array',
            'roles.*'       => 'integer|exists:roles,id',
            'company_code'  => 'nullable|string',
        ]);

        // 4. Filtrar solo los campos que realmente vienen presentes en el Request
        // Esto evita pisar datos con valores por defecto o volver a escribir lo mismo
        $updateData = $request->only(['first_name', 'last_name', 'email', 'employee_code', 'is_active']);

        // 5. Manejo condicional de la Contraseña y PIN (Solo si traen información)
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }
        
        if ($request->filled('pin')) {
            $updateData['pin'] = $request->pin;
        }

        // 6. Manejo de la Empresa (Solo si se envió un código de empresa)
        if ($request->filled('company_code')) {
            $company = Company::where('code', $request->company_code)->first();
            if ($company) {
                $updateData['company_id'] = $company->id;
            } else {
                return response()->json(['message' => 'Código de empresa inválido'], 422);
            }
        }

        // 7. Ejecutar la actualización (Laravel solo generará el SET de los campos contenidos en $updateData)
        $user->update($updateData);

        // 8. Sincronizar Roles de manera inteligente
        // Si la casilla 'roles' viene en el request (aunque esté vacía), se procesa
        if ($request->has('roles')) {
            $rolesIds = is_array($request->roles) ? array_map('intval', $request->roles) : [];
            $user->syncRoles($rolesIds);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Usuario actualizado correctamente'
        ]);
    }
}
