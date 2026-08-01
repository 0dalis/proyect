<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('La verificación de seguridad es obligatoria.');
            return;
        }

        // Consultar la API de Google reCAPTCHA
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        $data = $response->json();

        // Para reCAPTCHA v3 validamos 'success' y que el 'score' sea suficiente (ej. > 0.5)
        if (!($data['success'] ?? false) || ($data['score'] ?? 0) < 0.5) {
            $fail('La validación de reCAPTCHA ha fallado. Inténtalo de nuevo.');
        }
    }
}
