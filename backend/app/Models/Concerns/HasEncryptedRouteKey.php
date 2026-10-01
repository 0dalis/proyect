<?php

namespace App\Models\Concerns;

use App\Support\EncryptedId;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sustituye la llave primaria por un identificador cifrado en las rutas
 * (`/employees/{employee}`), para que el id numérico no aparezca en la URL.
 *
 * getRouteKey() genera el token y resolveRouteBindingQuery() lo descifra al
 * resolver el modelo. Un token inválido o de otra empresa no encuentra nada.
 */
trait HasEncryptedRouteKey
{
    public function getRouteKey(): string
    {
        return EncryptedId::encode((string) $this->getKey());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $id = is_string($value) ? EncryptedId::decode($value) : null;

        if ($id === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where($field ?? $this->getRouteKeyName(), $id);
    }
}
