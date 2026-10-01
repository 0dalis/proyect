<?php

namespace App\Filament\Resources\CompanyDeletions;

use App\Actions\ResendCompanyExport;
use App\Filament\Resources\CompanyDeletions\Pages\ListCompanyDeletions;
use App\Filament\Resources\CompanyDeletions\Pages\ViewCompanyDeletion;
use App\Models\Company;
use App\Models\CompanyDeletion;
use App\Models\SuperAdminAuditLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

/**
 * Bajas de empresas: en proceso (30 días, con días restantes y reenvío del
 * enlace de exportación) y eliminadas (registro histórico sin datos personales).
 */
class CompanyDeletionResource extends Resource
{
    protected static ?string $model = CompanyDeletion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?string $modelLabel = 'eliminación';

    protected static ?string $pluralModelLabel = 'eliminaciones';

    protected static ?string $navigationLabel = 'Eliminaciones';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CompanyDeletion::query()->inProgress()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('company_name')->label('Empresa')->searchable()->weight('medium'),
                TextColumn::make('last_plan')->label('Último plan')->badge()->color('gray')
                    ->description(fn (CompanyDeletion $record) => match ($record->billing_interval) {
                        'year' => 'Anual',
                        'month' => 'Mensual',
                        default => null,
                    }),
                TextColumn::make('days_in_system')->label('Tiempo en el sistema')
                    ->formatStateUsing(fn (int $state) => self::duration($state))
                    ->description(fn (CompanyDeletion $record) => 'Desde '.$record->registered_at->format('d/m/Y'))
                    ->sortable(),
                TextColumn::make('requested_at')->label('Solicitud')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('days_left')->label('Días restantes')
                    ->state(fn (CompanyDeletion $record) => $record->isInProgress() ? $record->daysLeft() : null)
                    ->formatStateUsing(fn (?int $state) => $state === null ? null : ($state === 1 ? '1 día' : "{$state} días"))
                    ->description(fn (CompanyDeletion $record) => $record->isInProgress() ? 'Se borra el '.$record->purge_after->format('d/m/Y') : null)
                    ->badge()
                    ->color(fn (?int $state) => match (true) {
                        $state === null => 'gray',
                        $state <= 3 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'info',
                    })
                    ->placeholder('-')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'in_progress') === 'in_progress'),
                TextColumn::make('export_expires_at')->label('Enlace de exportación')
                    ->state(fn (CompanyDeletion $record) => match (true) {
                        $record->exportLinkIsValid() => 'Vigente hasta '.$record->export_expires_at->format('d/m H:i'),
                        default => 'Vencido',
                    })
                    ->description(fn (CompanyDeletion $record) => $record->export_downloaded_at ? 'Descargado el '.$record->export_downloaded_at->format('d/m/Y H:i') : 'Sin descargar')
                    ->badge()
                    ->color(fn (CompanyDeletion $record) => $record->exportLinkIsValid() ? 'success' : 'gray')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'in_progress') === 'in_progress'),
                TextColumn::make('purged_at')->label('Eliminada')->dateTime('d/m/Y H:i')->sortable()
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? null) === 'purged'),
                TextColumn::make('reason')->label('Motivo')->limit(60)->tooltip(fn (CompanyDeletion $record) => $record->reason)->wrap(),
                TextColumn::make('feedback_rating')->label('Valoración')
                    ->formatStateUsing(fn (?int $state) => $state ? str_repeat('★', $state).str_repeat('☆', 5 - $state) : null)
                    ->placeholder('-')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? null) === 'purged'),
            ])
            ->recordActions([
                ViewAction::make(),
                self::resendExportAction(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Baja')
                ->columns(3)
                ->schema([
                    TextEntry::make('company_name')->label('Empresa'),
                    TextEntry::make('last_plan')->label('Último plan'),
                    TextEntry::make('days_in_system')->label('Tiempo en el sistema')->formatStateUsing(fn (int $state) => self::duration($state)),
                    TextEntry::make('registered_at')->label('Registro del dueño')->dateTime('d/m/Y H:i'),
                    TextEntry::make('requested_at')->label('Solicitud de eliminación')->dateTime('d/m/Y H:i'),
                    TextEntry::make('purge_after')->label('Borrado programado')->dateTime('d/m/Y H:i'),
                    TextEntry::make('purged_at')->label('Eliminada')->dateTime('d/m/Y H:i')->placeholder('En proceso'),
                    TextEntry::make('owner_email')->label('Correo del dueño')->placeholder('Eliminado')->copyable(),
                    TextEntry::make('stripe_customer_id')->label('Cliente en Stripe')->placeholder('-')->copyable(),
                    TextEntry::make('reason')->label('Motivo')->columnSpanFull(),
                ]),
            Section::make('Valoración')
                ->columns(3)
                ->visible(fn (CompanyDeletion $record) => ! $record->isInProgress())
                ->schema([
                    TextEntry::make('feedback_rating')->label('Calificación')
                        ->formatStateUsing(fn (?int $state) => $state ? "{$state} de 5" : null)->placeholder('Sin responder'),
                    TextEntry::make('feedback_submitted_at')->label('Respondida')->dateTime('d/m/Y H:i')->placeholder('-'),
                    TextEntry::make('feedback_comment')->label('Comentario')->placeholder('-')->columnSpanFull(),
                ]),
        ]);
    }

    public static function resendExportAction(): Action
    {
        return Action::make('resendExport')
            ->label('Reenviar enlace')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->visible(fn (CompanyDeletion $record) => $record->isInProgress())
            ->requiresConfirmation()
            ->modalHeading('Reenviar enlace de exportación')
            ->modalDescription(fn (CompanyDeletion $record) => "Se enviará al dueño de {$record->company_name} un enlace nuevo, válido por "
                .CompanyDeletion::EXPORT_LINK_HOURS.' horas. El anterior deja de funcionar.')
            ->modalSubmitActionLabel('Enviar')
            ->action(function (CompanyDeletion $record) {
                try {
                    app(ResendCompanyExport::class)->handle($record);
                    SuperAdminAuditLog::record(
                        'deletion.export_resent',
                        "Reenvió el enlace de exportación de {$record->company_name}",
                        company: Company::query()->find($record->company_id),
                    );
                    Notification::make()->title('Enlace enviado')->body('El dueño recibirá el correo en unos minutos.')->success()->send();
                } catch (Throwable $e) {
                    Notification::make()->title('No se pudo enviar')->body($e->getMessage())->danger()->send();
                }
            });
    }

    public static function duration(int $days): string
    {
        if ($days < 31) {
            return $days === 1 ? '1 día' : "{$days} días";
        }

        $months = intdiv($days, 30);
        if ($months < 12) {
            return $months === 1 ? '1 mes' : "{$months} meses";
        }

        $years = intdiv($months, 12);
        $rest = $months % 12;

        return ($years === 1 ? '1 año' : "{$years} años").($rest ? " y {$rest} ".($rest === 1 ? 'mes' : 'meses') : '');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyDeletions::route('/'),
            'view' => ViewCompanyDeletion::route('/{record}'),
        ];
    }
}
