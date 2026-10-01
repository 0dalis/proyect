<?php

namespace App\Notifications;

use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class PlanStarted extends Notification
{
    use Queueable;

    public function __construct(
        private Plan $plan,
        private string $interval,
        private ?Carbon $trialEndsAt,
        private ?Carbon $chargeOn,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject("Tu plan {$this->plan->name} de AsistControl está listo")
            ->greeting("Hola, {$notifiable->name}");

        if ($this->plan->isFree()) {
            return $mail
                ->line('Tu empresa ya usa AsistControl con el plan Free, sin costo.')
                ->line("Incluye hasta {$this->plan->included_employees} empleados. No incluye pre-nómina ni bonos.")
                ->line('Puedes mejorar tu plan cuando quieras desde Suscripción.');
        }

        $price = $this->interval === 'year' ? $this->plan->yearlyPrice() : (float) $this->plan->monthly_price;
        $amount = Number::currency($price, 'MXN', 'es_MX').($this->interval === 'year' ? ' al año' : ' al mes');

        if ($this->trialEndsAt) {
            $mail->line("Comenzó tu periodo de prueba del plan {$this->plan->name}. Termina el {$this->trialEndsAt->translatedFormat('j \\d\\e F \\d\\e Y')}.")
                ->line("Un día antes, el {$this->chargeOn->translatedFormat('j \\d\\e F')}, se cobrará tu suscripción: {$amount}.");
        } else {
            $mail->line("Tu plan {$this->plan->name} está activo: {$amount}.");
        }

        return $mail->line('Puedes cancelar cuando quieras desde Suscripción.');
    }
}
