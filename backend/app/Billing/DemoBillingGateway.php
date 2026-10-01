<?php

namespace App\Billing;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Support\Carbon;

/**
 * Sin Stripe (local y pruebas): la suscripción se da por hecha sin cobrar.
 * Nunca se usa en producción (ver AppServiceProvider).
 */
class DemoBillingGateway implements BillingGateway
{
    public function mode(): string
    {
        return 'demo';
    }

    public function setupIntent(Company $company): ?string
    {
        return null;
    }

    public function subscribe(Company $company, Plan $plan, string $interval, ?string $paymentMethod, ?Carbon $chargeOn): void
    {
        //
    }

    public function cancelNow(Company $company): void
    {
        //
    }
}
