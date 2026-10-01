<?php

namespace App\Models;

use App\Enums\DatabaseTier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'description', 'database_tier', 'monthly_price',
    'included_employees', 'included_offices',
    'employee_block_size', 'employee_block_price', 'extra_office_price',
    'trial_days', 'features', 'includes_payroll', 'stripe_product_id', 'stripe_price_id', 'stripe_yearly_price_id',
    'stripe_employee_block_price_id', 'stripe_extra_office_price_id',
    'is_active', 'is_public', 'sort_order',
])]
class Plan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'database_tier' => DatabaseTier::class,
            'monthly_price' => 'decimal:2',
            'employee_block_price' => 'decimal:2',
            'extra_office_price' => 'decimal:2',
            'features' => 'array',
            'includes_payroll' => 'boolean',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function isFree(): bool
    {
        return (float) $this->monthly_price <= 0;
    }

    /**
     * Precio anual: 11 mensualidades, un mes de regalo.
     */
    public function yearlyPrice(): float
    {
        return round((float) $this->monthly_price * 11, 2);
    }

    /**
     * @param  'month'|'year'  $interval
     */
    public function stripePriceFor(string $interval): ?string
    {
        return $interval === 'year' ? $this->stripe_yearly_price_id : $this->stripe_price_id;
    }

    /**
     * Lo que ven la landing y el modal para elegir plan.
     */
    public function toPublicArray(): array
    {
        return [
            ...$this->only([
                'name', 'slug', 'description', 'monthly_price', 'included_employees', 'included_offices',
                'employee_block_size', 'employee_block_price', 'extra_office_price', 'features', 'includes_payroll',
            ]),
            'is_free' => $this->isFree(),
            'yearly_price' => $this->yearlyPrice(),
            'database_tier' => $this->database_tier->value,
            'database_label' => $this->database_tier->label(),
            'trial_days' => $this->isFree() ? 0 : $this->effectiveTrialDays(),
        ];
    }

    public static function free(): self
    {
        return self::query()->where('slug', 'free')->firstOrFail();
    }

    public function effectiveTrialDays(): int
    {
        return $this->trial_days ?? (int) SystemSetting::get('trial_days', config('tenancy.default_trial_days'));
    }
}
