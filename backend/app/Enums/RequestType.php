<?php

namespace App\Enums;

enum RequestType: string
{
    case Justification = 'justification';
    case LateArrival = 'late_arrival';
    case EarlyDeparture = 'early_departure';
    case Vacation = 'vacation';
    case Leave = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::Justification => 'Justificación',
            self::LateArrival => 'Aviso de llegada tarde',
            self::EarlyDeparture => 'Salida anticipada',
            self::Vacation => 'Vacaciones',
            self::Leave => 'Permiso',
        };
    }
}
