<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * "Olvidé mi contraseña": el enlace abre la página de Angular que pide la nueva.
 */
class ResetPasswordLink extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return rtrim(config('app.frontend_url'), '/').'/restablecer-contrasena?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña de AsistControl')
            ->greeting('Hola')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Elegir una contraseña nueva', $url)
            ->line("El enlace vence en {$minutes} minutos y solo se puede usar una vez.")
            ->line('Si no lo pediste, ignora este correo: tu contraseña no cambia.');
    }
}
