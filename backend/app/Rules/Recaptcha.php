<?php

namespace App\Rules;

use App\Support\Recaptcha as RecaptchaVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 'recaptcha_token' => [new Recaptcha('login')]
 */
class Recaptcha implements ValidationRule
{
    /** Se evalúa aunque el campo no venga (sin token = rechazado). */
    public bool $implicit = true;

    public function __construct(private string $action) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(RecaptchaVerifier::class)->passes(is_string($value) ? $value : null, $this->action, request()->ip())) {
            $fail('No pudimos confirmar que eres una persona. Recarga la página e inténtalo de nuevo.');
        }
    }
}
