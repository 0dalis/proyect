<?php

namespace App\Http\Controllers\Api;

use App\Actions\ActivateCompany;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

/**
 * Enlace del correo de confirmación. Angular abre /verificar-cuenta y:
 * - GET: pregunta en qué estado está (inválido, ya activa, vencido o pendiente).
 * - POST: confirma la cuenta (con reCAPTCHA).
 */
class EmailVerificationController extends Controller
{
    /** Si el enlace venció, se envía otro como máximo cada tantos minutos. */
    private const RESEND_EVERY_MINUTES = 5;

    public function show(Request $request, int $id, string $hash): JsonResponse
    {
        [$user, $state] = $this->inspect($request, $id, $hash);

        if ($state === 'expired') {
            $this->resend($user);
        }

        return $this->respond($state, $user);
    }

    public function store(Request $request, int $id, string $hash, ActivateCompany $activateCompany): JsonResponse
    {
        $request->validate(['recaptcha_token' => [new Recaptcha('verify_email')]]);

        [$user, $state] = $this->inspect($request, $id, $hash);

        if ($state === 'expired') {
            $this->resend($user);
        }

        if ($state !== 'pending') {
            return $this->respond($state, $user, $state === 'invalid' ? 403 : 200);
        }

        $user->markEmailAsVerified();

        if ($user->is_owner) {
            $activateCompany->prepareOwner($user->company);
        }

        return $this->respond('verified', $user);
    }

    /**
     * @return array{0: User|null, 1: 'invalid'|'already_verified'|'expired'|'pending'}
     */
    private function inspect(Request $request, int $id, string $hash): array
    {
        $user = User::query()->find($id);

        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)
            || ! URL::hasCorrectSignature($request, absolute: false)) {
            return [null, 'invalid'];
        }

        if ($user->hasVerifiedEmail()) {
            return [$user, 'already_verified'];
        }

        if (! URL::signatureHasNotExpired($request)) {
            return [$user, 'expired'];
        }

        return [$user, 'pending'];
    }

    private function resend(User $user): void
    {
        RateLimiter::attempt(
            "verification-resend:{$user->id}",
            1,
            fn () => $user->sendEmailVerificationNotification(),
            self::RESEND_EVERY_MINUTES * 60,
        );
    }

    private function respond(string $state, ?User $user, int $status = 200): JsonResponse
    {
        $messages = [
            'invalid' => 'El enlace no es válido. Revisa que lo copiaste completo o pide uno nuevo.',
            'already_verified' => 'Tu cuenta ya está activa. Inicia sesión.',
            'expired' => 'El enlace venció. Te enviamos uno nuevo: revisa tu correo y usa el enlace más reciente.',
            'pending' => 'Confirma tu cuenta para continuar.',
            'verified' => 'Cuenta confirmada. Ya puedes iniciar sesión.',
        ];

        return response()->json([
            'status' => $state,
            'message' => $messages[$state],
            'email' => $user && $state !== 'invalid' ? $this->mask($user->email) : null,
        ], $status);
    }

    /** d***@empresa.com: confirma a quién llegó sin mostrar el correo completo. */
    private function mask(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);

        return mb_substr($name, 0, 1).str_repeat('*', max(2, mb_strlen($name) - 1)).'@'.$domain;
    }
}
