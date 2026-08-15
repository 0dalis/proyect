<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public Company $company;
    public User $owner;
    public string $nombreEmpresa;
    public string $nombreOwner;
    public string $planNombre;
    public string $fechaFinTrial;
    public string $precio;

    public function __construct(Company $company, User $owner)
    {
        $this->company = $company;
        $this->owner = $owner;
        $this->nombreEmpresa = $company->name;
        $this->nombreOwner = trim(($owner->employee?->first_name ?? '') . ' ' . ($owner->employee?->last_name ?? ''));
        $this->planNombre = $company->plan?->nombre ?? 'Premium';
        $this->precio = '$' . number_format((float) ($company->plan?->precio ?? 0), 2);
        $this->fechaFinTrial = $company->trial_ends_at?->translatedFormat('d \d\e F \d\e Y') ?? 'Próximamente';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu periodo de prueba está por terminar — AsistControl',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
