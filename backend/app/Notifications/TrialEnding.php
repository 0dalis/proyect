<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

/**
 * Antes del primer cobro: 3 días antes ($daysLeft = 3) y el mismo día ($daysLeft = 0).
 */
class TrialEnding extends Notification
{
    use Queueable;

    public function __construct(
        private Company $company,
        private Carbon $chargeOn,
        private int $daysLeft,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->company->plan;
        $yearly = $this->company->billing_interval === 'year';
        $amount = Number::currency($yearly ? $plan->yearlyPrice() : (float) $plan->monthly_price, 'MXN', 'es_MX')
            .($yearly ? ' al año' : ' al mes');
        $date = $this->chargeOn->translatedFormat('j \d\e F');
        $billing = rtrim(config('app.frontend_url'), '/').'/panel/suscripcion';

        $mail = (new MailMessage)->greeting("Hola, {$notifiable->name}");

        if ($this->daysLeft > 0) {
            $mail->subject("Tu prueba de AsistControl termina en {$this->daysLeft} días")
                ->line("La prueba del plan {$plan->name} de {$this->company->name} está por terminar.")
                ->line("El **{$date}** haremos el primer cobro de tu suscripción: **{$amount}**, con la tarjeta que registraste.");
        } else {
            $mail->subject('Hoy se cobra tu suscripción de AsistControl')
                ->line("Hoy terminó la prueba del plan {$plan->name} de {$this->company->name}.")
                ->line("Estamos cobrando tu suscripción: **{$amount}**. Te llegará el comprobante de Stripe.");
        }

        return $mail
            ->action('Ver mi suscripción', $billing)
            ->line('Si quieres cambiar de plan o cancelar, hazlo desde Suscripción antes de la fecha del cobro.');
    }
}
