<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\DatabaseTier;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, $get) => $get('slug') ?: $set('slug', Str::slug($state))),
                        TextInput::make('slug')->label('Identificador')->required()->unique(ignoreRecord: true),
                        Textarea::make('description')->label('Descripción')->columnSpanFull(),
                        Select::make('database_tier')
                            ->label('Base de datos')
                            ->options(DatabaseTier::class)
                            ->default(DatabaseTier::Basic)
                            ->helperText('Free/Básico comparten BD; Plus comparte solo con Plus; Premium tiene BD dedicada.')
                            ->required(),
                        TextInput::make('monthly_price')->label('Precio mensual')->required()->numeric()->minValue(0)->prefix('$'),
                        TextInput::make('trial_days')->label('Días de prueba')->numeric()->minValue(0)
                            ->helperText('Vacío = usa los días de prueba globales.'),
                        TagsInput::make('features')->label('Características (landing)')->columnSpanFull(),
                    ]),
                Section::make('Límites incluidos')
                    ->columns(2)
                    ->description('Cada empleado puede tener usuario para la app sin costo aparte.')
                    ->schema([
                        TextInput::make('included_employees')->label('Empleados')->required()->integer()->minValue(1),
                        TextInput::make('included_offices')->label('Oficinas')->required()->integer()->minValue(1),
                    ]),
                Section::make('Extras')
                    ->columns(2)
                    ->schema([
                        TextInput::make('employee_block_size')->label('Empleados por bloque extra')->required()->integer()->minValue(1),
                        TextInput::make('employee_block_price')->label('Precio por bloque')->required()->numeric()->prefix('$'),
                        TextInput::make('extra_office_price')->label('Precio por oficina extra')->required()->numeric()->prefix('$'),
                    ]),
                Section::make('Stripe')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('stripe_product_id')->label('Producto'),
                        TextInput::make('stripe_price_id')->label('Precio del plan'),
                        TextInput::make('stripe_employee_block_price_id')->label('Precio bloque de empleados'),
                        TextInput::make('stripe_extra_office_price_id')->label('Precio oficina extra'),
                    ]),
                Section::make('Visibilidad')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Activo')->default(true),
                        Toggle::make('is_public')->label('Visible en la landing')->default(true),
                        TextInput::make('sort_order')->label('Orden')->integer()->default(0),
                    ]),
            ]);
    }
}
