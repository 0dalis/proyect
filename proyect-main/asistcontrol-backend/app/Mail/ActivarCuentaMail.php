<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ActivarCuentaMail extends Mailable {
    use Queueable, SerializesModels;

    public User $user;
    public string $verificationUrl;
    public string $nombreCompleto;
    public string $nombreEmpresa;

    public function __construct(User $user, string $nombreCompleto, string $nombreEmpresa)
    {
        $this->user = $user;
        $this->nombreCompleto = $nombreCompleto;
        $this->nombreEmpresa = $nombreEmpresa;

        $this->verificationUrl = URL::temporarySignedRoute(
            'activar.cuenta',
            now()->addHours(24),
            ['id' => $user->id]
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope // 👈 Corregido (un solo _)
    {
        return new Envelope(
            subject: '¡Bienvenido! Activa tu cuenta para comenzar',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content // 👈 Corregido (un solo _)
    {
        return new Content(
            view: 'emails.activar-cuenta',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
