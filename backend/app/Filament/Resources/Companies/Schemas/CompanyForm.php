<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Plan;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Empresa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required(),
                        Select::make('status')->label('Estado')->options(CompanyStatus::class)->required(),
                        Select::make('plan_id')
                            ->label('Plan')
                            ->required()
                            // Cambiar a otro tipo de BD requiere migrar los datos; por ahora solo planes del mismo tipo
                            ->options(fn (?Company $record) => Plan::query()
                                ->when($record?->database, fn ($query) => $query->where('database_tier', $record->plan->database_tier))
                                ->pluck('name', 'id'))
                            ->helperText('Solo se muestran planes con el mismo tipo de base de datos.'),
                        DateTimePicker::make('trial_ends_at')->label('Fin de la prueba'),
                    ]),
                Section::make('Extras contratados')
                    ->columns(2)
                    ->schema([
                        TextInput::make('extra_employee_blocks')->label('Bloques de empleados')->integer()->minValue(0)->required(),
                        TextInput::make('extra_offices')->label('Oficinas extra')->integer()->minValue(0)->required(),
                    ]),
                Section::make('Módulos')
                    ->columns(3)
                    ->schema([
                        Toggle::make('employees_can_use_web')->label('Empleados pueden usar la web'),
                        Toggle::make('payroll_enabled')->label('Sueldos'),
                        Toggle::make('bonuses_enabled')->label('Bonos'),
                    ]),
            ]);
    }
}
