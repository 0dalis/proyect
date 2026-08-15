<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripeController extends Controller
{
    /**
     * Crea una sesión de Stripe Checkout para suscribirse a un plan.
     *
     * GET/POST /api/web/billing/checkout?plan_id=X&period=monthly|annual
     */
    public function checkout(Request $request): JsonResponse
    {
        $company = Auth::user()->company;

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'period'  => 'required|in:monthly,annual',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $priceId = $validated['period'] === 'annual'
            ? $plan->stripe_annual_price_id
            : $plan->stripe_price_id;

        if (! $priceId) {
            return response()->json([
                'message' => 'Este plan no tiene configurado un precio de pago en Stripe.',
            ], 422);
        }

        $baseUrl = config('app.frontend_url', config('app.url'));

        $builder = $company->newSubscription('default', $priceId)
            ->withMetadata([
                'plan_id' => (string) $plan->id,
                'period'  => $validated['period'],
            ]);

        // Si la empresa sigue en trial, se respeta el trial_end para no cobrar dos veces.
        if ($company->trial_ends_at && $company->trial_ends_at->isFuture()) {
            $builder->trialUntil($company->trial_ends_at);
        }

        $checkout = $builder->checkout([
            'success_url' => $baseUrl . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $baseUrl . '/billing/cancel',
        ]);

        return response()->json([
            'url' => $checkout->url,
        ]);
    }

    /**
     * Verifica el estado de la suscripción actual de la empresa.
     */
    public function status(): JsonResponse
    {
        $company = Auth::user()->company;

        $subscription = $company->subscriptions()->first();

        return response()->json([
            'plan' => $company->plan,
            'plan_id' => $company->plan_id,
            'is_active' => $company->is_active,
            'trial_ends_at' => $company->trial_ends_at,
            'subscription_ends_at' => $company->subscription_ends_at,
            'has_stripe_id' => $company->hasStripeId(),
            'subscription' => $subscription ? [
                'type' => $subscription->type,
                'status' => $subscription->stripe_status,
                'price' => $subscription->stripe_price,
                'ends_at' => $subscription->ends_at,
                'trial_ends_at' => $subscription->trial_ends_at,
                'on_trial' => $subscription->onTrial(),
                'active' => $subscription->active(),
                'recurring' => $subscription->recurring(),
                'canceled' => $subscription->canceled(),
            ] : null,
        ]);
    }

    /**
     * Abre el portal de facturación de Stripe (cambiar tarjeta, facturas, cancelar).
     */
    public function billingPortal(Request $request): JsonResponse
    {
        $company = Auth::user()->company;

        if (! $company->hasStripeId()) {
            return response()->json([
                'message' => 'La empresa aún no tiene un cliente de facturación en Stripe.',
            ], 422);
        }

        $baseUrl = config('app.frontend_url', config('app.url'));

        $portal = $company->billingPortalUrl([
            'return_url' => $baseUrl . '/billing/settings',
        ]);

        return response()->json(['url' => $portal]);
    }

    /**
     * Cancela la suscripción al final del periodo vigente.
     */
    public function cancel(Request $request): JsonResponse
    {
        $company = Auth::user()->company;

        $subscription = $company->subscriptions()->first();

        if (! $subscription || ! $subscription->active()) {
            return response()->json([
                'message' => 'No existe una suscripción activa para cancelar.',
            ], 422);
        }

        $subscription->cancel();

        return response()->json([
            'message' => 'Tu suscripción se cancelará al final del periodo actual.',
        ]);
    }
}
