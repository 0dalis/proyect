<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesEmpleadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Employee $employee;
    public string $password;
    public string $nombreCompleto;
    public string $nombreEmpresa;
    public string $codigoEmpresa;

    public function __construct(User $user, Employee $employee, string $password)
    {
        $this->user = $user;
        $this->employee = $employee;
        $this->password = $password;
        $this->nombreCompleto = trim($employee->first_name . ' ' . $employee->last_name);
        $this->nombreEmpresa = $user->company?->name ?? '';
        $this->codigoEmpresa = $user->company?->code ?? '';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tus credenciales de acceso a AsistControl',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales-empleado',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
