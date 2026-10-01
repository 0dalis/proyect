<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case OnTime = 'on_time';
    case Late = 'late';
    case Absent = 'absent';
    case EarlyLeave = 'early_leave';

    public function label(): string
    {
        return match ($this) {
            self::OnTime => 'A tiempo',
            self::Late => 'Retardo',
            self::Absent => 'Falta',
            self::EarlyLeave => 'Salida anticipada',
        };
    }
}
