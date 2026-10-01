<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification
{
    use Queueable;

    public const GRACE_DAYS = 3;

    public function __construct(private Company $company) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deadline = now()->addDays(self::GRACE_DAYS)->translatedFormat('j \d\e F');

        return (new MailMessage)
            ->subject('No pudimos cobrar tu suscripción de AsistControl')
            ->greeting("Hola, {$notifiable->name}")
            ->line("No pudimos cobrar la suscripción del plan {$this->company->plan->name} de {$this->company->name}.")
            ->line('Revisa que tu tarjeta esté vigente y tenga fondos, o registra otra desde Suscripción.')
            ->action('Revisar mi método de pago', rtrim(config('app.frontend_url'), '/').'/panel/suscripcion')
            ->line("Si el pago no se completa antes del {$deadline}, tu empresa pasará al plan Free y quedará limitada a sus condiciones.");
    }
}
