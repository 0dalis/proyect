<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'name',
        'is_active',
        'start_time',
        'end_time',
        'cross_midnight',
        'work_days',
        'lunch_start',
        'lunch_end',
        'tolerance_minutes',
        'early_leave_minutes',
        'work_hours_expected',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cross_midnight' => 'boolean',
        'work_days' => 'array',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'lunch_start' => 'datetime:H:i:s',
        'lunch_end' => 'datetime:H:i:s',
    ];

    /**
     * Días laborables por defecto (Lunes a Viernes).
     */
    public const DEFAULT_WORK_DAYS = [1, 2, 3, 4, 5];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS (🔥 lógica de negocio real)
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Determina si un check-in es tardanza
     */
    public function isLate(Carbon $checkIn): bool
    {
        $start = Carbon::parse($this->start_time);
        $limit = $start->copy()->addMinutes($this->tolerance_minutes);

        return $checkIn->gt($limit);
    }

    /**
     * Determina si es salida anticipada
     */
    public function leftEarly(Carbon $checkOut): bool
    {
        $end = Carbon::parse($this->end_time);
        $limit = $end->copy()->subMinutes($this->early_leave_minutes);

        return $checkOut->lt($limit);
    }

    /**
     * Calcula duración del turno en minutos
     */
    public function getDurationMinutes(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        // Turno nocturno
        if ($this->cross_midnight) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }

    /**
     * Calcula minutos de comida
     */
    public function getLunchMinutes(): int
    {
        if (!$this->lunch_start || !$this->lunch_end) {
            return 0;
        }

        $start = Carbon::parse($this->lunch_start);
        $end = Carbon::parse($this->lunch_end);

        return $start->diffInMinutes($end);
    }

    /**
     * Días laborables del turno (ISO 1-7). Si no están definidos, Lun-Vie.
     *
     * @return array<int,int>
     */
    public function getWorkDays(): array
    {
        $days = $this->work_days;

        if (empty($days) || ! is_array($days)) {
            return self::DEFAULT_WORK_DAYS;
        }

        return array_values(array_map('intval', $days));
    }

    public function isWorkingDay(Carbon $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->getWorkDays(), true);
    }

    /**
     * Cuenta los días laborables entre dos fechas (inclusive), excluyendo los
     * festivos indicados (array de fechas 'Y-m-d').
     *
     * @param  array<int,string>  $holidays
     */
    public function workingDaysBetween(Carbon $start, Carbon $end, array $holidays = []): int
    {
        $count = 0;
        $cursor = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if ($this->isWorkingDay($cursor) && ! in_array($cursor->toDateString(), $holidays, true)) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }
}