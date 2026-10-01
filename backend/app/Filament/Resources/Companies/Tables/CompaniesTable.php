<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\SuperAdminAuditLog;
use App\Support\PlatformMetrics;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    /**
     * Usa el sondeo en caché del tablero para no conectarse a cada base por fila.
     */
    public static function databaseOnline(string $database): bool
    {
        return (bool) (collect(app(PlatformMetrics::class)->databases())->firstWhere('name', $database)['online'] ?? false);
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Empresa')->searchable()->description(fn (Company $record) => $record->owner?->email),
                TextColumn::make('plan.name')->label('Plan')->badge()->color('gray'),
                TextColumn::make('status')->label('Estado')->badge()->color(fn (CompanyStatus $state) => match ($state) {
                    CompanyStatus::Active => 'success',
                    CompanyStatus::Trial => 'info',
                    CompanyStatus::Pending, CompanyStatus::Onboarding => 'gray',
                    CompanyStatus::PastDue => 'warning',
                    default => 'danger',
                }),
                TextColumn::make('users_count')->label('Usuarios')->counts('users')->sortable()->alignEnd(),
                TextColumn::make('employees_total')->label('Empleados')->alignEnd()
                    ->state(fn (Company $record) => app(PlatformMetrics::class)->countsByCompany()[$record->id]['employees'] ?? 0)
                    ->description(fn (Company $record) => 'de '.$record->employeeLimit()),
                TextColumn::make('offices_total')->label('Oficinas')->alignEnd()->toggleable()
                    ->state(fn (Company $record) => app(PlatformMetrics::class)->countsByCompany()[$record->id]['offices'] ?? 0),
                TextColumn::make('monthly_price')->label('Mensualidad')
                    ->state(fn (Company $record) => $record->estimatedMonthlyPrice())
                    ->money('MXN'),
                TextColumn::make('database')->label('Base de datos')->placeholder('Sin crear')->toggleable()
                    ->icon(fn (?string $state) => $state === null ? null : (self::databaseOnline($state) ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'))
                    ->iconColor(fn (?string $state) => $state !== null && self::databaseOnline($state) ? 'success' : 'danger'),
                TextColumn::make('trial_ends_at')->label('Fin de prueba')->date('d/m/Y')->placeholder('-')->sortable(),
                TextColumn::make('created_at')->label('Registro')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(CompanyStatus::class),
                SelectFilter::make('plan')->label('Plan')->relationship('plan', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('extendTrial')
                        ->label('Extender prueba')
                        ->icon('heroicon-o-clock')
                        ->schema([TextInput::make('days')->label('Días')->integer()->minValue(1)->default(7)->required()])
                        ->visible(fn (Company $record) => $record->status === CompanyStatus::Trial)
                        ->action(function (Company $record, array $data) {
                            $base = $record->trial_ends_at?->isFuture() ? $record->trial_ends_at : now();
                            $before = $record->trial_ends_at;
                            $record->update(['trial_ends_at' => $base->copy()->addDays((int) $data['days'])]);
                            SuperAdminAuditLog::record('company.trial_extended', "Extendió {$data['days']} días la prueba de {$record->name}", $record, [
                                'from' => $before?->toIso8601String(), 'to' => $record->trial_ends_at->toIso8601String(),
                            ]);
                            Notification::make()->title('Prueba extendida')->success()->send();
                        }),
                    Action::make('suspend')
                        ->label('Suspender')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Company $record) => $record->status->canOperate())
                        ->action(function (Company $record) {
                            $record->forceFill(['status' => CompanyStatus::Suspended, 'suspended_at' => now()])->save();
                            SuperAdminAuditLog::record('company.suspended', "Suspendió {$record->name}", $record);
                        }),
                    Action::make('reactivate')
                        ->label('Reactivar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Company $record) => $record->status === CompanyStatus::Suspended)
                        ->action(function (Company $record) {
                            $record->forceFill([
                                'status' => $record->trial_ends_at?->isFuture() ? CompanyStatus::Trial : CompanyStatus::Active,
                                'suspended_at' => null,
                            ])->save();
                            SuperAdminAuditLog::record('company.reactivated', "Reactivó {$record->name}", $record);
                        }),
                ]),
            ]);
    }
}
