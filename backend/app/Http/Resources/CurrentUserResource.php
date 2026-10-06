<?php

namespace App\Http\Resources;

use App\Enums\CompanyStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use App\Support\StoredImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class CurrentUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $company = $this->company;
        // Mientras elige plan, la empresa aún no tiene base (ni empleados)
        $employee = $company->database ? $this->employee() : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->primaryRole()->value,
            'role_label' => $this->primaryRole()->label(),
            // Contraseña temporal: debe cambiarla y crear su PIN antes de seguir
            'must_change_password' => (bool) $this->must_change_password,
            // Dueño y administradores pueden calificar AsistControl
            'can_rate' => $this->is_owner || $this->hasRole(Role::Admin->value),
            'permissions' => $this->is_owner
                ? array_map(fn (Permission $permission) => $permission->value, Permission::cases())
                : $this->getAllPermissions()->pluck('name'),
            // Foto de la cuenta; null si todavía no tiene
            'avatar_url' => StoredImage::url('web.sesion.perfil.avatar.ver', $this->avatar_path),
            'employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'employee_code' => $employee->employee_code,
                'area_id' => $employee->area_id,
                'office_id' => $employee->office_id,
                'shift_id' => $employee->shift_id,
                'has_pin' => $employee->pin_hash !== null,
            ] : null,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo_url' => StoredImage::url('web.empresa.logo.ver', $company->logo_path),
                'code' => $company->code,
                'timezone' => $company->timezone,
                'status' => $company->status->value,
                'status_label' => $company->status->label(),
                'plan' => $company->plan->name,
                'plan_slug' => $company->plan->slug,
                'billing_interval' => $company->billing_interval,
                'onboarding' => $company->status === CompanyStatus::Onboarding,
                'trial_ends_at' => $company->trial_ends_at,
                'past_due_since' => $company->past_due_since,
                'includes_payroll' => (bool) $company->plan->includes_payroll,
                'employees_can_use_web' => $company->employees_can_use_web,
                'payroll_enabled' => $company->moduleAvailable('payroll'),
                'bonuses_enabled' => $company->moduleAvailable('bonuses'),
                'limits' => [
                    'employees' => $company->employeeLimit(),
                    'offices' => $company->officeLimit(),
                ],
            ],
            // Clima del encabezado: el empleado ve el de su oficina; dueño/admin
            // lo usan de respaldo si niegan la geolocalización del navegador.
            'weather_location' => $company->database ? $this->weatherLocation($employee) : null,
        ];
    }

    /**
     * Oficina del empleado (si tiene coordenadas) o, si no, la primera oficina
     * de la empresa con coordenadas (la principal primero).
     *
     * @return array{latitude: float, longitude: float, place: string}|null
     */
    private function weatherLocation(?Employee $employee): ?array
    {
        $office = $employee?->office_id ? Office::query()->find($employee->office_id) : null;

        if (! $office?->hasLocation()) {
            $office = Office::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
        }

        return $office ? [
            'latitude' => $office->latitude,
            'longitude' => $office->longitude,
            'place' => $office->name,
        ] : null;
    }
}
