<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Qué empleados puede ver cada usuario: Owner/Admin todos, Gerente los de
 * las áreas que supervisa, Empleado solo a sí mismo.
 */
class Visibility
{
    /**
     * @return Builder<Employee>
     */
    public static function employees(User $user): Builder
    {
        $query = Employee::query();

        return match ($user->primaryRole()) {
            Role::Owner, Role::Admin => $query,
            Role::Manager => $query->whereIn('area_id', self::managedAreaIds($user)),
            Role::Employee => $query->whereKey($user->employee_id ?? 0),
        };
    }

    public static function canSeeEmployee(User $user, int $employeeId): bool
    {
        return self::employees($user)->whereKey($employeeId)->exists();
    }

    /**
     * @return list<int>
     */
    private static function managedAreaIds(User $user): array
    {
        $employee = $user->employee();

        return $employee ? $employee->managedAreas()->pluck('areas.id')->all() : [];
    }
}
