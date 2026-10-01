<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    /**
     * Configuración visual/empresa para la app móvil.
     * Devuelve nulls cuando no hay archivos personalizados (el cliente usa valores por defecto).
     */
    public function fonts(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        return response()->json([
            'company_name' => $company?->name,
            'splash_url' => null,
            'splash_ext' => null,
            'colors_url' => null,
            'theme_bg_url' => null,
            'theme_bg_ext' => null,
        ]);
    }

    public function deviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request->user()->update(['device_token' => $request->device_token]);

        return response()->json(['message' => 'Dispositivo registrado.']);
    }
}
