<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Enums\Role;
use App\Models\Area;
use App\Models\EmployeeRequest;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cada ruta protegida con cada rol: quien no tiene el permiso recibe 403.
 * Los roles permitidos pueden recibir 200, 201, 204 o 422 (validación), nunca 403.
 */
class RoutePermissionsTest extends TestCase
{
    private const OWNER = ['owner'];

    private const ADMINS = ['owner', 'admin'];

    private const TEAM = ['owner', 'admin', 'manager'];

    private const ALL = ['owner', 'admin', 'manager', 'employee'];

    /** @var array<string, User> */
    private array $users = [];

    private array $ids = [];

    public static function routes(): array
    {
        return [
            // empleados.php
            'ver empleados' => ['GET', 'employees', self::TEAM],
            'crear empleado' => ['POST', 'employees', self::ADMINS],
            'editar empleado' => ['PUT', 'employees/{employee}', self::ADMINS],
            'estadísticas del empleado' => ['GET', 'employees/{employee}/stats', self::TEAM],
            'credencial PDF' => ['GET', 'employees/{employee}/badge.pdf', self::ADMINS],
            'credencial capturada' => ['POST', 'employees/{employee}/badge.pdf', self::ADMINS],
            'credenciales en lote' => ['POST', 'badges.pdf', self::ADMINS],
            'foto del empleado' => ['GET', 'employees/{employee}/photo', self::TEAM],
            'subir foto del empleado' => ['POST', 'employees/{employee}/photo', self::ADMINS],
            'quitar foto del empleado' => ['DELETE', 'employees/{employee}/photo', self::ADMINS],
            // historico.php
            'bitácora general' => ['GET', 'activity', self::OWNER],
            'historial del empleado' => ['GET', 'employees/{employee}/activity', self::TEAM],
            // asistencia.php
            'consultar asistencia' => ['GET', 'attendance', self::ALL],
            'registro manual' => ['POST', 'attendance/manual', self::ADMINS],
            'justificar checada' => ['PATCH', 'attendance/{record}/justify', self::ADMINS],
            // solicitudes.php
            'ver solicitudes' => ['GET', 'requests', self::ALL],
            'revisar solicitud' => ['POST', 'requests/{request}/review', self::TEAM],
            // avisos.php
            'bandeja' => ['GET', 'notifications', self::ALL],
            'avisos enviados' => ['GET', 'announcements', self::ADMINS],
            'enviar aviso' => ['POST', 'announcements', self::ADMINS],
            // reportes.php
            'reporte de asistencia' => ['GET', 'reports/attendance?from=2026-09-01&to=2026-09-15', self::TEAM],
            // nomina.php
            'pre-nómina' => ['GET', 'payroll?from=2026-09-01&to=2026-09-15', self::ADMINS],
            'reglas de bonos' => ['GET', 'bonus-rules', self::ADMINS],
            // organizacion.php
            'oficinas' => ['GET', 'offices', self::ADMINS],
            'crear turno' => ['POST', 'shifts', self::ADMINS],
            'áreas' => ['GET', 'areas', self::ADMINS],
            // usuarios.php
            'usuarios' => ['GET', 'users', self::ADMINS],
            'bloquear usuario' => ['PATCH', 'users/{user}/access', self::ADMINS],
            'matriz de roles' => ['GET', 'roles', self::ADMINS],
            'asignar rol' => ['PUT', 'users/{user}/roles', self::ADMINS],
            'editar permisos de un rol' => ['PUT', 'roles/manager/permissions', self::OWNER],
            'mis celulares' => ['GET', 'devices', self::ALL],
            // kioskos.php
            'kioskos' => ['GET', 'kiosks', self::ADMINS],
            // empresa.php
            'configuración de la empresa' => ['PATCH', 'company/settings', self::OWNER],
            'logotipo' => ['GET', 'company/logo', self::ALL],
            'subir logotipo' => ['POST', 'company/logo', self::OWNER],
            'quitar logotipo' => ['DELETE', 'company/logo', self::OWNER],
            'suscripción' => ['GET', 'company/subscription', self::ADMINS],
            'planes para cambiar' => ['GET', 'company/billing/plans', self::OWNER],
            // inicio.php y sesion.php
            'tablero' => ['GET', 'dashboard', self::ALL],
            'mi perfil' => ['GET', 'me', self::ALL],
            'mantener sesión' => ['GET', 'session/ping', self::ALL],
            'mi foto' => ['GET', 'me/avatar', self::ALL],
            'subir mi foto' => ['POST', 'me/avatar', self::ALL],
            'quitar mi foto' => ['DELETE', 'me/avatar', self::ALL],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $company = $this->createCompany('plus', ['payroll_enabled' => true, 'bonuses_enabled' => true, 'employees_can_use_web' => true]);

        app(TenantManager::class)->connect($company);
        $sales = Area::query()->create(['name' => 'Ventas']);

        $target = $this->createEmployee($company, ['area_id' => $sales->id]);
        $managerEmployee = $this->createEmployee($company, ['area_id' => $sales->id]);
        $sales->managers()->attach($managerEmployee->id);

        $this->users = [
            'owner' => $this->ownerOf($company),
            'admin' => $this->createUserFor($company, $this->createEmployee($company), [Role::Admin, Role::Employee]),
            'manager' => $this->createUserFor($company, $managerEmployee, [Role::Manager, Role::Employee]),
            'employee' => $this->createUserFor($company, $this->createEmployee($company, ['area_id' => $sales->id])),
        ];
        $targetUser = $this->createUserFor($company, $target);

        $this->travelTo(Carbon::parse('2026-09-28 09:25', 'America/Mexico_City'));
        app(TenantManager::class)->connect($company);
        $record = app(RegisterAttendance::class)->handle($target->refresh(), AttendanceChannel::KioskPin);
        $request = EmployeeRequest::query()->create([
            'employee_id' => $target->id, 'type' => 'justification', 'starts_on' => '2026-09-28', 'reason' => 'Tráfico',
        ]);
        $request->forceFill(['status' => 'pending'])->save();

        $this->ids = [
            '{employee}' => $target->getRouteKey(),
            '{record}' => $record->id,
            '{request}' => $request->id,
            '{user}' => $targetUser->id,
        ];
    }

    #[DataProvider('routes')]
    public function test_route_is_only_available_to_allowed_roles(string $method, string $uri, array $allowed): void
    {
        $url = '/api/'.strtr($uri, $this->ids);

        foreach ($this->users as $role => $user) {
            $status = $this->as($user)->json($method, $url, $this->bodyFor($uri))->status();

            if (in_array($role, $allowed, true)) {
                $this->assertNotSame(403, $status, "{$role} debería poder usar {$method} {$uri}");
                $this->assertLessThan(500, $status, "{$method} {$uri} falló para {$role}");
            } else {
                $this->assertSame(403, $status, "{$role} NO debería poder usar {$method} {$uri} (respondió {$status})");
            }
        }
    }

    private function bodyFor(string $uri): array
    {
        return match (true) {
            str_contains($uri, 'justify') => ['is_justified' => true],
            str_contains($uri, 'review') => ['decision' => 'approved'],
            str_contains($uri, '/access') => ['app_access' => true],
            str_contains($uri, '/roles') && str_starts_with($uri, 'users') => ['roles' => []],
            str_contains($uri, 'permissions') => ['permissions' => ['employees.view']],
            str_contains($uri, 'company/settings') => ['employees_can_use_web' => true],
            default => [],
        };
    }
}
