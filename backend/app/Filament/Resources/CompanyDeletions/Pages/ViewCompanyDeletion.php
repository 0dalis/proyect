<?php

namespace App\Filament\Resources\CompanyDeletions\Pages;

use App\Filament\Resources\CompanyDeletions\CompanyDeletionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyDeletion extends ViewRecord
{
    protected static string $resource = CompanyDeletionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CompanyDeletionResource::resendExportAction(),
        ];
    }
}
