<?php

namespace App\Billing;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StripeBillingGateway implements BillingGateway
{
    public const SUBSCRIPTION = 'default';

    public function mode(): string
    {
        return 'stripe';
    }

    public function setupIntent(Company $company): ?string
    {
        $company->createOrGetStripeCustomer(['name' => $company->name]);

        return $company->createSetupIntent(['usage' => 'off_session'])->client_secret;
    }

    public function subscribe(Company $company, Plan $plan, string $interval, ?string $paymentMethod, ?Carbon $chargeOn): void
    {
        $price = $plan->stripePriceFor($interval);

        if (! $price) {
            throw ValidationException::withMessages([
                'plan' => 'Este plan aún no tiene precio en Stripe. Avísanos a soporte.',
            ]);
        }

        $company->createOrGetStripeCustomer(['name' => $company->name]);

        if ($paymentMethod) {
            $company->updateDefaultPaymentMethod($paymentMethod);
        } elseif (! $company->hasDefaultPaymentMethod()) {
            throw ValidationException::withMessages(['payment_method' => 'Registra tu tarjeta para continuar.']);
        }

        // Ya paga otro plan: se cambia el precio y se cobra la diferencia
        $current = $company->subscription(self::SUBSCRIPTION);
        if ($current && $current->valid()) {
            $current->swapAndInvoice($price);

            return;
        }

        $subscription = $company->newSubscription(self::SUBSCRIPTION, $price);

        if ($chargeOn) {
            $subscription->trialUntil($chargeOn);
        }

        $subscription->create($paymentMethod ?? $company->defaultPaymentMethod()?->id);
    }

    public function cancelNow(Company $company): void
    {
        $subscription = $company->subscription(self::SUBSCRIPTION);

        if ($subscription && ! $subscription->ended()) {
            $subscription->cancelNow();
        }
    }
}
