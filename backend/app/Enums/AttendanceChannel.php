<?php

namespace App\Enums;

enum AttendanceChannel: string
{
    case AppBiometric = 'app_biometric';
    case AppPin = 'app_pin';
    case KioskQr = 'kiosk_qr';
    case KioskPin = 'kiosk_pin';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::AppBiometric => 'App (biometría)',
            self::AppPin => 'App (PIN)',
            self::KioskQr => 'Kiosko (QR)',
            self::KioskPin => 'Kiosko (PIN)',
            self::Manual => 'Manual',
        };
    }
}
