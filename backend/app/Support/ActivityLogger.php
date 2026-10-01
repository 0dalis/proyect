<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\AttendanceRecord;
use App\Models\BonusRule;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\Holiday;
use App\Models\Kiosk;
use App\Models\Office;
use App\Models\PayrollPeriod;
use App\Models\RemoteWorkPeriod;
use App\Models\Shift;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Registra en la bitácora de la empresa activa quién hizo qué.
 *
 *   ActivityLogger::log('downloaded', $employee, 'Descargó la credencial');
 */
class ActivityLogger
{
    /** Nombre corto de cada modelo para filtrar en la bitácora. */
    public const SUBJECT_TYPES = [
        Employee::class => 'employee',
        Office::class => 'office',
        Shift::class => 'shift',
        Area::class => 'area',
        Kiosk::class => 'kiosk',
        Device::class => 'device',
        BonusRule::class => 'bonus_rule',
        EmployeeRequest::class => 'request',
        AttendanceRecord::class => 'attendance',
        RemoteWorkPeriod::class => 'remote_work',
        User::class => 'user',
        Holiday::class => 'holiday',
        PayrollPeriod::class => 'payroll_period',
    ];

    /** Nombre de quien actúa cuando no hay usuario (kiosko, tareas, seeders). */
    private static ?string $fallbackActor = null;

    public static function actingAs(?string $name): void
    {
        self::$fallbackActor = $name;
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $changes = [],
        ?User $actor = null,
        ?int $employeeId = null,
    ): void {
        $tenants = app(TenantManager::class);

        // Sin empresa conectada no hay bitácora donde escribir
        if ($tenants->current() === null) {
            return;
        }

        $actor ??= auth()->user() instanceof User ? auth()->user() : null;
        $request = app()->runningInConsole() ? null : request();

        try {
            ActivityLog::query()->create([
                'user_id' => $actor?->id,
                'user_name' => $actor?->name ?? self::$fallbackActor ?? 'Sistema',
                'action' => $action,
                'subject_type' => $subject ? (self::SUBJECT_TYPES[$subject::class] ?? Str::snake(class_basename($subject))) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? self::label($subject) : null,
                'employee_id' => $employeeId ?? self::employeeIdOf($subject),
                'description' => Str::limit($description ?? self::describe($action, $subject), 250),
                'changes' => $changes ?: null,
                // No se guarda la IP (decisión de privacidad de la empresa)
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            ]);
        } catch (Throwable $e) {
            // La bitácora nunca debe romper la operación que se registra
            report($e);
        }
    }

    public static function label(Model $subject): string
    {
        return match (true) {
            $subject instanceof Employee => $subject->fullName(),
            $subject instanceof User => $subject->name,
            $subject instanceof EmployeeRequest => 'Solicitud #'.$subject->getKey(),
            $subject instanceof AttendanceRecord => 'Checada #'.$subject->getKey(),
            $subject instanceof Device => $subject->name ?: $subject->device_identifier,
            $subject instanceof RemoteWorkPeriod => 'Home office desde '.$subject->starts_on?->format('d/m/Y'),
            default => (string) ($subject->getAttribute('name') ?? class_basename($subject).' #'.$subject->getKey()),
        };
    }

    private static function employeeIdOf(?Model $subject): ?int
    {
        return match (true) {
            $subject instanceof Employee => $subject->getKey(),
            $subject instanceof User => $subject->employee_id,
            $subject !== null && $subject->getAttribute('employee_id') !== null => (int) $subject->getAttribute('employee_id'),
            default => null,
        };
    }

    private static function describe(string $action, ?Model $subject): string
    {
        $verb = match ($action) {
            'created' => 'Creó',
            'updated' => 'Modificó',
            'deleted' => 'Eliminó',
            'downloaded' => 'Descargó',
            default => ucfirst($action),
        };

        return $subject ? "{$verb} ".self::label($subject) : $verb;
    }
}
