<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Olvidé mi contraseña (panel y app): enlace por correo válido 60 minutos.
 */
class PasswordResetController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            // El panel manda token de reCAPTCHA; la app móvil no
            'recaptcha_token' => $request->header('X-Client') === 'app' ? [] : [new Recaptcha('forgot_password')],
        ]);

        Password::broker()->sendResetLink(['email' => $data['email']]);

        // Misma respuesta exista o no la cuenta, para no revelar correos
        return response()->json([
            'message' => 'Si el correo tiene una cuenta, te enviamos un enlace para restablecer tu contraseña.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
            'recaptcha_token' => $request->header('X-Client') === 'app' ? [] : [new Recaptcha('reset_password')],
        ]);

        $status = Password::broker()->reset(
            ['email' => $data['email'], 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function (User $user, string $password) {
                // Una contraseña elegida por el usuario deja de ser temporal
                $user->forceFill(['password' => $password, 'must_change_password' => false])->save();

                // Cierra todas sus sesiones y tokens de la app
                $user->tokens()->delete();
                DB::connection('central')->table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => $status === Password::INVALID_TOKEN
                    ? 'El enlace no es válido o ya venció. Pide uno nuevo.'
                    : 'No pudimos restablecer la contraseña. Pide un enlace nuevo.',
            ]);
        }

        return response()->json(['message' => 'Tu contraseña se actualizó. Ya puedes iniciar sesión.']);
    }
}
