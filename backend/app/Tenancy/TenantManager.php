<?php

namespace App\Tenancy;

use App\Enums\DatabaseTier;
use App\Models\Company;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantManager
{
    private ?Company $current = null;

    public function current(): ?Company
    {
        return $this->current;
    }

    public function currentOrFail(): Company
    {
        return $this->current ?? throw new RuntimeException('No hay una empresa activa.');
    }

    /**
     * Apunta la conexión "tenant" a la base de la empresa y la marca como activa.
     */
    public function connect(Company $company): void
    {
        if ($company->database === null) {
            throw new RuntimeException("La empresa {$company->id} no tiene base de datos asignada.");
        }

        if (config('database.connections.tenant.database') !== $company->database) {
            config(['database.connections.tenant.database' => $company->database]);
            DB::purge('tenant');
        }

        $this->current = $company;
        setPermissionsTeamId($company->id);
    }

    public function forget(): void
    {
        $this->current = null;
        setPermissionsTeamId(null);
    }

    /**
     * Ejecuta un callback dentro del contexto de otra empresa y restaura el anterior.
     */
    public function run(Company $company, callable $callback): mixed
    {
        $previous = $this->current;
        $this->connect($company);

        try {
            return $callback($company);
        } finally {
            $previous ? $this->connect($previous) : $this->forget();
        }
    }

    public function databaseNameFor(Company $company): string
    {
        $prefix = config('tenancy.database_prefix');

        return match ($company->plan->database_tier) {
            DatabaseTier::Basic => $prefix.'pool_basic',
            DatabaseTier::Plus => $prefix.'pool_plus',
            DatabaseTier::Premium => $prefix.'tenant_'.$company->id,
        };
    }

    /**
     * Asigna la base según el plan, la crea si no existe y corre las migraciones de empresa.
     */
    public function provision(Company $company): void
    {
        $database = $this->databaseNameFor($company);

        DB::connection('central')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $company->forceFill(['database' => $database])->save();

        $this->connect($company);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => config('tenancy.migrations_path'),
            '--force' => true,
        ]);
    }
}
