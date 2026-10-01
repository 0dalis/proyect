<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\EmployeeRequest;
use App\Models\PanelNotification;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Crea las notificaciones de la campana del panel:
 * - Solicitud nueva: al dueño y a quien puede revisarla (administradores y
 *   gerentes del área, según permisos). Son acciones que requieren revisión.
 * - Solicitud revisada: a quien la pidió, con la decisión y el motivo.
 * - Aviso publicado: a gerentes y empleados que lo recibieron. Al dueño y a
 *   los administradores no, su campana es solo para lo que deben revisar.
 */
class PanelNotifier
{
    public static function requestSubmitted(EmployeeRequest $request): void
    {
        $request->loadMissing('employee');
        $name = $request->employee?->fullName() ?? 'Un empleado';

        foreach (self::reviewersOf($request) as $user) {
            self::send($user, PanelNotification::REQUEST_SUBMITTED, "{$name} pidió ".self::typeLabel($request->type), [
                'body' => self::period($request).' · '.Str::limit($request->reason, 140),
                'link' => '/panel/solicitudes',
                'subject_type' => 'request',
                'subject_id' => $request->id,
            ]);
        }
    }

    public static function requestReviewed(EmployeeRequest $request, User $reviewer): void
    {
        $user = self::usersOf([$request->employee_id])->first();

        if (! $user) {
            return;
        }

        $approved = $request->status === RequestStatus::Approved;
        $title = sprintf('%s tu %s', $approved ? 'Aprobaron' : 'Rechazaron', mb_strtolower($request->type->label()));
        $body = self::period($request).' · Revisó '.$reviewer->name
            .($request->review_notes ? ". Motivo: {$request->review_notes}" : '.');

        self::send($user, $approved ? PanelNotification::REQUEST_APPROVED : PanelNotification::REQUEST_REJECTED, $title, [
            'body' => $body,
            'link' => '/panel/solicitudes',
            'subject_type' => 'request',
            'subject_id' => $request->id,
        ]);

        // Los revisores ya no tienen nada pendiente con esta solicitud
        PanelNotification::query()
            ->where('type', PanelNotification::REQUEST_SUBMITTED)
            ->where('subject_type', 'request')
            ->where('subject_id', $request->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function announcementPublished(Announcement $announcement): void
    {
        $employeeIds = $announcement->recipients()->pluck('employees.id')->all();

        foreach (self::usersOf($employeeIds) as $user) {
            if (! in_array($user->primaryRole(), [Role::Manager, Role::Employee], true)) {
                continue;
            }

            self::send($user, PanelNotification::ANNOUNCEMENT, $announcement->title, [
                'body' => Str::limit($announcement->body, 200),
                'link' => '/panel/notificaciones?tab=avisos',
                'subject_type' => 'announcement',
                'subject_id' => $announcement->id,
            ]);
        }
    }

    /**
     * Usuarios que pueden revisar la solicitud: el dueño siempre; los demás
     * con el permiso del tipo (vacaciones u otras) y que vean al empleado.
     *
     * @return Collection<int, User>
     */
    public static function reviewersOf(EmployeeRequest $request): Collection
    {
        $permission = $request->type === RequestType::Vacation ? Permission::VacationsApprove : Permission::RequestsApprove;

        return User::query()
            ->where('company_id', app(TenantManager::class)->currentOrFail()->id)
            ->whereNull('blocked_at')
            ->get()
            ->filter(fn (User $user) => $user->employee_id !== $request->employee_id)
            ->filter(fn (User $user) => $user->is_owner || (
                $user->primaryRole() !== Role::Employee
                && $user->can($permission->value)
                && Visibility::canSeeEmployee($user, $request->employee_id)
            ))
            ->values();
    }

    /**
     * @param  list<int>  $employeeIds
     * @return Collection<int, User>
     */
    private static function usersOf(array $employeeIds): Collection
    {
        if ($employeeIds === []) {
            return collect();
        }

        return User::query()
            ->where('company_id', app(TenantManager::class)->currentOrFail()->id)
            ->whereIn('employee_id', $employeeIds)
            ->whereNull('blocked_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function send(User $user, string $type, string $title, array $attributes): void
    {
        PanelNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => Str::limit($title, 147),
            ...$attributes,
        ]);
    }

    private static function typeLabel(RequestType $type): string
    {
        return match ($type) {
            RequestType::Justification => 'justificar una falta o retardo',
            RequestType::LateArrival => 'avisar que llegará tarde',
            RequestType::EarlyDeparture => 'salir antes',
            RequestType::Vacation => 'vacaciones',
            RequestType::Leave => 'permiso',
        };
    }

    private static function period(EmployeeRequest $request): string
    {
        $format = fn (Carbon $date) => $date->locale('es')->translatedFormat('j \d\e F');

        if ($request->ends_on && ! $request->ends_on->isSameDay($request->starts_on)) {
            return 'Del '.$format($request->starts_on).' al '.$format($request->ends_on);
        }

        $day = ucfirst($format($request->starts_on));

        return $request->expected_time ? "{$day}, ".substr($request->expected_time, 0, 5) : $day;
    }
}
