<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Temporary = 'temporary';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Planta',
            self::Temporary => 'Temporal',
        };
    }
}
