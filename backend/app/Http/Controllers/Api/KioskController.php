<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kiosk;
use App\Support\ActivityLogger;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alta de kioskos desde el panel. El token se muestra una sola vez para
 * configurar la tableta o PC de la entrada.
 */
class KioskController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Kiosk::query()->with('office:id,name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'office_id' => ['required', TenantRule::exists('offices')],
        ]);

        $kiosk = new Kiosk($data);
        $token = $kiosk->issueToken();

        return response()->json([...$kiosk->load('office:id,name')->toArray(), 'token' => $token], 201);
    }

    public function update(Request $request, Kiosk $kiosk): JsonResponse
    {
        $kiosk->update($request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'office_id' => ['sometimes', TenantRule::exists('offices')],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return response()->json($kiosk);
    }

    public function regenerateToken(Kiosk $kiosk): JsonResponse
    {
        $token = $kiosk->issueToken();
        ActivityLogger::log('updated', $kiosk, "Generó un código de activación nuevo para {$kiosk->name}");

        return response()->json(['token' => $token]);
    }

    public function destroy(Kiosk $kiosk): JsonResponse
    {
        $kiosk->delete();

        return response()->json(status: 204);
    }
}
