<?php

namespace App\Notifications;

use App\Models\CompanyDeletion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Último correo al dueño: sus datos ya no existen. Incluye un enlace único
 * para valorar AsistControl.
 */
class CompanyDataDeleted extends Notification
{
    use Queueable;

    public function __construct(private CompanyDeletion $deletion) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/').'/valoracion/'.$this->deletion->feedback_token;

        return (new MailMessage)
            ->subject("Eliminamos los datos de {$this->deletion->company_name}")
            ->greeting('Hola')
            ->line("Eliminamos de nuestros servidores toda la información de {$this->deletion->company_name}, de sus usuarios y de sus empleados.")
            ->line('Los respaldos que aún la contengan se eliminarán en su ciclo normal de rotación. Solo conservamos los comprobantes fiscales de tus pagos, como exige la ley.')
            ->line('Nos ayudaría mucho saber cómo fue tu experiencia:')
            ->action('Valorar AsistControl', $url)
            ->line('Si algún día quieres regresar, tendrás que registrar tu empresa y tus datos de nuevo.')
            ->salutation('Gracias por usar AsistControl. — JALY SYSTEMS, S.A. de C.V.');
    }
}
