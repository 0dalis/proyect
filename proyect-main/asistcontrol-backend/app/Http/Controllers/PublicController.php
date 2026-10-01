<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Mail\ActivarCuentaMail;

class PublicController extends Controller{

    public function loginWeb(Request $request){
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials['is_active'] = true;

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            $allowedRoles = ['admin', 'owner', 'super-admin'];

            if (!$user->hasAnyRole($allowedRoles)) {
                Auth::logout();
                $request->session()->invalidate();
                return response()->json([
                    'message' => 'No tienes los permisos necesarios para acceder al sistema.',
                ], 403);
            }

            $request->session()->regenerate();

            $request->session()->put('last_activity_time', time());

            Cookie::queue('user_id', $user->id, 120, null, null, false, false, false, false);

            $employee = $user->employee;
            $company  = $user->company;

            $response = [
                'message' => 'Sesión iniciada correctamente.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $employee?->first_name,
                    'last_name' => $employee?->last_name,
                ],
            ];

            if ($company && !$company->is_active) {
                $response['company_inactive'] = true;
            }

            return response()->json($response);
        }

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {

            if (!$user->is_active) {
                $tokenExpiro = !$user->activation_token_expires_at
                    || now()->gt($user->activation_token_expires_at);

                if ($tokenExpiro) {
                    $nuevoToken = Str::random(64);
                    $user->update([
                        'activation_token'            => $nuevoToken,
                        'activation_token_expires_at' => now()->addHours(24),
                    ]);

                    $employee       = $user->employee;
                    $nombreCompleto = ($employee?->first_name ?? '') . ' ' . ($employee?->last_name ?? '');
                    $nombreEmpresa  = $user->company?->name ?? '';

                    Mail::to($user->email)->send(
                        new ActivarCuentaMail($user, trim($nombreCompleto), $nombreEmpresa)
                    );

                    return response()->json([
                        'message' => 'Tu cuenta no está activa. Hemos reenviado el correo de verificación a tu email.',
                    ], 403);
                }

                return response()->json([
                    'message' => 'Tu cuenta aún no ha sido activada. Revisa tu correo electrónico para activarla.',
                ], 403);
            }

            return response()->json([
                'message' => 'No tienes los permisos necesarios para acceder al sistema.',
            ], 403);
        }

        return response()->json([
            'message' => 'Las credenciales proporcionadas son incorrectas.',
        ], 401);
    }

    public function getUserPermissions(Request $request){
        $user = $request->user();
        $employee = $user->employee;
        $company = $user->company;

        return response()->json([
            'id' => $user->id,
            'name' => $employee ? $employee->first_name . ' ' . $employee->last_name : $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'company_inactive' => $company && !$company->is_active,
            'setup_step' => $company ? $company->setup_step : null,
        ]);
    }

    public function logout(Request $request){
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    public function loginmobile(Request $request){
        $validator = Validator::make($request->all(), [
            'id_empresa' => 'required|string',
            'correo'     => 'required|email',
            'password'   => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'is_active' => false,
                'message'   => 'Por favor, complete todos los campos con un formato válido.',
                'errors'    => $validator->errors()
            ], 422);
        }

        // 2. Buscar la empresa por su columna 'code'
        $company = Company::where('code', $request->id_empresa)->first();

        if (!$company) {
            return response()->json([
                'is_active' => false,
                'message'   => 'La empresa especificada no existe.'
            ], 404);
        }

        // Validar si la empresa está activa (SaaS Bloqueo)
        if (!$company->is_active) {
            return response()->json([
                'is_active' => false,
                'message'   => 'La empresa se encuentra inactiva. Contacte a soporte.'
            ], 403);
        }

        // 3. Buscar al usuario por correo electrónico Y que pertenezca a esa empresa
        $user = User::where('email', $request->correo)
                    ->where('company_id', $company->id)
                    ->first();

        // 4. Verificar existencia del usuario y contraseña
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'is_active' => false,
                'message'   => 'Credenciales incorrectas para esta empresa.'
            ], 401);
        }

        $userIsActive = (bool) $user->is_active;

        if (!$userIsActive) {
            return response()->json([
                'is_active' => false,
                'message'   => 'Usuario inactivo. Contacte a su administrador.'
            ], 403);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        $isFirstTime = trim((string) ($user->employee?->pin ?? '')) === '';
        
        return response()->json([
            'token'     => $token,
            'user_id'   => (string) $user->id,
            'is_active' => true,
            'is_first_time' => $isFirstTime,
            'message'   => 'Inicio de sesión exitoso.'
        ], 200);
    }

    public function logoutmobile(Request $request)
    {
        // Revocar el token que se está usando actualmente
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ], 200);
    }
}
