<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Plan;

class PlanLimitsService
{
    /**
     * Obtiene el plan efectivo de la empresa (o el Plan Gratis si no tiene plan).
     */
    public function planFor(Company $company): ?Plan
    {
        if ($company->plan) {
            return $company->plan;
        }

        return Plan::where('tipo', 'Free')->first() ?? Plan::orderBy('precio')->first();
    }

    /**
     * Conteo de empleados de la empresa EXCLUYENDO al owner (no marca asistencia).
     * Incluye a los admins que son también empleados (marcan asistencia).
     */
    public function countEmployees(Company $company): int
    {
        return $company->employees()
            ->where(function ($query) {
                $query->whereNull('user_id')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->whereDoesntHave('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'owner');
                        });
                    });
            })
            ->count();
    }

    /**
     * Límite de empleados permitidos por el plan (Free = 8).
     */
    public function employeeLimit(Company $company): int
    {
        return (int) ($this->planFor($company)?->max_users ?? 8);
    }

    /**
     * Conteo de oficinas de la empresa.
     */
    public function countOffices(Company $company): int
    {
        return $company->offices()->count();
    }

    /**
     * Límite de oficinas del plan (Free = 1). null = ilimitado.
     */
    public function officeLimit(Company $company): ?int
    {
        $limit = $this->planFor($company)?->max_offices;

        return $limit === null ? null : (int) $limit;
    }

    /**
     * ¿Se puede crear un nuevo empleado?
     */
    public function canCreateEmployee(Company $company): bool
    {
        $limit = $this->employeeLimit($company);

        return $this->countEmployees($company) < $limit;
    }

    /**
     * ¿Se puede crear una nueva oficina?
     */
    public function canCreateOffice(Company $company): bool
    {
        $limit = $this->officeLimit($company);

        if ($limit === null) {
            return true;
        }

        return $this->countOffices($company) < $limit;
    }

    /**
     * ¿El empleado está dentro de los permitidos para editar?
     * (En Free solo los primeros N registrados son editables.)
     */
    public function canEditEmployee(Company $company, Employee $employee): bool
    {
        $limit = $this->employeeLimit($company);

        $allowedIds = $company->employees()
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        return $allowedIds->contains($employee->id);
    }

    /**
     * ¿La oficina está dentro de las permitidas para editar?
     * (En Free solo la primera oficina es editable.)
     */
    public function canEditOffice(Company $company, Office $office): bool
    {
        $limit = $this->officeLimit($company);

        if ($limit === null) {
            return true;
        }

        $allowedIds = $company->offices()
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        return $allowedIds->contains($office->id);
    }

    /**
     * ¿La empresa tiene habilitada una característica específica del plan?
     * Soporta features como lista de strings o como array asociativo [clave => bool].
     */
    public function hasFeature(Company $company, string $feature): bool
    {
        $features = $this->planFor($company)?->features;

        if (! is_array($features)) {
            return false;
        }

        if (array_is_list($features)) {
            return in_array($feature, $features);
        }

        return (bool) ($features[$feature] ?? false);
    }

    /**
     * ¿El empleado puede registrar asistencia según el plan?
     * (En Free solo los primeros N empleados pueden marcar asistencia.)
     */
    public function canRegisterAttendance(Company $company, Employee $employee): bool
    {
        return $this->canEditEmployee($company, $employee);
    }

    /**
     * Resumen de límites para el frontend (estado de suscripción y límites).
     */
    public function summary(Company $company): array
    {
        $plan = $this->planFor($company);

        return [
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->nombre,
            'plan_type' => $plan?->tipo,
            'employee_limit' => $this->employeeLimit($company),
            'employee_count' => $this->countEmployees($company),
            'can_create_employee' => $this->canCreateEmployee($company),
            'office_limit' => $this->officeLimit($company),
            'office_count' => $this->countOffices($company),
            'can_create_office' => $this->canCreateOffice($company),
            'features' => $plan?->features ?? [],
            'is_active' => $company->is_active,
            'trial_ends_at' => $company->trial_ends_at,
            'subscription_ends_at' => $company->subscription_ends_at,
            'has_active_subscription' => $company->hasActiveSubscription(),
            'is_on_trial' => $company->isOnTrial(),
        ];
    }
}
