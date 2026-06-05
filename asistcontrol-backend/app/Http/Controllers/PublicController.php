<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginWeb(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user && $user->is_blocked) {
                Auth::logout();
                return response()->json([
                    'message' => 'Usuario bloqueado. Contacte al administrador.',
                ], 403);
            }

            $request->session()->regenerate();

            // Adherir cookie user_id (no encriptada, httpOnly false)
            // Importante: En Laravel 12, para que no sea encriptada,
            // se debe configurar en bootstrap/app.php o usar Cookie::queue
            Cookie::queue('user_id', $user->id, 120, null, null, false, false, false, false);

            return response()->json([
                'message' => 'Sesión iniciada correctamente.',
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => 'Las credenciales proporcionadas son incorrectas.',
        ], 401);
    }

    /**
     * Get authenticated user permissions and profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }
}
