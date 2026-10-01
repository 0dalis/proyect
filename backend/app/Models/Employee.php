<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\WorkMode;
use App\Models\Concerns\HasEncryptedRouteKey;
use App\Support\Auditable;
use App\Support\EmployeeCode;
use App\Tenancy\BelongsToCompany;
use App\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable([
    'employee_number', 'first_name', 'last_name', 'email', 'phone', 'position',
    'office_id', 'shift_id', 'area_id', 'status', 'employment_type', 'work_mode',
    'hired_on', 'contract_ends_on', 'terminated_on', 'badge_expires_on', 'salary', 'salary_period',
])]
/**
 * El employee_number es interno (lo teclea quien checa con PIN en el kiosko)
 * y no sale en las respuestas: lo visible es employee_code.
 */
#[Hidden(['pin_hash', 'badge_token', 'salary', 'employee_number'])]
class Employee extends Model
{
    use Auditable, BelongsToCompany, HasEncryptedRouteKey, SoftDeletes;

    /** Identificador cifrado para las URLs; el id numérico no se expone. */
    protected $appends = ['public_id'];

    protected static function booted(): void
    {
        // Todo alta (formulario, importación, seeder, fábrica) recibe su número
        // interno y su código visible ALDE-0001 sin que nadie tenga que pedirlos.
        static::creating(function (Employee $employee): void {
            $employee->employee_number ??= self::nextEmployeeNumber();
            $employee->employee_code ??= EmployeeCode::generate(app(TenantManager::class)->currentOrFail());
        });
    }

    /** Consecutivo por empresa: 00001, 00002... Lo teclea quien checa con PIN. */
    private static function nextEmployeeNumber(): string
    {
        $next = self::withTrashed()->count() + 1;

        while (self::withTrashed()->where('employee_number', $number = str_pad((string) $next, 5, '0', STR_PAD_LEFT))->exists()) {
            $next++;
        }

        return $number;
    }

    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
            'employment_type' => EmploymentType::class,
            'work_mode' => WorkMode::class,
            'hired_on' => 'date',
            'contract_ends_on' => 'date',
            'terminated_on' => 'date',
            'badge_issued_at' => 'datetime',
            'badge_expires_on' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    protected function publicId(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->getRouteKey());
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function managedAreas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'area_manager');
    }

    public function remoteWorkPeriods(): HasMany
    {
        return $this->hasMany(RemoteWorkPeriod::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class)->withPivot('read_at');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Recién importado (carga masiva): le falta oficina, turno, área o tipo.
     * No puede checar hasta que lo organicen.
     *
     * @param  Builder<self>  $query
     */
    public function scopeNeedsSetup(Builder $query): void
    {
        $query->where(fn ($q) => $q->whereNull('office_id')->orWhereNull('shift_id')
            ->orWhereNull('area_id')->orWhereNull('employment_type'));
    }

    public function needsSetup(): bool
    {
        return ! $this->office_id || ! $this->shift_id || ! $this->area_id || ! $this->employment_type;
    }

    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::Active;
    }

    public function setPin(string $pin): void
    {
        $this->pin_hash = Hash::make($pin);
    }

    public function checkPin(string $pin): bool
    {
        return $this->pin_hash !== null && Hash::check($pin, $this->pin_hash);
    }

    /**
     * Genera un token nuevo para el QR de la credencial; el anterior deja de servir.
     */
    public function issueBadge(): string
    {
        $this->badge_token = Str::random(48);
        $this->badge_issued_at = now();

        if ($this->employment_type === EmploymentType::Temporary && $this->badge_expires_on === null) {
            $this->badge_expires_on = $this->contract_ends_on;
        }

        $this->save();

        return $this->badge_token;
    }

    public function badgeIsValid(): bool
    {
        return $this->badge_token !== null
            && ($this->badge_expires_on === null || $this->badge_expires_on->copy()->endOfDay()->isFuture());
    }

    /**
     * Home office permanente o un periodo de home office que cubre esa fecha.
     */
    public function worksRemotelyOn(Carbon $date): bool
    {
        if ($this->work_mode === WorkMode::Remote) {
            return true;
        }

        return $this->remoteWorkPeriods()
            ->whereDate('starts_on', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))
            ->get()
            ->contains(fn (RemoteWorkPeriod $period) => $period->weekdays === null
                || in_array($date->dayOfWeekIso, array_map('intval', $period->weekdays), true));
    }
}
