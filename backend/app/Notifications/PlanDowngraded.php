<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlanDowngraded extends Notification
{
    use Queueable;

    public function __construct(private Company $company, private string $previousPlan) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $limit = $this->company->employeeLimit();

        return (new MailMessage)
            ->subject('Tu empresa pasó al plan Free de AsistControl')
            ->greeting("Hola, {$notifiable->name}")
            ->line("No pudimos cobrar el plan {$this->previousPlan} en los últimos 3 días, así que {$this->company->name} pasó al plan Free.")
            ->line("Tus datos siguen disponibles, pero solo puedes usar a tus primeros {$limit} empleados. Los demás aparecen en las listas, bloqueados: no pueden checar ni entrar, y no se pueden modificar.")
            ->line('La pre-nómina y los bonos no están incluidos en Free.')
            ->action('Elegir un plan', rtrim(config('app.frontend_url'), '/').'/panel/suscripcion')
            ->line('En cuanto pagues un plan, todos tus empleados se desbloquean.');
    }
}
