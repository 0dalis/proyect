<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google reCAPTCHA v3: invisible, califica cada intento de 0.0 (bot) a 1.0
 * (persona). Angular obtiene el token con grecaptcha.execute(clave, {action})
 * y Laravel lo valida aquí antes de registrar, iniciar sesión o confirmar la cuenta.
 */
class Recaptcha
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function enabled(): bool
    {
        return filled(config('services.recaptcha.secret_key'));
    }

    public function siteKey(): ?string
    {
        return $this->enabled() ? config('services.recaptcha.site_key') : null;
    }

    public function passes(?string $token, string $action, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            // En producción, sin claves no se deja pasar a nadie
            return ! app()->isProduction();
        }

        if (blank($token)) {
            return false;
        }

        try {
            $result = Http::asForm()->timeout(5)->post(self::VERIFY_URL, array_filter([
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]))->json();
        } catch (Throwable $e) {
            Log::warning('reCAPTCHA no respondió', ['error' => $e->getMessage()]);

            return false;
        }

        return ($result['success'] ?? false)
            && ($result['action'] ?? null) === $action
            && (float) ($result['score'] ?? 0) >= (float) config('services.recaptcha.min_score');
    }
}
