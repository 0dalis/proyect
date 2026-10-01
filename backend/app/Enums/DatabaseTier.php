<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DatabaseTier: string implements HasLabel
{
    case Basic = 'basic';
    case Plus = 'plus';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Compartida (Free / Básico)',
            self::Plus => 'Compartida Plus',
            self::Premium => 'Dedicada',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
