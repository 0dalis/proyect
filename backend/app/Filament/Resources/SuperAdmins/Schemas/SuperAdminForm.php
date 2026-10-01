<?php

namespace App\Filament\Resources\SuperAdmins\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SuperAdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cuenta de Super Admin')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required(),
                        TextInput::make('email')->label('Correo')->email()->required()->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->minLength(10)
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->helperText('Déjala vacía para no cambiarla.'),
                    ]),
            ]);
    }
}
