<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\SuperAdminAuditLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora del Super Admin (solo lectura): quién hizo qué y sobre qué empresa.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = SuperAdminAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'bitácora';

    protected static ?string $navigationLabel = 'Bitácora';

    protected static ?string $slug = 'audit-log';

    protected static ?int $navigationSort = 6;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i', 'America/Mexico_City')->sortable(),
                TextColumn::make('super_admin_name')->label('Super Admin')->placeholder('Sistema')->searchable(),
                TextColumn::make('action')->label('Acción')->badge()
                    ->formatStateUsing(fn (string $state) => SuperAdminAuditLog::ACTIONS[$state] ?? $state)
                    ->color(fn (string $state) => match (true) {
                        str_starts_with($state, 'support.') => 'warning',
                        in_array($state, ['company.suspended', 'plan.deleted', 'super_admin.deleted', 'mfa.disabled'], true) => 'danger',
                        in_array($state, ['company.reactivated', 'mfa.enabled', 'rating.published'], true) => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('company.name')->label('Empresa')->placeholder('-')->searchable(),
                TextColumn::make('description')->label('Detalle')->wrap()->searchable(),
            ])
            ->filters([
                SelectFilter::make('action')->label('Acción')->options(SuperAdminAuditLog::ACTIONS),
                SelectFilter::make('super_admin_id')->label('Super Admin')->relationship('superAdmin', 'name'),
                SelectFilter::make('company_id')->label('Empresa')->relationship('company', 'name')->searchable(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (SuperAdminAuditLog $record) => filled($record->properties))
                    ->modalHeading(fn (SuperAdminAuditLog $record) => $record->actionLabel())
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->schema(fn (SuperAdminAuditLog $record) => [
                        TextEntry::make('description')->label('Detalle')->state($record->description),
                        KeyValueEntry::make('properties')->label('Datos')
                            ->state(collect($record->properties)->map(fn ($value) => is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE))->all()),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
