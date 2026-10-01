<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\EmployeeStatus;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Tenancy\TenantManager;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Empresa')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Nombre'),
                        TextEntry::make('code')->label('Código de empresa')->copyable()->fontFamily('mono'),
                        TextEntry::make('owner.email')->label('Correo maestro')->copyable(),
                        TextEntry::make('status')->label('Estado')->badge(),
                        TextEntry::make('plan.name')->label('Plan'),
                        TextEntry::make('database')->label('Base de datos')->placeholder('Sin crear (correo sin verificar)')
                            ->icon(fn (?string $state) => $state && CompaniesTable::databaseOnline($state) ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                            ->iconColor(fn (?string $state) => $state && CompaniesTable::databaseOnline($state) ? 'success' : 'danger')
                            ->helperText(fn (?string $state) => $state ? (CompaniesTable::databaseOnline($state) ? 'En línea' : 'Sin conexión') : null),
                        TextEntry::make('monthly_price')->label('Mensualidad estimada')
                            ->state(fn (Company $record) => $record->estimatedMonthlyPrice())
                            ->money('MXN'),
                        TextEntry::make('trial_ends_at')->label('Fin de la prueba')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('created_at')->label('Registro')->dateTime('d/m/Y H:i'),
                        TextEntry::make('activated_at')->label('Correo verificado')->dateTime('d/m/Y H:i')->placeholder('-'),
                    ]),
                Section::make('Uso del plan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('employees_usage')->label('Empleados activos')
                            ->state(fn (Company $record) => self::usage($record, fn () => Employee::query()->where('status', EmployeeStatus::Active)->count(), $record->employeeLimit())),
                        TextEntry::make('offices_usage')->label('Oficinas')
                            ->state(fn (Company $record) => self::usage($record, fn () => Office::query()->count(), $record->officeLimit())),
                        TextEntry::make('app_users')->label('Empleados con app')
                            ->state(fn (Company $record) => $record->users()->where('is_owner', false)->whereNotNull('employee_id')->where('app_access', true)->count()),
                    ]),
                Section::make('Módulos y facturación')
                    ->columns(3)
                    ->schema([
                        IconEntry::make('employees_can_use_web')->label('Empleados en web')->boolean(),
                        IconEntry::make('payroll_enabled')->label('Sueldos')->boolean(),
                        IconEntry::make('bonuses_enabled')->label('Bonos')->boolean(),
                        TextEntry::make('stripe_id')->label('Cliente Stripe')->placeholder('Sin cliente'),
                        TextEntry::make('pm_last_four')->label('Tarjeta')->placeholder('-')->prefix('•••• '),
                    ]),
            ]);
    }

    private static function usage(Company $company, callable $count, int $limit): string
    {
        if (! $company->database) {
            return "0 / {$limit}";
        }

        return app(TenantManager::class)->run($company, $count)." / {$limit}";
    }
}
