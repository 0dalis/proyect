<?php

namespace App\Http\Middleware;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Support\PlanSeats;
use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Empleados fuera del límite del plan: se pueden consultar, pero nada que
 * los modifique (editar, checar, justificar, revisar sus solicitudes...).
 */
class EnsureEmployeeWithinPlan
{
    public function __construct(private TenantManager $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! $company = $this->tenants->current()) {
            return $next($request);
        }

        foreach ($this->employeeIds($request) as $employeeId) {
            if (PlanSeats::isLocked($company, $employeeId)) {
                return response()->json([
                    'message' => PlanSeats::message($company),
                    'code' => 'employee_locked',
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * @return list<int>
     */
    private function employeeIds(Request $request): array
    {
        $route = $request->route();
        $ids = [];

        foreach ($route?->parameters() ?? [] as $parameter) {
            $ids[] = match (true) {
                $parameter instanceof Employee => $parameter->id,
                $parameter instanceof AttendanceRecord, $parameter instanceof EmployeeRequest => $parameter->employee_id,
                default => null,
            };
        }

        $ids[] = $request->input('employee_id');

        return array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
    }
}
