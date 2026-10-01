<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\SuperAdminAuditLog;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $changes = collect($this->record->getChanges())->except('updated_at');

        if ($changes->isEmpty()) {
            return;
        }

        SuperAdminAuditLog::record(
            'company.updated',
            "Editó {$this->record->name}: ".$changes->keys()->implode(', '),
            company: $this->record,
            properties: ['changes' => $changes->all()],
        );
    }
}
