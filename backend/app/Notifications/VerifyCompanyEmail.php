<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * El enlace abre la página "Confirmar cuenta" de Angular, que valida la firma
 * con Laravel (GET) y confirma con reCAPTCHA (POST).
 */
class VerifyCompanyEmail extends VerifyEmail
{
    public const VALID_HOURS = 24;

    protected function verificationUrl($notifiable): string
    {
        // Firma relativa: sirve igual si la API se llama por el proxy de Angular
        $signed = URL::temporarySignedRoute(
            'web.publico.verification.verify',
            now()->addHours(self::VALID_HOURS),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            absolute: false,
        );

        $query = parse_url($signed, PHP_URL_QUERY);
        $hash = sha1($notifiable->getEmailForVerification());

        return rtrim(config('app.frontend_url'), '/')."/verificar-cuenta/{$notifiable->getKey()}/{$hash}?{$query}";
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma tu correo para activar AsistControl')
            ->greeting('¡Bienvenido a AsistControl!')
            ->line('Este correo será la cuenta maestra (Owner) de tu empresa.')
            ->line('Confirma que es válido para activar tu cuenta.')
            ->action('Confirmar correo', $url)
            ->line('El enlace vence en '.self::VALID_HOURS.' horas. Si vence, al abrirlo te enviaremos uno nuevo.')
            ->line('Si no creaste esta cuenta, ignora este mensaje: las cuentas sin confirmar se eliminan a los 30 días.');
    }
}
