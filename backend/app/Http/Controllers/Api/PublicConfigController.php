<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Recaptcha;
use Illuminate\Http\JsonResponse;

/**
 * Claves públicas que Angular necesita (viven en el .env de Laravel, así se
 * configuran en un solo lugar). Nunca incluye secretos.
 */
class PublicConfigController extends Controller
{
    public function __invoke(Recaptcha $recaptcha): JsonResponse
    {
        return response()->json([
            'recaptcha_site_key' => $recaptcha->siteKey(),
            'support_email' => config('legal.support_email'),
            'legal_version' => config('legal.version'),
        ]);
    }
}
