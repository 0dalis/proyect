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
    public static function middleware(): array
    {
        return ['auth'];
    }

    public function index()
    {
        $users = User::with(['roles', 'company', 'employee'])->get();
        $roles = Role::all();
        return view('system.user', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        $request->validate([
            'email'        => 'required|email|unique:users,email',
            'password'     => 'nullable|string|min:6',
            'is_active'    => 'required|boolean',
            'roles'        => 'nullable|array',
            'company_code' => 'nullable|string',
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
            'email'      => $request->email,
            'password'   => $request->password,
            'is_active'  => $request->is_active,
            'company_id' => $companyId,
        ]);

        if ($request->filled('roles')) {
            $rolesIds = array_map('intval', $request->roles);
            $user->syncRoles($rolesIds);
        } else {
            $user->syncRoles([]);
        }

        return response()->json(['success' => true, 'message' => 'Usuario creado correctamente']);
    }

    public function update(Request $request)
    {
        $user = User::findOrFail($request->userid);

        $request->merge([
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        $validated = $request->validate([
            'email'        => 'required|email|unique:users,email,' . $user->id,
            'password'     => 'nullable|string|min:6',
            'is_active'    => 'required|in:0,1',
            'roles'        => 'nullable|array',
            'roles.*'      => 'integer|exists:roles,id',
            'company_code' => 'nullable|string',
        ]);

        $updateData = $request->only(['email', 'is_active']);

        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        if ($request->filled('company_code')) {
            $company = Company::where('code', $request->company_code)->first();
            if ($company) {
                $updateData['company_id'] = $company->id;
            } else {
                return response()->json(['message' => 'Código de empresa inválido'], 422);
            }
        }

        $user->update($updateData);

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
