<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\CompanyDeletion;
use App\Notifications\CompanyDeletionRequested;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Soporte reenvía el enlace de exportación (otras 48 horas) cuando el del
 * dueño venció. Solo mientras la baja está en proceso (30 días).
 */
class ResendCompanyExport
{
    public function __construct(private ExportCompanyData $export) {}

    public function handle(CompanyDeletion $deletion): void
    {
        if (! $deletion->isInProgress() || ! $deletion->owner_email) {
            throw new RuntimeException('Los datos de esta empresa ya se eliminaron; no hay nada que exportar.');
        }

        if (! $deletion->export_path || ! Storage::disk('local')->exists($deletion->export_path)) {
            $company = Company::query()->findOrFail($deletion->company_id);
            $deletion->forceFill(['export_path' => $this->export->handle($company)])->save();
        }

        $token = $deletion->issueExportToken();

        Notification::route('mail', [$deletion->owner_email => $deletion->owner_name])
            ->notify(new CompanyDeletionRequested($deletion, $token, resent: true));
    }
}
