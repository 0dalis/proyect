<?php

namespace App\Actions;

use App\Enums\CompanyStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Area;
use App\Models\Company;
use App\Models\Office;
use App\Models\Shift;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Alta de la empresa en dos momentos:
 * 1. prepareOwner(): el dueño confirmó su correo. Puede entrar, pero solo ve
 *    el modal de bienvenida (términos, plan y tarjeta).
 * 2. handle(): eligió plan. Crea (o reutiliza) la BD según el plan y deja la
 *    empresa lista, en prueba o activa (Free).
 */
class ActivateCompany
{
    public function __construct(private TenantManager $tenants) {}

    public function prepareOwner(Company $company): void
    {
        if ($company->status !== CompanyStatus::Pending) {
            return;
        }

        // Aún no hay base conectada: los roles se buscan por empresa (Spatie teams)
        $previousTeam = getPermissionsTeamId();
        setPermissionsTeamId($company->id);

        try {
            $this->createRoles($company);
            $company->owner->assignRole(Role::Owner->value);
        } finally {
            setPermissionsTeamId($previousTeam);
        }

        $company->forceFill(['status' => CompanyStatus::Onboarding])->save();
    }

    public function handle(Company $company, CompanyStatus $status = CompanyStatus::Trial, ?Carbon $trialEndsAt = null): void
    {
        if (! in_array($company->status, [CompanyStatus::Pending, CompanyStatus::Onboarding], true)) {
            return;
        }

        $this->tenants->provision($company);

        $this->createRoles($company);
        $company->owner->assignRole(Role::Owner->value);

        $this->createDefaultOrganization($company);

        $company->forceFill([
            'status' => $status,
            'trial_ends_at' => $status === CompanyStatus::Trial
                ? ($trialEndsAt ?? now()->addDays($company->plan->effectiveTrialDays()))
                : null,
            'activated_at' => now(),
        ])->save();
    }

    public static function ensurePermissionsExist(): void
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }
    }

    private function createRoles(Company $company): void
    {
        self::ensurePermissionsExist();

        foreach (Role::cases() as $role) {
            $roleModel = RoleModel::query()->firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'web',
                'company_id' => $company->id,
            ]);

            $roleModel->syncPermissions(array_map(fn (Permission $p) => $p->value, Permission::defaultsFor($role)));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createDefaultOrganization(Company $company): void
    {
        $office = Office::query()->create([
            'name' => 'Oficina principal',
            'geofence_radius' => 50,
            'timezone' => $company->timezone,
            'is_default' => true,
        ]);

        Shift::query()->create([
            'office_id' => $office->id,
            'name' => 'Turno matutino',
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'weekdays' => [1, 2, 3, 4, 5],
            'tolerance_minutes' => 15,
            'absence_after_minutes' => 30,
            'is_default' => true,
        ]);

        Area::query()->create(['name' => 'General']);
    }
}
