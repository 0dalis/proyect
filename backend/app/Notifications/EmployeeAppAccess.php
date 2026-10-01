<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Al empleado: cómo entrar a la app (correo + código de empresa + contraseña temporal).
 */
class EmployeeAppAccess extends Notification
{
    use Queueable;

    public function __construct(private Company $company, private string $temporaryPassword) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu acceso a AsistControl de {$this->company->name}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("{$this->company->name} te dio acceso a la app de AsistControl para registrar tu asistencia y hacer tus solicitudes.")
            ->line('Para entrar usa estos datos:')
            ->line("**Correo:** {$notifiable->email}")
            ->line("**Código de empresa:** {$this->company->code}")
            ->line("**Contraseña temporal:** {$this->temporaryPassword}")
            ->line('La primera vez te pediremos cambiar la contraseña y crear tu PIN de 6 dígitos.')
            ->line('Si no tienes batería o no traes tu celular, puedes checar en el kiosko de tu empresa con tu número de empleado y tu PIN.')
            ->line('Si no esperabas este correo, avisa a tu empresa.');
    }
}
