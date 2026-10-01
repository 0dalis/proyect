<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Kiosk;
use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica un kiosko con el encabezado X-Kiosk-Token ({empresa}|{kiosko}|{secreto}).
 */
class AuthenticateKiosk
{
    public function __construct(private TenantManager $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        $parts = explode('|', (string) $request->header('X-Kiosk-Token'), 3);

        if (count($parts) !== 3) {
            return $this->unauthorized();
        }

        [$companyId, $kioskId, $secret] = $parts;
        $company = Company::query()->find($companyId);

        if (! $company || ! $company->status->canOperate()) {
            return $this->unauthorized();
        }

        $this->tenants->connect($company);

        $kiosk = Kiosk::query()->find($kioskId);

        if (! $kiosk || ! $kiosk->is_active || ! hash_equals($kiosk->token_hash, hash('sha256', $secret))) {
            return $this->unauthorized();
        }

        $kiosk->forceFill(['last_seen_at' => now()])->saveQuietly();
        $request->attributes->set('kiosk', $kiosk);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json(['message' => 'Kiosko no autorizado.'], 401);
    }
}
