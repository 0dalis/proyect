<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Dueño',
            self::Admin => 'Administrador',
            self::Manager => 'Gerente',
            self::Employee => 'Empleado',
        };
    }

    /**
     * Roles que puede otorgar o quitar alguien con este rol.
     *
     * @return list<self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::Owner => [self::Admin, self::Manager, self::Employee],
            self::Admin => [self::Manager, self::Employee],
            default => [],
        };
    }
}
