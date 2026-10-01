<?php

namespace App\Filament\Resources\Ratings\Tables;

use App\Http\Controllers\Api\RatingController;
use App\Models\Rating;
use App\Models\SuperAdminAuditLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

/**
 * Opiniones de dueños y administradores. Se publica en la landing solo la que
 * el usuario permitió mostrar y trae comentario.
 */
class RatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('score')->label('Calificación')
                    ->formatStateUsing(fn ($state) => self::stars((float) $state))
                    ->html()
                    ->description(fn (Rating $record) => number_format($record->score, 1).' de 5')
                    ->sortable(),
                TextColumn::make('comment')->label('Opinión')->placeholder('Sin comentario')->wrap()->limit(160)->searchable(),
                TextColumn::make('user.name')->label('Quién')
                    ->description(fn (Rating $record) => ($record->user?->is_owner ? 'Dueño' : 'Administrador').' · '.$record->company?->name),
                IconColumn::make('allow_publish')->label('Permite mostrarla')->boolean(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => Rating::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Rating::PUBLISHED => 'success',
                        Rating::HIDDEN => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('updated_at')->label('Fecha')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(Rating::STATUSES),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Mostrar en la página')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (Rating $record) => $record->status !== Rating::PUBLISHED && $record->canBePublished())
                    ->action(function (Rating $record) {
                        $record->publish();
                        self::audit('rating.published', 'Publicó en la landing', $record);
                        Notification::make()->title('Opinión publicada en la página')->success()->send();
                    }),
                Action::make('hide')
                    ->label(fn (Rating $record) => $record->status === Rating::PUBLISHED ? 'Quitar de la página' : 'Ocultar')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->visible(fn (Rating $record) => $record->status !== Rating::HIDDEN)
                    ->action(function (Rating $record) {
                        $record->hide();
                        self::audit('rating.hidden', 'Ocultó', $record);
                        Notification::make()->title('Opinión oculta')->success()->send();
                    }),
            ]);
    }

    /**
     * Estrellas llenas, media y vacías (Bootstrap Icons).
     */
    public static function stars(float $score): string
    {
        $html = '<span class="inline-flex gap-0.5 text-amber-500" aria-label="'.number_format($score, 1).' de 5 estrellas">';

        for ($i = 1; $i <= 5; $i++) {
            $icon = $score >= $i ? 'bi-star-fill' : ($score >= $i - 0.5 ? 'bi-star-half' : 'bi-star');
            $html .= '<i class="bi '.$icon.'" aria-hidden="true"></i>';
        }

        return $html.'</span>';
    }

    private static function audit(string $action, string $verb, Rating $record): void
    {
        Cache::forget(RatingController::TESTIMONIALS_CACHE);

        SuperAdminAuditLog::record(
            $action,
            "{$verb} la opinión de {$record->user?->name} ({$record->score} estrellas)",
            company: $record->company,
            properties: ['rating_id' => $record->id],
        );
    }
}
