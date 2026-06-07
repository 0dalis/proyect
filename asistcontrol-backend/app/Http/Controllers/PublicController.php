<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\JsonResponse;

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
        ]);
    }
}
