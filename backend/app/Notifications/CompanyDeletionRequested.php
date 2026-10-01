<?php

namespace App\Notifications;

use App\Models\CompanyDeletion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Al dueño: confirma la solicitud de baja y trae el enlace de exportación
 * (48 horas). $resent = true cuando soporte vuelve a enviarlo.
 */
class CompanyDeletionRequested extends Notification
{
    use Queueable;

    public function __construct(
        private CompanyDeletion $deletion,
        private string $exportToken,
        private bool $resent = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $purgeOn = $this->deletion->purge_after->translatedFormat('j \d\e F \d\e Y');
        $hours = CompanyDeletion::EXPORT_LINK_HOURS;
        $url = route('web.publico.exportacion.descargar', $this->exportToken);
        $support = config('legal.support_email');

        $mail = (new MailMessage)->greeting('Hola, '.($this->deletion->owner_name ?? ''));

        if ($this->resent) {
            $mail->subject("Nuevo enlace para descargar los datos de {$this->deletion->company_name}")
                ->line("Te enviamos un nuevo enlace para descargar los datos de {$this->deletion->company_name}.");
        } else {
            $mail->subject("Recibimos tu solicitud para eliminar {$this->deletion->company_name} de AsistControl")
                ->line("Recibimos tu solicitud para eliminar los datos de {$this->deletion->company_name}.")
                ->line('Desde este momento nadie de tu empresa puede entrar: ni el dueño, ni administradores, ni empleados, ni kioskos.')
                ->line('Cancelamos tu suscripción; no se harán más cargos.');
        }

        return $mail
            ->line("El {$purgeOn} eliminaremos de forma definitiva toda la información de tu empresa y de tus empleados.")
            ->line('Descarga una copia de tus datos (empleados, histórico de asistencia, solicitudes, áreas, oficinas y turnos):')
            ->action('Descargar mis datos', $url)
            ->line("El enlace funciona durante {$hours} horas. Si vence, escribe a {$support} para que te enviemos otro, antes del {$purgeOn}.")
            ->line('Después de esa fecha no será posible recuperar la información.')
            ->line("Si no pediste esto o necesitas alguna aclaración, escríbenos a {$support}.");
    }
}
