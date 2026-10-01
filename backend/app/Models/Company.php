<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

#[Fillable([
    'name', 'logo_path', 'slug', 'plan_id', 'status', 'extra_employee_blocks', 'extra_offices',
    'employees_can_use_web', 'payroll_enabled', 'bonuses_enabled', 'timezone', 'trial_ends_at',
    'billing_interval',
])]
class Company extends Model
{
    use Billable, HasFactory;

    /** Sin letras o números que se confundan (O/0, I/1/L). */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 8;

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            $company->code ??= self::generateCode();
        });
    }

    /**
     * Código único de la empresa: los empleados lo usan para entrar a la app.
     */
    public static function generateCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    }

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'employees_can_use_web' => 'boolean',
            'payroll_enabled' => 'boolean',
            'bonuses_enabled' => 'boolean',
            'trial_ends_at' => 'datetime',
            'trial_reminder_sent_at' => 'datetime',
            'trial_charge_notice_sent_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'onboarding_accepted_at' => 'datetime',
            'past_due_since' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'deletion_scheduled_for' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('is_owner', true);
    }

    /**
     * Pre-nómina y bonos: el plan debe incluirlos (Free no) y el dueño tenerlos encendidos.
     */
    public function moduleAvailable(string $module): bool
    {
        if (! $this->plan->includes_payroll) {
            return false;
        }

        return $module === 'payroll' ? $this->payroll_enabled : $this->bonuses_enabled;
    }

    public function employeeLimit(): int
    {
        return $this->plan->included_employees + $this->extra_employee_blocks * $this->plan->employee_block_size;
    }

    public function officeLimit(): int
    {
        return $this->plan->included_offices + $this->extra_offices;
    }

    /**
     * Mensualidad estimada: plan + bloques de empleados + oficinas extra.
     * Los usuarios de la app no cuestan aparte: cualquier empleado puede tener uno.
     */
    public function estimatedMonthlyPrice(): float
    {
        $plan = $this->plan;

        return (float) $plan->monthly_price
            + $this->extra_employee_blocks * (float) $plan->employee_block_price
            + $this->extra_offices * (float) $plan->extra_office_price;
    }

    /**
     * Correo que Stripe usa para la factura: el correo maestro.
     */
    public function stripeEmail(): ?string
    {
        return $this->owner?->email;
    }
}
