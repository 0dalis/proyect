<?php

namespace App\Enums;

enum AttendanceType: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';

    public function label(): string
    {
        return match ($this) {
            self::CheckIn => 'Entrada',
            self::CheckOut => 'Salida',
        };
    }
}
