<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Días de descanso obligatorio del art. 74 de la Ley Federal del Trabajo.
 * No incluye los días de jornada electoral (se agregan a mano cuando se publican).
 */
class MexicanHolidays
{
    /**
     * @return array<string, string> fecha (Y-m-d) => nombre
     */
    public static function forYear(int $year): array
    {
        $days = [
            Carbon::create($year, 1, 1)->toDateString() => 'Año Nuevo',
            self::nthMonday($year, 2, 1) => 'Día de la Constitución',
            self::nthMonday($year, 3, 3) => 'Natalicio de Benito Juárez',
            Carbon::create($year, 5, 1)->toDateString() => 'Día del Trabajo',
            Carbon::create($year, 9, 16)->toDateString() => 'Día de la Independencia',
            self::nthMonday($year, 11, 3) => 'Día de la Revolución',
            Carbon::create($year, 12, 25)->toDateString() => 'Navidad',
        ];

        // Transmisión del Poder Ejecutivo Federal: 1 de octubre cada seis años (2024, 2030...)
        if ($year >= 2024 && ($year - 2024) % 6 === 0) {
            $days[Carbon::create($year, 10, 1)->toDateString()] = 'Transmisión del Poder Ejecutivo Federal';
        }

        ksort($days);

        return $days;
    }

    private static function nthMonday(int $year, int $month, int $nth): string
    {
        $date = Carbon::create($year, $month, 1);

        while (! $date->isMonday()) {
            $date->addDay();
        }

        return $date->addWeeks($nth - 1)->toDateString();
    }
}
