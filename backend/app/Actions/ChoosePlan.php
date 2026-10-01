<?php

namespace App\Actions;

use App\Billing\BillingGateway;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Notifications\PlanStarted;
use App\Support\ActivityLogger;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * El dueño elige plan:
 * - En el modal de bienvenida (empresa sin base todavía): Free queda activa
 *   sin prueba; los de pago inician la prueba y el primer cobro es un día
 *   antes de que termine.
 * - Después (por ejemplo, tras bajar a Free): se cobra de inmediato, sin prueba.
 */
class ChoosePlan
{
    public function __construct(
        private BillingGateway $billing,
        private ActivateCompany $activateCompany,
        private TenantManager $tenants,
    ) {}

    /**
     * @param  'month'|'year'  $interval
     * @return array{status: string, trial_ends_at: ?Carbon, charge_on: ?Carbon}
     */
    public function handle(Company $company, Plan $plan, string $interval, ?string $paymentMethod): array
    {
        $firstTime = $company->status === CompanyStatus::Onboarding;
        $trialDays = $firstTime && ! $plan->isFree() ? $plan->effectiveTrialDays() : 0;
        $trialEndsAt = $trialDays > 0 ? now()->addDays($trialDays) : null;
        $chargeOn = $trialEndsAt?->copy()->subDay();

        if (! $plan->isFree()) {
            // Si Stripe rechaza la tarjeta, no se crea nada
            $this->billing->subscribe($company, $plan, $interval, $paymentMethod, $chargeOn);
        } elseif (! $firstTime) {
            $this->billing->cancelNow($company);
        }

        DB::connection('central')->transaction(function () use ($company, $plan, $interval) {
            $company->forceFill([
                'plan_id' => $plan->id,
                'billing_interval' => $plan->isFree() ? null : $interval,
                'past_due_since' => null,
                // Free no incluye pre-nómina ni bonos
                'payroll_enabled' => $plan->includes_payroll && $company->payroll_enabled,
                'bonuses_enabled' => $plan->includes_payroll && $company->bonuses_enabled,
            ])->save();
        });
        $company->setRelation('plan', $plan);

        $status = $trialEndsAt ? CompanyStatus::Trial : CompanyStatus::Active;

        if ($firstTime) {
            $this->activateCompany->handle($company, $status, $trialEndsAt);
        } else {
            $company->forceFill(['status' => $status, 'trial_ends_at' => null])->save();
            $this->tenants->run($company, fn () => ActivityLogger::log(
                'plan_changed',
                description: "Cambió al plan {$plan->name}".($plan->isFree() ? '' : ($interval === 'year' ? ' (anual)' : ' (mensual)')),
            ));
        }

        $company->owner?->notify(new PlanStarted($plan, $interval, $trialEndsAt, $chargeOn));

        return ['status' => $status->value, 'trial_ends_at' => $trialEndsAt, 'charge_on' => $chargeOn];
    }
}
