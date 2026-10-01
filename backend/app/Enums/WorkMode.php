<?php

namespace App\Enums;

enum WorkMode: string
{
    case Onsite = 'onsite';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'Presencial',
            self::Remote => 'Home office',
        };
    }
}
