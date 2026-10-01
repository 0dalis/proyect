<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accounts:prune-unverified {--days=30 : Días sin confirmar el correo}')]
#[Description('Elimina los registros cuyo dueño no confirmó su correo en 30 días')]
class PruneUnverifiedAccounts extends Command
{
    public function handle(): int
    {
        // Aún no tienen base de datos: basta con borrar la empresa (y sus usuarios, en cascada)
        $deleted = Company::query()
            ->where('status', CompanyStatus::Pending)
            ->where('created_at', '<=', now()->subDays((int) $this->option('days')))
            ->whereDoesntHave('users', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->whereNull('database')
            ->get()
            ->each->delete()
            ->count();

        $this->info("Registros sin confirmar eliminados: {$deleted}");

        return self::SUCCESS;
    }
}
