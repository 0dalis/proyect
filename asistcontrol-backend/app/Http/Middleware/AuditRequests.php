<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditRequests
{
    /**
     * Registra en auditoría las peticiones que modifican datos.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $request->user()) {
            try {
                AuditLog::record(
                    $request->user()->company_id,
                    $request->user()->id,
                    strtolower($request->method()) . ' ' . $request->path(),
                    null,
                    null,
                    ['status' => $response->getStatusCode()]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $response;
    }
}
