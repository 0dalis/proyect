<?php

namespace App\Filament\Resources\CompanyDeletions\Pages;

use App\Filament\Resources\CompanyDeletions\CompanyDeletionResource;
use App\Models\CompanyDeletion;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCompanyDeletions extends ListRecords
{
    protected static string $resource = CompanyDeletionResource::class;

    public function getTabs(): array
    {
        return [
            'in_progress' => Tab::make('En proceso')
                ->icon('heroicon-o-clock')
                ->badge(CompanyDeletion::query()->inProgress()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('purged_at')->reorder('purge_after')),
            'purged' => Tab::make('Eliminadas')
                ->icon('heroicon-o-archive-box-x-mark')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('purged_at')->reorder('purged_at', 'desc')),
        ];
    }
}
