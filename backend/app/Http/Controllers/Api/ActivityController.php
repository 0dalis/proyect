<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\ActivityLogger;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bitácora general de la empresa. Incluye acciones generales (exportes,
 * configuración) y acciones sobre empleados (justificaciones, cambios).
 */
class ActivityController extends Controller
{
    public const ACTIONS = [
        'created' => 'Creó',
        'updated' => 'Modificó',
        'deleted' => 'Eliminó',
        'downloaded' => 'Descargó',
        'exported' => 'Exportó',
        'approved' => 'Aprobó',
        'rejected' => 'Rechazó',
        'justified' => 'Justificó',
        'unjustified' => 'Quitó justificación',
        'manual_punch' => 'Registro manual',
        'role_changed' => 'Cambió rol',
        'access_changed' => 'Cambió acceso',
        'permissions_changed' => 'Cambió permisos',
        'settings_changed' => 'Cambió configuración',
        'login' => 'Inició sesión',
        'logout' => 'Cerró sesión',
        'plan_changed' => 'Cambió plan',
        'company_deletion_requested' => 'Solicitó eliminar la empresa',
        'payroll_closed' => 'Cerró pre-nómina',
        'payroll_reopened' => 'Reabrió pre-nómina',
    ];

    public function index(Request $request): JsonResponse|StreamedResponse
    {
        $query = $this->filtered($request);

        if ($request->query('format') === 'csv') {
            $rows = $query->limit(5000)->get();
            ActivityLogger::log('exported', description: "Exportó la bitácora ({$rows->count()} registros)");

            return Csv::download(
                'bitacora-'.now()->format('Y-m-d').'.csv',
                ['Fecha', 'Usuario', 'Acción', 'Descripción', 'Sobre'],
                $rows->map(fn (ActivityLog $log) => [
                    $log->created_at->setTimezone($request->user()->company->timezone)->format('Y-m-d H:i:s'),
                    $log->user_name,
                    self::ACTIONS[$log->action] ?? $log->action,
                    $log->description,
                    $log->subject_label,
                ]),
            );
        }

        return response()->json([
            ...$query->paginate($request->integer('per_page', 50))->toArray(),
            'actions' => self::ACTIONS,
        ]);
    }

    /**
     * @return Builder<ActivityLog>
     */
    public static function filteredFor(Request $request, Builder $query): Builder
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'action' => ['nullable', 'string', 'max:40'],
            'user_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', 'in:all,general,employees'],
        ]);

        return $query
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->when($request->string('action')->toString(), fn ($q, $action) => $q->where('action', $action))
            ->when($request->integer('user_id'), fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($request->integer('employee_id'), fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($request->string('subject_type')->toString(), fn ($q, $type) => $q->where('subject_type', $type))
            ->when($request->query('scope') === 'general', fn ($q) => $q->whereNull('employee_id'))
            ->when($request->query('scope') === 'employees', fn ($q) => $q->whereNotNull('employee_id'))
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where(fn ($inner) => $inner
                ->where('description', 'like', "%{$search}%")
                ->orWhere('user_name', 'like', "%{$search}%")
                ->orWhere('subject_label', 'like', "%{$search}%")))
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * @return Builder<ActivityLog>
     */
    private function filtered(Request $request): Builder
    {
        return self::filteredFor($request, ActivityLog::query());
    }
}
