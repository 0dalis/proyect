<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Plan')->searchable()->description(fn ($record) => $record->slug),
                TextColumn::make('database_tier')->label('Base de datos')->badge(),
                TextColumn::make('monthly_price')->label('Mensual')->money('MXN'),
                TextColumn::make('included_employees')->label('Empleados'),
                TextColumn::make('included_offices')->label('Oficinas'),
                TextColumn::make('trial_days')->label('Prueba')->placeholder('Global')->suffix(' días'),
                TextColumn::make('companies_count')->label('Empresas')->counts('companies'),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                IconColumn::make('is_public')->label('Público')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
