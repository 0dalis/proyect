<?php

namespace App\Http\Controllers\Api;

use App\Actions\ActivateCompany;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matriz de permisos por rol. Solo el Owner puede modificarla.
 */
class RolePermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = RoleModel::query()
            ->where('company_id', $request->user()->company_id)
            ->with('permissions:id,name')
            ->get()
            ->map(function (RoleModel $roleModel) {
                $role = Role::from($roleModel->name);

                return [
                    'id' => $roleModel->id,
                    'name' => $role->value,
                    'label' => $role->label(),
                    'editable' => $role !== Role::Owner,
                    'permissions' => $roleModel->permissions->pluck('name'),
                    'locked' => array_map(fn (Permission $p) => $p->value, Permission::lockedFor($role)),
                ];
            });

        return response()->json([
            'roles' => $roles,
            'catalog' => array_map(fn (Permission $p) => ['name' => $p->value, 'label' => $p->label()], Permission::cases()),
        ]);
    }

    public function update(Request $request, string $roleName): JsonResponse
    {
        abort_unless($request->user()->is_owner, 403, 'Solo el dueño puede modificar permisos.');

        $role = Role::tryFrom($roleName);
        abort_if($role === null || $role === Role::Owner, 422, 'Este rol no se puede modificar.');

        $allowed = array_diff(
            array_map(fn (Permission $p) => $p->value, Permission::cases()),
            array_map(fn (Permission $p) => $p->value, Permission::lockedFor($role)),
        );

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in($allowed)],
        ], [
            'permissions.*.in' => 'Ese permiso no se puede otorgar a este rol.',
        ]);

        $roleModel = RoleModel::query()
            ->where('company_id', $request->user()->company_id)
            ->where('name', $role->value)
            ->firstOrFail();

        ActivateCompany::ensurePermissionsExist();
        $before = $roleModel->permissions()->pluck('name')->all();
        $roleModel->syncPermissions($data['permissions']);

        ActivityLogger::log('permissions_changed', description: "Cambió los permisos del rol {$role->label()}", changes: [
            'permisos' => ['old' => $before, 'new' => $data['permissions']],
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['permissions' => $roleModel->permissions()->pluck('name')]);
    }
}
