<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyStatus: string implements HasLabel
{
    case Pending = 'pending';
    /** Correo confirmado; el dueño aún no acepta los términos ni elige plan. */
    case Onboarding = 'onboarding';
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    /** Pidió eliminar sus datos: acceso bloqueado hasta el borrado (30 días). */
    case DeletionPending = 'deletion_pending';

    public function canOperate(): bool
    {
        return in_array($this, [self::Trial, self::Active, self::PastDue], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de verificar correo',
            self::Onboarding => 'Eligiendo plan',
            self::Trial => 'En prueba',
            self::Active => 'Activa',
            self::PastDue => 'Pago vencido',
            self::Suspended => 'Suspendida',
            self::Cancelled => 'Cancelada',
            self::DeletionPending => 'En eliminación',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
