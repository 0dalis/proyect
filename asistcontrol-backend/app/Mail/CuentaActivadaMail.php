<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuentaActivadaMail extends Mailable {
    use Queueable, SerializesModels;

    public User $user;
    public string $nombreCompleto;
    public string $nombreEmpresa;
    public int $daysTrial;
    public string $nombrePlan;
    public string $loginUrl;

    public function __construct(User $user, string $nombreCompleto, string $nombreEmpresa, int $daysTrial, string $nombrePlan)
    {
        $this->user = $user;
        $this->nombreCompleto = $nombreCompleto;
        $this->nombreEmpresa = $nombreEmpresa;
        $this->daysTrial = $daysTrial;
        $this->nombrePlan = $nombrePlan;
        $this->loginUrl = 'http://localhost:4200/login';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tu cuenta está activa! Comienza a usar AsistControl',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cuenta-activada',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
