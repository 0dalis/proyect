<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Cuentas de acceso de los empleados (app / web), roles y bloqueos.
 * Las cuentas se crean desde la ficha del empleado (ManageAppAccess).
 */
class UserAccessController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->where('company_id', $request->user()->company_id)
            ->with('roles:id,name')
            ->orderByDesc('is_owner')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'role' => $user->primaryRole()->value,
                'roles' => $user->roles->pluck('name'),
                'app_access' => $user->app_access,
                'web_access' => $user->web_access,
                'blocked_at' => $user->blocked_at,
                'last_login_at' => $user->last_login_at,
            ]);

        return response()->json($users);
    }

    public function updateRoles(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $user);

        $assignable = array_map(fn (Role $role) => $role->value, $actor->primaryRole()->assignableRoles());

        $data = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::in($assignable)],
        ], [
            'roles.*.in' => 'No tienes permiso para asignar ese rol.',
        ]);

        // Solo el Owner puede quitar el rol de Admin a alguien
        if ($user->hasRole(Role::Admin->value) && ! in_array(Role::Admin->value, $data['roles'], true) && $actor->primaryRole() !== Role::Owner) {
            abort(403, 'Solo el dueño puede quitar el rol de administrador.');
        }

        // Todo usuario ligado a un empleado conserva el rol base de empleado
        $roles = array_values(array_unique([...$data['roles'], Role::Employee->value]));
        $before = $user->roles()->pluck('name')->all();
        $user->syncRoles($roles);

        ActivityLogger::log('role_changed', $user, "Cambió el rol de {$user->name}", [
            'roles' => ['old' => $before, 'new' => $roles],
        ]);

        return response()->json(['roles' => $user->roles()->pluck('name')]);
    }

    public function updateAccess(Request $request, User $user): JsonResponse
    {
        $this->ensureManageable($request->user(), $user);

        $data = $request->validate([
            'blocked' => ['sometimes', 'boolean'],
            'app_access' => ['sometimes', 'boolean'],
            'web_access' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('blocked', $data)) {
            $user->blocked_at = $data['blocked'] ? now() : null;
        }

        $user->fill(collect($data)->only(['app_access', 'web_access'])->all())->save();

        $changes = collect($user->getChanges())->except('updated_at')
            ->map(fn ($value, $field) => ['old' => $user->getOriginal($field), 'new' => $value])->all();
        ActivityLogger::log('access_changed', $user, match (true) {
            array_key_exists('blocked', $data) && $data['blocked'] => "Bloqueó el acceso de {$user->name}",
            array_key_exists('blocked', $data) => "Desbloqueó a {$user->name}",
            default => "Cambió el acceso de {$user->name}",
        }, $changes);

        // Al bloquear: cerrar sesiones y revocar sus celulares registrados
        if ($user->isBlocked() || ! $user->app_access) {
            $user->tokens()->delete();
            DB::connection('central')->table('sessions')->where('user_id', $user->id)->delete();

            if ($user->employee_id) {
                Device::query()->where('employee_id', $user->employee_id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }
        }

        return response()->json($user->only(['id', 'app_access', 'web_access', 'blocked_at']));
    }

    private function ensureManageable(User $actor, User $target): void
    {
        abort_unless($target->company_id === $actor->company_id, 404);
        abort_if($target->is_owner, 403, 'Nadie puede modificar al dueño de la empresa.');
        abort_if($target->is($actor), 403, 'No puedes modificar tu propio acceso.');
        abort_if(
            $target->hasRole(Role::Admin->value) && $actor->primaryRole() !== Role::Owner,
            403,
            'Solo el dueño puede modificar a un administrador.'
        );
    }
}
