<?php

namespace App\Models;

use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Pre-nómina cerrada: guarda el cálculo tal como estaba al cerrar. Mientras
 * esté cerrada no se pueden cambiar checadas ni solicitudes de esas fechas.
 */
#[Fillable(['name', 'starts_on', 'ends_on', 'closed_by', 'closed_by_name', 'closed_at', 'totals'])]
class PayrollPeriod extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'closed_at' => 'datetime',
            'totals' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /** @param Builder<self> $query */
    public function scopeOverlapping(Builder $query, Carbon|string $from, Carbon|string $to): void
    {
        $query->whereDate('starts_on', '<=', Carbon::parse($to)->toDateString())
            ->whereDate('ends_on', '>=', Carbon::parse($from)->toDateString());
    }

    /**
     * Periodo cerrado que incluye la fecha, si lo hay.
     */
    public static function closedOn(Carbon|string $date): ?self
    {
        return self::query()->overlapping($date, $date)->first();
    }

    /**
     * Impide cambiar asistencia de fechas cuya pre-nómina ya se cerró.
     */
    public static function ensureOpen(Carbon|string $from, Carbon|string|null $to = null, string $field = 'date'): void
    {
        $period = self::query()->overlapping($from, $to ?? $from)->first();

        if ($period) {
            throw ValidationException::withMessages([
                $field => "La pre-nómina \"{$period->name}\" ({$period->starts_on->format('d/m/Y')} al {$period->ends_on->format('d/m/Y')}) ya está cerrada. "
                    .'El dueño puede reabrirla para hacer cambios.',
            ]);
        }
    }
}
