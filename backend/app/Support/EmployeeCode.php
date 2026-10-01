<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Str;

/**
 * Código visible del empleado: las 4 primeras letras del nombre de la empresa
 * más una secuencia por empresa (ALDE-0001, ALDE-0002...).
 *
 * El employee_number sigue existiendo: es lo que teclea quien checa con PIN
 * en el kiosko. El código es lo que se muestra en el panel y se imprime en la
 * credencial, para no dejar a la vista el consecutivo interno.
 */
class EmployeeCode
{
    /** Letras que se toman del nombre de la empresa. */
    private const PREFIX_LENGTH = 4;

    /**
     * Dos letras por palabra hasta completar 4: "Almacenes Demo" -> ALDE,
     * "Empresa Demo" -> EMDE; si es una sola palabra se toman sus 4 primeras
     * ("Empresa" -> EMPR). Si el nombre no da letras se usa el código corto de
     * la empresa y, en el peor de los casos, EMPR.
     */
    public static function prefix(string $companyName, ?string $fallback = null): string
    {
        $letters = self::letters($companyName);

        if ($letters === '') {
            $letters = self::letters((string) $fallback);
        }

        if ($letters === '') {
            $letters = 'EMPR';
        }

        return str_pad(mb_substr($letters, 0, self::PREFIX_LENGTH), self::PREFIX_LENGTH, 'X');
    }

    /** Código nuevo para la empresa: PREFIX-0001, PREFIX-0002... */
    public static function generate(Company $company): string
    {
        return self::next($company->id, self::prefix($company->name, $company->code));
    }

    /**
     * Siguiente código libre de la empresa; cuenta a los dados de baja para no
     * repetir.
     */
    public static function next(int $companyId, string $prefix): string
    {
        $taken = Employee::withTrashed()
            ->where('company_id', $companyId)
            ->where('employee_code', 'like', $prefix.'-%')
            ->pluck('employee_code');

        $next = 1 + (int) $taken
            ->map(fn (string $code): int => (int) mb_substr($code, mb_strlen($prefix) + 1))
            ->max();

        while ($taken->contains(sprintf('%s-%04d', $prefix, $next))) {
            $next++;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }

    /** Dos letras por palabra; una sola palabra se toma entera hasta 4. */
    private static function letters(string $value): string
    {
        $words = preg_split('/[^A-Za-z]+/', Str::ascii($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $letters = '';

        foreach ($words as $word) {
            $letters .= mb_substr($word, 0, 2);

            if (mb_strlen($letters) >= self::PREFIX_LENGTH) {
                break;
            }
        }

        if (count($words) === 1) {
            $letters = mb_substr($words[0], 0, self::PREFIX_LENGTH);
        }

        return strtoupper($letters);
    }
}
