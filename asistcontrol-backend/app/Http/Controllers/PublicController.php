<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PublicController extends Controller{

    public function loginWeb(Request $request){
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials['is_active'] = true;

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user && $user->is_blocked) {
                Auth::logout();
                return response()->json([
                    'message' => 'Usuario bloqueado. Contacte al administrador.',
                ], 403);
            }

            $request->session()->regenerate();

            Cookie::queue('user_id', $user->id, 120, null, null, false, false, false, false);

            return response()->json([
                'message' => 'Sesión iniciada correctamente.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ],
            ]);
        }
        return response()->json([
            'message' => 'Las credenciales proporcionadas son incorrectas o el usuario está inactivo.',
        ], 401);
    }

    public function getUserPermissions(Request $request){
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout(Request $request){
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);}
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

        // 5. Verificar si el usuario está activo individualmente
        // Nota: Forzamos el cast manual aquí por si acaso, aunque Laravel maneja booleanos,
        // esto asegura la compatibilidad exacta con la estructura que espera tu Flutter.
        $userIsActive = (bool) $user->is_active;

        if (!$userIsActive) {
            return response()->json([
                'is_active' => false,
                'message'   => 'Usuario inactivo. Contacte a su administrador.'
            ], 403);
        }

        // 6. Generar el Token con Laravel Sanctum
        // Puedes guardar metadatos en el nombre del token si lo deseas (ej: 'Flutter-App')
        $token = $user->createToken('auth_token')->plainTextToken;

        // 7. Respuesta exitosa estructurada exactamente como la mapea tu AuthModel en Flutter
        return response()->json([
            'token'     => $token,
            'user_id'   => (string) $user->id,
            'is_active' => true,
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
