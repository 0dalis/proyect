<?php

namespace App\Filament\Resources\SuperAdmins\Tables;

use App\Models\SuperAdmin;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuperAdminsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable(),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('created_at')->label('Alta')->date('d/m/Y'),
            ])
            ->recordActions([
                EditAction::make(),
                // Nadie puede borrarse a sí mismo ni dejar la plataforma sin Super Admin
                DeleteAction::make()->hidden(fn (SuperAdmin $record) => $record->is(Filament::auth()->user()) || SuperAdmin::query()->count() === 1),
            ]);
    }
}
