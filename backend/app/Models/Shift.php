<?php

namespace App\Models;

use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['office_id', 'name', 'starts_at', 'ends_at', 'break_minutes', 'weekdays', 'day_schedules', 'tolerance_minutes', 'absence_after_minutes', 'is_default'])]
class Shift extends Model
{
    use Auditable, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            // Horario especial por día ISO: {"5": {"starts_at": "09:00", "ends_at": "17:00", "break_minutes": 60}}
            'day_schedules' => 'array',
            'tolerance_minutes' => 'integer',
            'break_minutes' => 'integer',
            'absence_after_minutes' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function isActiveOn(int $isoWeekday): bool
    {
        return in_array($isoWeekday, array_map('intval', $this->weekdays), true);
    }

    /**
     * Horario de un día: el especial si lo tiene, si no el general del turno.
     *
     * @return array{starts_at: string, ends_at: string, break_minutes: int, special: bool}
     */
    public function scheduleFor(?int $isoWeekday = null): array
    {
        $special = $isoWeekday !== null ? ($this->day_schedules[(string) $isoWeekday] ?? null) : null;

        return [
            'starts_at' => substr($special['starts_at'] ?? $this->starts_at, 0, 5),
            'ends_at' => substr($special['ends_at'] ?? $this->ends_at, 0, 5),
            'break_minutes' => (int) ($special['break_minutes'] ?? $this->break_minutes),
            'special' => $special !== null,
        ];
    }

    /**
     * Minutos de trabajo de una jornada completa (sin el descanso). Sin día,
     * la del horario general.
     */
    public function scheduledMinutes(?int $isoWeekday = null): int
    {
        $schedule = $this->scheduleFor($isoWeekday);
        $start = Carbon::createFromTimeString($schedule['starts_at']);
        $end = Carbon::createFromTimeString($schedule['ends_at']);

        if ($this->crossesMidnight($isoWeekday)) {
            $end->addDay();
        }

        return max(0, (int) $start->diffInMinutes($end) - $schedule['break_minutes']);
    }

    /**
     * Turno nocturno: la salida es al día siguiente (ej. 22:00 a 06:00).
     */
    public function crossesMidnight(?int $isoWeekday = null): bool
    {
        $schedule = $this->scheduleFor($isoWeekday);

        return $schedule['ends_at'] <= $schedule['starts_at'];
    }

    /**
     * Minutos de trabajo de una semana normal (para el valor de la hora).
     */
    public function weeklyMinutes(): int
    {
        return array_sum(array_map(fn ($day) => $this->scheduledMinutes((int) $day), $this->weekdays));
    }
}
