<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    private const ASSIGNABLE_ROLES = ['admin', 'employee'];

    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $users = $company->users()
            ->with(['roles:id,name', 'employee:id,user_id,first_name,last_name,employee_code,is_area_manager,office_id,area_id'])
            ->orderBy('email')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'has_device' => ! empty($user->device_token),
                    'roles' => $user->roles->pluck('name'),
                    'employee' => $user->employee,
                    'created_at' => $user->created_at,
                ];
            });

        return response()->json(['users' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:' . implode(',', self::ASSIGNABLE_ROLES),
            'employee_id' => 'nullable|integer|exists:employees,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = DB::transaction(function () use ($company, $request) {
            $user = User::create([
                'company_id' => $company->id,
                'email' => $request->email,
                'password' => $request->password,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $user->assignRole($request->role);

            if ($request->filled('employee_id')) {
                $employee = $company->employees()->findOrFail($request->employee_id);
                $employee->update(['user_id' => $user->id]);
            }

            AuditLog::record($company->id, $request->user()->id, 'user.created', User::class, $user->id, [
                'email' => $user->email,
                'role' => $request->role,
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user' => $user->load('roles:id,name', 'employee'),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $user = $company->users()->findOrFail($id);

        if ($user->hasRole('owner') && ! $request->user()->hasRole('owner')) {
            return response()->json(['message' => 'No puedes modificar al propietario.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'nullable|in:' . implode(',', self::ASSIGNABLE_ROLES),
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update([
            'email' => $request->email,
            'is_active' => $request->boolean('is_active', $user->is_active),
        ]);

        if ($request->filled('role') && ! $user->hasRole('owner')) {
            $user->syncRoles([$request->role]);
        }

        AuditLog::record($company->id, $request->user()->id, 'user.updated', User::class, $user->id, [
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Usuario actualizado.',
            'user' => $user->load('roles:id,name', 'employee'),
        ]);
    }

    public function resetPassword(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $user = $company->users()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::record($company->id, $request->user()->id, 'user.password_reset', User::class, $user->id);

        return response()->json(['message' => 'Contraseña restablecida.']);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $user = $company->users()->findOrFail($id);

        if ($user->hasRole('owner')) {
            return response()->json(['message' => 'No puedes eliminar al propietario.'], 422);
        }

        DB::transaction(function () use ($company, $request, $user) {
            Employee::where('user_id', $user->id)->update(['user_id' => null]);
            AuditLog::record($company->id, $request->user()->id, 'user.deleted', User::class, $user->id, [
                'email' => $user->email,
            ]);
            $user->delete();
        });

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'roles' => [
                ['name' => 'admin', 'label' => 'Administrador'],
                ['name' => 'employee', 'label' => 'Empleado'],
            ],
        ]);
    }
}
