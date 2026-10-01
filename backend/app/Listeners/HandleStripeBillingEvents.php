<?php

namespace App\Listeners;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Notifications\PaymentFailed;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Webhooks de Stripe (Cashier ya actualiza la suscripción; aquí, el estado de
 * la empresa):
 * - Cobro fallido (fin de prueba o renovación): "Pago vencido" y aviso al
 *   dueño para que revise su tarjeta. A los 3 días sin pago, billing:downgrade-overdue
 *   la baja a Free.
 * - Cobro exitoso: vuelve a "Activa".
 */
class HandleStripeBillingEvents
{
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;
        $invoice = $event->payload['data']['object'] ?? [];

        if (! in_array($type, ['invoice.payment_failed', 'invoice.paid'], true) || empty($invoice['customer'])) {
            return;
        }

        $company = Company::query()->where('stripe_id', $invoice['customer'])->first();

        if (! $company || $company->status === CompanyStatus::DeletionPending) {
            return;
        }

        if ($type === 'invoice.payment_failed') {
            $this->paymentFailed($company);
        } elseif (($invoice['amount_paid'] ?? 0) > 0) {
            $company->forceFill(['status' => CompanyStatus::Active, 'past_due_since' => null, 'trial_ends_at' => null])->save();
        }
    }

    private function paymentFailed(Company $company): void
    {
        $firstFailure = $company->past_due_since === null;

        $company->forceFill([
            'status' => CompanyStatus::PastDue,
            'past_due_since' => $company->past_due_since ?? now(),
        ])->save();

        // Stripe reintenta el cobro; solo se avisa la primera vez
        if ($firstFailure) {
            $company->owner?->notify(new PaymentFailed($company));
        }
    }
}
