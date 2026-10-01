<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegisterCompany;
use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Models\Company;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Support\ActivityLogger;
use App\Support\PlanSeats;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request, RegisterCompany $registerCompany): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'accept_terms' => ['accepted'],
            'recaptcha_token' => [new Recaptcha('register')],
        ]);

        $registerCompany->handle($data);

        User::query()->where('email', $data['email'])->update([
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.version'),
        ]);

        return response()->json([
            'message' => 'Te enviamos un correo para confirmar tu cuenta. Podrás entrar cuando lo confirmes.',
        ], 201);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::query()->where('email', $request->string('email'))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Misma respuesta exista o no el correo, para no revelar cuentas
        return response()->json(['message' => 'Si el correo está pendiente de confirmar, te enviamos un nuevo enlace.']);
    }

    public function login(Request $request, TenantManager $tenants): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'client' => ['required', 'in:web,app'],
            // La app entra con correo + código de empresa + contraseña
            'company_code' => ['required_if:client,app', 'nullable', 'string', 'max:20'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        // El captcha es del navegador; la app móvil no lo usa
        if ($data['client'] === 'web') {
            $request->validate(['recaptcha_token' => [new Recaptcha('login')]]);
        }

        $user = User::query()->where('email', $data['email'])->first();

        $codeMatches = ! filled($data['company_code'] ?? null)
            || ($user && Company::normalizeCode($data['company_code']) === $user->company->code);

        if (! $user || ! $codeMatches || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => $data['client'] === 'app'
                    ? 'Correo, código de empresa o contraseña incorrectos.'
                    : 'Correo o contraseña incorrectos.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Aún no confirmas tu correo. Revisa tu bandeja de entrada.',
                'code' => 'email_not_verified',
            ], 403);
        }

        if ($user->isBlocked()) {
            return response()->json(['message' => 'Tu acceso fue desactivado.', 'code' => 'user_blocked'], 403);
        }

        if ($denied = $this->companyAccessError($user)) {
            return $denied;
        }

        $onboarding = $user->company->status === CompanyStatus::Onboarding;

        if ($onboarding && $data['client'] === 'app') {
            return response()->json([
                'message' => 'Termina de configurar tu empresa desde el panel web.',
                'code' => 'onboarding_required',
            ], 403);
        }

        if (! $onboarding) {
            $tenants->connect($user->company);

            if (PlanSeats::userIsLocked($user)) {
                return response()->json([
                    'message' => 'Tu empresa superó el límite de empleados de su plan y tu acceso quedó en pausa. Contacta a tu empresa.',
                    'code' => 'employee_locked',
                ], 403);
            }
        }

        if ($data['client'] === 'web') {
            // Solo el panel (origen permitido) tiene sesión; así no se emite
            // un token reutilizable fuera del navegador.
            if (! $request->hasSession()) {
                return response()->json([
                    'message' => 'El inicio de sesión web solo está disponible desde el panel.',
                    'code' => 'web_session_required',
                ], 403);
            }

            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }

        $user->forceFill(['last_login_at' => now()])->save();

        // Mientras elige plan la empresa aún no tiene base de datos ni bitácora
        if ($onboarding) {
            return response()->json(['user' => new CurrentUserResource($user)]);
        }

        ActivityLogger::log(
            'login',
            description: $data['client'] === 'web' ? 'Inició sesión en el panel web' : 'Inició sesión en la app',
            actor: $user,
        );

        $response = ['user' => new CurrentUserResource($user)];

        if ($data['client'] === 'app') {
            $response['token'] = $user->createToken($data['device_name'] ?? 'app', ['app'])->plainTextToken;
        }

        return response()->json($response);
    }

    /**
     * Solo entra quien tenga cuenta confirmada y empresa activa. El dueño
     * también entra mientras elige plan (ve solo el modal de bienvenida).
     */
    private function companyAccessError(User $user): ?JsonResponse
    {
        $company = $user->company;

        if ($company->status === CompanyStatus::Onboarding && $user->is_owner) {
            return null;
        }

        if ($company->status === CompanyStatus::DeletionPending) {
            return response()->json([
                'message' => 'Tu empresa pidió eliminar sus datos de AsistControl. El acceso está cerrado.',
                'code' => 'company_deleted',
            ], 403);
        }

        if (! $company->status->canOperate()) {
            return response()->json([
                'message' => 'La cuenta de tu empresa no está activa: '.$company->status->label().'.',
                'code' => 'company_inactive',
            ], 403);
        }

        return null;
    }

    public function logout(Request $request): JsonResponse
    {
        ActivityLogger::log('logout', description: 'Cerró sesión');
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): CurrentUserResource
    {
        return new CurrentUserResource($request->user());
    }
}
