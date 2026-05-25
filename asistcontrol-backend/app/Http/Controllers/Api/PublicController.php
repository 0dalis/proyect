<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;

class PublicController extends Controller{
    public function loginWeb(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.']
            ]);
        }

        $user = Auth::user();

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Tu cuenta ha sido suspendida. Contacta al administrador.'
            ], 403);
        }

        // Limpieza y generación del token de Sanctum
        $user->tokens()->where('name', 'web-token')->delete();
        $tokenResult = $user->createToken('web-token', ['web-access']);
        $token = $tokenResult->plainTextToken;

        // Configuración de la cookie HttpOnly
        $cookie = cookie(
            'web_session',     
            $token,            
            1440,              
            '/',               
            null,              
            false, // Recuerda cambiar a true en producción bajo HTTPS
            true,  // httpOnly
            false, 
            'Lax'  
        );

        // JSON minimalista de éxito
        return response()->json([
            'status' => 'success',
            'message' => 'Autenticado correctamente.'
        ])->withCookie($cookie);
    }
    /**
     * LOGIN MOBILE
     * Más flexible: token más duradero
     */
    public function loginMobile(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas']
            ]);
        }

        $user = $request->user();

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Usuario bloqueado'
            ], 403);
        }
        $token = $user->createToken(
            $request->device_name, 
            ['mobile']
        )->plainTextToken;
        return response()->json([
            'token' => $token,
            'type' => 'mobile',
            'user' => $user
        ]);
    }
}