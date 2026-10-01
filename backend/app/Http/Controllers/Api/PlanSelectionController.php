<?php

namespace App\Http\Controllers\Api;

use App\Actions\ChoosePlan;
use App\Billing\BillingGateway;
use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Modal de bienvenida del dueño (onboarding.php) y cambio de plan
 * posterior (empresa.php): aceptar términos, elegir plan y registrar tarjeta.
 */
class PlanSelectionController extends Controller
{
    public function __construct(private BillingGateway $billing) {}

    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        return response()->json([
            'company_name' => $company->name,
            'accepted' => $company->onboarding_accepted_at !== null,
            'legal_version' => config('legal.version'),
            'billing_mode' => $this->billing->mode(),
            'stripe_key' => $this->billing->mode() === 'stripe' ? config('cashier.key') : null,
            'current_plan' => $company->plan->slug,
            'plans' => Plan::query()->where('is_active', true)->where('is_public', true)->orderBy('sort_order')
                ->get()->map(fn (Plan $plan) => $plan->toPublicArray()),
        ]);
    }

    /**
     * Primer paso del modal: autoriza el uso y acepta aviso de privacidad y términos.
     */
    public function accept(Request $request): JsonResponse
    {
        $request->validate([
            'authorize_use' => ['accepted'],
            'accept_privacy' => ['accepted'],
            'accept_terms' => ['accepted'],
        ], [
            '*.accepted' => 'Debes aceptar para continuar.',
        ]);

        $user = $request->user();
        $user->company->forceFill(['onboarding_accepted_at' => now()])->save();
        $user->forceFill(['terms_accepted_at' => now(), 'terms_version' => config('legal.version')])->save();

        return response()->json(['accepted' => true]);
    }

    /**
     * Stripe.js necesita este secreto para guardar la tarjeta sin que pase por nuestro servidor.
     */
    public function setupIntent(Request $request): JsonResponse
    {
        $this->ensureAccepted($request);

        return response()->json(['client_secret' => $this->billing->setupIntent($request->user()->company)]);
    }

    public function subscribe(Request $request, ChoosePlan $choosePlan): JsonResponse
    {
        $this->ensureAccepted($request);

        $data = $request->validate([
            'plan' => ['required', 'string', Rule::exists('plans', 'slug')->where('is_active', true)->where('is_public', true)],
            'interval' => ['required', 'in:month,year'],
            'payment_method' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $company = $user->company;
        $plan = Plan::query()->where('slug', $data['plan'])->firstOrFail();

        if ($company->status !== CompanyStatus::Onboarding && $company->plan_id === $plan->id) {
            throw ValidationException::withMessages(['plan' => 'Ya tienes este plan.']);
        }

        $result = $choosePlan->handle($company, $plan, $data['interval'], $data['payment_method'] ?? null);

        return response()->json([
            ...$result,
            'plan' => $plan->toPublicArray(),
            'interval' => $plan->isFree() ? null : $data['interval'],
            'user' => new CurrentUserResource($user->refresh()),
        ]);
    }

    private function ensureAccepted(Request $request): void
    {
        $company = $request->user()->company;

        if ($company->status === CompanyStatus::Onboarding && ! $company->onboarding_accepted_at) {
            throw ValidationException::withMessages(['accept_terms' => 'Primero acepta los términos y el aviso de privacidad.']);
        }
    }
}
