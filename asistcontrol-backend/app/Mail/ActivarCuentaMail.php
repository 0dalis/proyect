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

class ActivarCuentaMail extends Mailable implements ShouldQueue{
    use Queueable, SerializesModels;

    public User $user;
    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        // Generar URL firmada temporal con 24 horas de validez
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
