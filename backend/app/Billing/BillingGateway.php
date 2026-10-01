<?php

namespace App\Billing;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Support\Carbon;

/**
 * Cobro de la suscripción. En producción es Stripe (StripeBillingGateway);
 * sin claves de Stripe, en local y pruebas, se usa DemoBillingGateway.
 */
interface BillingGateway
{
    /** 'stripe' o 'demo' (Angular omite el formulario de tarjeta en demo). */
    public function mode(): string;

    /**
     * Client secret de un SetupIntent para que Stripe.js guarde la tarjeta.
     */
    public function setupIntent(Company $company): ?string;

    /**
     * Suscribe con la tarjeta guardada. Con $chargeOn, el primer cobro es en esa fecha (prueba).
     *
     * @param  'month'|'year'  $interval
     */
    public function subscribe(Company $company, Plan $plan, string $interval, ?string $paymentMethod, ?Carbon $chargeOn): void;

    /** Cancela la suscripción de inmediato (baja o degradación a Free). */
    public function cancelNow(Company $company): void;
}
