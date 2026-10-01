<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ratings\Tables\RatingsTable;
use App\Models\Rating;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Opiniones en el tablero: primero las que faltan por revisar. El Super Admin
 * elige cuáles se muestran en la landing.
 */
class RatingsModeration extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $average = round((float) Rating::query()->avg('score'), 1);
        $count = Rating::query()->count();

        return RatingsTable::configure($table)
            ->heading('Opiniones de los clientes')
            ->description($count > 0
                ? "Promedio {$average} de 5 con {$count} ".($count === 1 ? 'opinión' : 'opiniones').'. Elige cuáles se muestran en la página.'
                : 'Aún no hay opiniones. Los dueños y administradores califican desde su panel.')
            ->query(Rating::query()->with(['user', 'company'])->orderByRaw('status = ? desc', [Rating::PENDING]))
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Sin opiniones todavía');
    }
}
