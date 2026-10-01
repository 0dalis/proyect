<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;

/**
 * Para modelos que viven en la BD de la empresa. Como las bases Free/Básico y
 * Plus son compartidas, toda consulta se filtra por la empresa activa.
 */
trait BelongsToCompany
{
    public function initializeBelongsToCompany(): void
    {
        $this->setConnection('tenant');
    }

    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $builder->where(
                $builder->qualifyColumn('company_id'),
                app(TenantManager::class)->currentOrFail()->id
            );
        });

        static::creating(function ($model) {
            $model->company_id ??= app(TenantManager::class)->currentOrFail()->id;
        });
    }
}
