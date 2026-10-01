<?php

namespace App\Console\Commands;

use App\Actions\PurgeCompany;
use App\Models\CompanyDeletion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('companies:purge-deleted')]
#[Description('Borra de forma definitiva las empresas cuya solicitud de baja cumplió 30 días')]
class PurgeDeletedCompanies extends Command
{
    public function handle(PurgeCompany $purge): int
    {
        $deletions = CompanyDeletion::query()->inProgress()->where('purge_after', '<=', now())->get();

        foreach ($deletions as $deletion) {
            try {
                $purge->handle($deletion);
                $this->line("Eliminada: {$deletion->company_name}");
            } catch (Throwable $e) {
                // Se reintenta en la siguiente ejecución
                report($e);
                $this->error("{$deletion->company_name}: {$e->getMessage()}");
            }
        }

        $this->info("Empresas eliminadas: {$deletions->count()}");

        return self::SUCCESS;
    }
}
