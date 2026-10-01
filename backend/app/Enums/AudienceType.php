<?php

namespace App\Enums;

enum AudienceType: string
{
    case All = 'all';
    case Areas = 'areas';
    case Offices = 'offices';
    case Roles = 'roles';
    case Employees = 'employees';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Toda la empresa',
            self::Areas => 'Áreas',
            self::Offices => 'Oficinas',
            self::Roles => 'Roles',
            self::Employees => 'Empleados específicos',
        };
    }
}
