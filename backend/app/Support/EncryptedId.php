<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Convierte una llave primaria numérica en un identificador cifrado y apto
 * para una URL (sin "/" ni "+"), para que el id no viaje en texto plano.
 *
 * El cifrado usa APP_KEY, así que el token cambia en cada llamada y no se
 * puede adivinar ni enumerar. El id sigue siendo numérico en la base de datos.
 */
class EncryptedId
{
    /** Base64 estándar → base64 url-safe ("+" → "-", "/" → "_"). */
    public static function encode(int|string $id): string
    {
        return strtr(Crypt::encryptString((string) $id), ['+' => '-', '/' => '_']);
    }

    public static function decode(string $value): ?string
    {
        try {
            return Crypt::decryptString(strtr($value, ['-' => '+', '_' => '/']));
        } catch (Throwable) {
            // Token alterado, vencido o de otra APP_KEY
            return null;
        }
    }
}
