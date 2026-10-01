<?php

namespace App\Filament\Widgets;

use App\Support\PlatformMetrics;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Cache;

class DatabaseHealth extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Bases de datos')
            ->description('Central, pools compartidos y bases dedicadas. Se revisan cada minuto.')
            ->records(fn () => collect(app(PlatformMetrics::class)->databases())->keyBy('name')->all())
            ->paginated(false)
            ->columns([
                IconColumn::make('online')
                    ->label('Estado')
                    ->icon(fn (bool $state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->color(fn (bool $state) => $state ? 'success' : 'danger')
                    ->tooltip(fn (array $record) => $record['online'] ? 'En línea' : 'Sin conexión: '.$record['error']),
                TextColumn::make('name')->label('Base de datos')->fontFamily('mono')->weight('semibold'),
                TextColumn::make('tier')->label('Tipo')->badge()->color('gray'),
                TextColumn::make('companies')->label('Empresas')->alignEnd(),
                TextColumn::make('latency_ms')->label('Latencia')->suffix(' ms')->placeholder('—')->alignEnd()
                    ->color(fn ($state) => $state === null ? 'danger' : ($state > 200 ? 'warning' : null)),
                TextColumn::make('size_mb')->label('Tamaño')->suffix(' MB')->alignEnd(),
                TextColumn::make('tables')->label('Tablas')->alignEnd(),
            ])
            ->headerActions([
                Action::make('refresh')
                    ->label('Revisar ahora')
                    ->icon(Heroicon::ArrowPath)
                    ->action(fn () => Cache::forget('platform.databases')),
            ]);
    }
}
