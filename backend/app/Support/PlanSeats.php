<?php

namespace App\Support;

use App\Enums\EmployeeStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;

/**
 * Lugares del plan. Si la empresa tiene más empleados activos de los que
 * permite su plan (por ejemplo, bajó a Free), solo los primeros (por fecha de
 * alta) se pueden usar; el resto se ve en las listas pero queda bloqueado:
 * no checa, no entra y no se puede modificar hasta que se pague un plan mayor.
 *
 * Requiere la empresa conectada (TenantManager).
 */
class PlanSeats
{
    /**
     * @return list<int>
     */
    public static function lockedEmployeeIds(Company $company): array
    {
        $limit = $company->employeeLimit();

        return Employee::query()
            ->where('status', EmployeeStatus::Active)
            ->orderBy('id')
            ->pluck('id')
            ->slice($limit)
            ->values()
            ->all();
    }

    /**
     * Bloqueado si ya hay tantos empleados activos más antiguos como lugares
     * tiene el plan (una sola consulta; corre en cada petición).
     */
    public static function isLocked(Company $company, int $employeeId): bool
    {
        return Employee::query()
            ->where('status', EmployeeStatus::Active)
            ->where('id', '<', $employeeId)
            ->count() >= $company->employeeLimit();
    }

    /**
     * El dueño nunca se bloquea; los demás, si su empleado quedó fuera del plan.
     */
    public static function userIsLocked(User $user): bool
    {
        return ! $user->is_owner
            && $user->employee_id !== null
            && self::isLocked($user->company, $user->employee_id);
    }

    public static function message(Company $company): string
    {
        return "Este empleado está fuera del límite de tu plan ({$company->employeeLimit()} empleados). "
            .'Mejora tu plan para volver a usarlo.';
    }
}
