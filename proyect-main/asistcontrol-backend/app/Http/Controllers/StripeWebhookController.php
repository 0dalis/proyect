<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Stripe\Subscription as StripeSubscription;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle a Stripe webhook call.
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        return parent::handleWebhook($request);
    }

    /**
     * Tras manejar el evento de suscripción creada, sincroniza el plan de la empresa.
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        $this->syncCompanyPlan($payload);
 
        return $response;
    }

    /**
     * Tras manejar el evento de suscripción actualizada, sincroniza el plan de la empresa.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncCompanyPlan($payload);

        return $response;
    }

    /**
     * Cuando la suscripción se elimina, baja a la empresa al plan Free.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $company = $this->findCompanyByStripeCustomer($payload['data']['object']['customer'] ?? null);

        if ($company) {
            $freePlan = Plan::where('tipo', 'Free')->first() ?? Plan::orderBy('precio')->first();

            if ($freePlan) {
                $company->update([
                    'plan_id' => $freePlan->id,
                    'subscription_ends_at' => null,
                ]);
            }
        }

        return $response;
    }

    /**
     * Sincroniza el plan_id de la empresa según el price de la suscripción activa.
     */
    protected function syncCompanyPlan(array $payload): void
    {
        $data = $payload['data']['object'] ?? [];

        if (($data['status'] ?? null) === StripeSubscription::STATUS_INCOMPLETE_EXPIRED) {
            return;
        }

        $company = $this->findCompanyByStripeCustomer($data['customer'] ?? null);

        if (! $company) {
            return;
        }

        $subscription = $company->subscriptions()->first();

        if (! $subscription) {
            return;
        }

        $priceId = $subscription->stripe_price;

        if (! $priceId) {
            return;
        }

        $plan = Plan::where(function ($query) use ($priceId) {
            $query->where('stripe_price_id', $priceId)
                ->orWhere('stripe_annual_price_id', $priceId);
        })->first();

        if ($plan) {
            $company->update(['plan_id' => $plan->id]);
        }

        // Si la suscripción está activa o en periodo de gracia, se limpia el fin de trial local.
        if ($subscription->valid()) {
            $company->update(['trial_ends_at' => null]);
        }
    }

    /**
     * Busca la empresa por su Stripe customer ID.
     */
    protected function findCompanyByStripeCustomer(?string $stripeCustomerId)
    {
        if (! $stripeCustomerId) {
            return null;
        }

        return Cashier::findBillable($stripeCustomerId);
    }
}
