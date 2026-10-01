<?php

namespace App\Support;

use App\Tenancy\TenantManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Reglas de validación en la BD de la empresa, limitadas a la empresa activa
 * (las bases Free/Básico y Plus son compartidas).
 */
class TenantRule
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists("tenant.{$table}", $column)->where('company_id', self::companyId());
    }

    public static function unique(string $table, string $column): Unique
    {
        return Rule::unique("tenant.{$table}", $column)->where('company_id', self::companyId());
    }

    private static function companyId(): int
    {
        return app(TenantManager::class)->currentOrFail()->id;
    }
}
