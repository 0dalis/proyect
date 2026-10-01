<?php

namespace App\Models;

use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Día festivo de la empresa: no cuenta como falta, y si se trabaja se paga
 * doble adicional (art. 75 LFT).
 */
#[Fillable(['date', 'name', 'is_official'])]
class Holiday extends Model
{
    use Auditable, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_official' => 'boolean',
        ];
    }

    /**
     * @return array<string, string> fecha (Y-m-d) => nombre
     */
    public static function between(Carbon $from, Carbon $to): array
    {
        return self::query()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get()
            ->mapWithKeys(fn (Holiday $holiday) => [$holiday->date->toDateString() => $holiday->name])
            ->all();
    }
}
