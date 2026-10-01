<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Company;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

/**
 * Roles y permisos del sistema (solo lectura). Los permisos están en el código
 * (App\Enums\Permission) y cada empresa tiene sus propios roles; el dueño
 * ajusta los de su empresa desde su panel.
 *
 * - Matriz: qué permiso trae cada rol al crear una empresa y cuáles nunca se pueden dar.
 * - Uso: usuarios por rol en toda la plataforma y empresas que personalizaron sus roles.
 * - Por empresa: los permisos que tiene hoy cada rol de una empresa.
 */
class RolesAndPermissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Roles y permisos';

    protected static ?string $title = 'Roles y permisos';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.roles-and-permissions';

    /** Empresa elegida para ver sus roles actuales. */
    public ?int $companyId = null;

    /**
     * @return list<array{key: string, label: string, roles: array<string, string>}>
     *                                                                               roles: default | locked | off
     */
    public function matrix(): array
    {
        return array_map(fn (Permission $permission) => [
            'key' => $permission->value,
            'label' => $permission->label(),
            'roles' => collect(Role::cases())->mapWithKeys(fn (Role $role) => [
                $role->value => match (true) {
                    in_array($permission, Permission::defaultsFor($role), true) => 'default',
                    in_array($permission, Permission::lockedFor($role), true) => 'locked',
                    default => 'off',
                },
            ])->all(),
        ], Permission::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function roles(): array
    {
        return array_map(fn (Role $role) => ['value' => $role->value, 'label' => $role->label()], Role::cases());
    }

    /**
     * Usuarios con cada rol en todas las empresas (el dueño es uno por empresa).
     *
     * @return array<string, int>
     */
    public function usersByRole(): array
    {
        $counts = DB::connection('central')->table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->selectRaw('roles.name, count(distinct model_has_roles.model_id) as total')
            ->groupBy('roles.name')
            ->pluck('total', 'name');

        return collect(Role::cases())->mapWithKeys(fn (Role $role) => [
            $role->value => $role === Role::Owner
                ? DB::connection('central')->table('users')->where('is_owner', true)->count()
                : (int) ($counts[$role->value] ?? 0),
        ])->all();
    }

    /**
     * Empresas cuyos roles ya no tienen los permisos iniciales.
     *
     * @return array<string, int>
     */
    public function customizedRoles(): array
    {
        $result = [];

        foreach ([Role::Admin, Role::Manager] as $role) {
            $defaults = collect(Permission::defaultsFor($role))->map->value->sort()->values()->all();
            $count = 0;

            foreach ($this->permissionsByCompanyRole($role->value) as $permissions) {
                if ($permissions !== $defaults) {
                    $count++;
                }
            }

            $result[$role->value] = $count;
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function companies(): array
    {
        return Company::query()->whereNotNull('database')->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Permisos actuales de cada rol de la empresa elegida.
     *
     * @return array<string, list<string>> rol => permisos
     */
    public function companyRoles(): array
    {
        if (! $this->companyId) {
            return [];
        }

        $rows = DB::connection('central')->table('roles')
            ->leftJoin('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->leftJoin('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('roles.company_id', $this->companyId)
            ->get(['roles.name as role', 'permissions.name as permission']);

        return $rows->groupBy('role')
            ->map(fn ($items) => $items->pluck('permission')->filter()->values()->all())
            ->all();
    }

    public function permissionLabel(string $key): string
    {
        return Permission::tryFrom($key)?->label() ?? $key;
    }

    /**
     * @return array<int, list<string>> company_id => permisos ordenados
     */
    private function permissionsByCompanyRole(string $role): array
    {
        return DB::connection('central')->table('roles')
            ->leftJoin('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->leftJoin('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('roles.name', $role)
            ->whereNotNull('roles.company_id')
            ->get(['roles.company_id', 'permissions.name'])
            ->groupBy('company_id')
            ->map(fn ($items) => $items->pluck('name')->filter()->sort()->values()->all())
            ->all();
    }
}
