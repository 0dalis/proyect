<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Area;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\Office;
use App\Models\PanelNotification;
use App\Models\Shift;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Inicio de la empresa (estado de hoy, por oficina y turno, métricas del mes)
 * y la campana de notificaciones del panel.
 */
class DashboardAndNotificationsTest extends TestCase
{
    private Company $company;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        // Los empleados también usan el panel web (y su campana)
        $this->company = $this->createCompany('plus', ['employees_can_use_web' => true]);
        $this->owner = $this->ownerOf($this->company);
        // Lunes 28/09/2026, 10:30 en CDMX
        $this->travelTo(Carbon::parse('2026-09-28 10:30', 'America/Mexico_City'));
    }

    private function checkIn(Employee $employee, string $status, int $minutesLate = 0): void
    {
        app(TenantManager::class)->connect($this->company);

        AttendanceRecord::query()->create([
            'employee_id' => $employee->id,
            'office_id' => $employee->office_id,
            'shift_id' => $employee->shift_id,
            'work_date' => '2026-09-28',
            'type' => 'check_in',
            'recorded_at' => Carbon::parse('2026-09-28 09:05', 'America/Mexico_City')->addMinutes($minutesLate),
            'channel' => 'app_pin',
            'status' => $status,
            'minutes_late' => $minutesLate,
        ]);
    }

    private function approved(Employee $employee, string $type, string $from, string $to): void
    {
        app(TenantManager::class)->connect($this->company);

        EmployeeRequest::query()->create([
            'employee_id' => $employee->id, 'type' => $type, 'starts_on' => $from, 'ends_on' => $to, 'reason' => 'Motivo',
        ])->forceFill(['status' => RequestStatus::Approved])->save();
    }

    public function test_today_shows_who_registered_attendance_and_who_did_not(): void
    {
        $onTime = $this->createEmployee($this->company, ['first_name' => 'Ana', 'last_name' => 'Puntual']);
        $late = $this->createEmployee($this->company, ['first_name' => 'Beto', 'last_name' => 'Tarde']);
        $this->createEmployee($this->company, ['first_name' => 'Carla', 'last_name' => 'Ausente']);
        $vacation = $this->createEmployee($this->company, ['first_name' => 'Dora', 'last_name' => 'Playa']);
        $leave = $this->createEmployee($this->company);

        // Segunda oficina con turno de tarde y un turno de fin de semana
        $north = Office::query()->create(['name' => 'Sucursal Norte', 'timezone' => 'America/Mexico_City']);
        $evening = Shift::query()->create([
            'office_id' => $north->id, 'name' => 'Tarde', 'starts_at' => '14:00', 'ends_at' => '22:00',
            'break_minutes' => 0, 'weekdays' => [1, 2, 3, 4, 5], 'tolerance_minutes' => 10, 'absence_after_minutes' => 30,
        ]);
        $weekend = Shift::query()->create([
            'office_id' => $north->id, 'name' => 'Fin de semana', 'starts_at' => '08:00', 'ends_at' => '16:00',
            'break_minutes' => 0, 'weekdays' => [6, 7], 'tolerance_minutes' => 10, 'absence_after_minutes' => 30,
        ]);
        $this->createEmployee($this->company, ['office_id' => $north->id, 'shift_id' => $evening->id]);
        $this->createEmployee($this->company, ['office_id' => $north->id, 'shift_id' => $weekend->id]);

        $this->checkIn($onTime, 'on_time');
        $this->checkIn($late, 'late', 25);
        $this->approved($vacation, 'vacation', '2026-09-28', '2026-10-02');
        $this->approved($leave, 'leave', '2026-09-28', '2026-09-28');

        $response = $this->as($this->owner)->getJson('/api/dashboard')->assertOk();

        $response
            ->assertJsonPath('team.total', 7)
            // El de fin de semana descansa hoy: no cuenta
            ->assertJsonPath('team.scheduled', 6)
            ->assertJsonPath('team.registered', 2)
            ->assertJsonPath('team.in_office_now', 2)
            ->assertJsonPath('team.counts.on_time', 1)
            ->assertJsonPath('team.counts.late', 1)
            ->assertJsonPath('team.counts.missing', 1)
            ->assertJsonPath('team.counts.vacation', 1)
            ->assertJsonPath('team.counts.leave', 1)
            ->assertJsonPath('team.counts.upcoming', 1)
            ->assertJsonPath('team.counts.rest', 1)
            ->assertJsonPath('team.missing.0.name', 'Carla Ausente')
            ->assertJsonPath('team.late.0.minutes_late', 25)
            ->assertJsonPath('today.checked_in', 2)
            ->assertJsonPath('month.top_lates.0.name', 'Beto Tarde')
            ->assertJsonPath('punctuality.current', 50)
            ->assertJsonPath('upcoming_vacations.0.name', 'Dora Playa')
            ->assertJsonPath('upcoming_vacations.0.ongoing', true);

        $offices = collect($response->json('team.by_office'))->keyBy('name');
        $this->assertSame(2, $offices['Sucursal Norte']['total']);
        $this->assertSame(1, $offices['Sucursal Norte']['counts']['upcoming']);
        $this->assertCount(3, $response->json('team.by_shift'));
    }

    public function test_by_office_is_empty_with_a_single_office(): void
    {
        $this->createEmployee($this->company);

        $this->as($this->owner)->getJson('/api/dashboard')
            ->assertJsonPath('team.by_office', [])
            ->assertJsonPath('team.by_shift', []);
    }

    public function test_requests_notify_reviewers_and_decisions_notify_the_requester(): void
    {
        app(TenantManager::class)->connect($this->company);
        $sales = Area::query()->create(['name' => 'Ventas']);
        $warehouse = Area::query()->create(['name' => 'Almacén']);
        $admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);
        $managerEmployee = $this->createEmployee($this->company, ['area_id' => $sales->id]);
        $sales->managers()->attach($managerEmployee->id);
        $manager = $this->createUserFor($this->company, $managerEmployee, [Role::Manager, Role::Employee]);
        $otherManagerEmployee = $this->createEmployee($this->company, ['area_id' => $warehouse->id]);
        $warehouse->managers()->attach($otherManagerEmployee->id);
        $otherManager = $this->createUserFor($this->company, $otherManagerEmployee, [Role::Manager, Role::Employee]);
        $seller = $this->createUserFor($this->company, $this->createEmployee($this->company, ['first_name' => 'Luis', 'last_name' => 'Vega', 'area_id' => $sales->id]));

        $id = $this->as($seller, 'app')->postJson('/api/requests', [
            'type' => 'leave', 'starts_on' => '2026-09-30', 'reason' => 'Cita médica',
        ])->assertCreated()->json('id');

        // Revisan: dueño, administrador y el gerente de Ventas; no el de Almacén ni el propio empleado
        foreach ([$this->owner, $admin, $manager] as $reviewer) {
            $this->as($reviewer)->getJson('/api/panel-notifications')
                ->assertJsonPath('unread', 1)
                ->assertJsonPath('data.0.type', 'request_submitted')
                ->assertJsonPath('data.0.title', 'Luis Vega pidió permiso')
                ->assertJsonPath('data.0.link', '/panel/solicitudes');
        }
        $this->as($otherManager)->getJson('/api/panel-notifications/unread')->assertJsonPath('unread', 0);
        $this->as($seller)->getJson('/api/panel-notifications/unread')->assertJsonPath('unread', 0);

        $this->as($admin)->postJson("/api/requests/{$id}/review", ['decision' => 'rejected', 'notes' => 'Hay inventario ese día'])->assertOk();

        $this->as($seller)->getJson('/api/panel-notifications')
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('data.0.type', 'request_rejected')
            ->assertJsonPath('data.0.title', 'Rechazaron tu permiso');
        $this->assertStringContainsString('Hay inventario ese día', $this->as($seller)->getJson('/api/panel-notifications')->json('data.0.body'));

        // Ya revisada: deja de estar pendiente en la campana de los demás
        $this->as($this->owner)->getJson('/api/panel-notifications/unread')->assertJsonPath('unread', 0);
    }

    public function test_announcements_reach_managers_and_employees_but_not_owner_or_admins(): void
    {
        $admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);
        $employee = $this->createUserFor($this->company, $this->createEmployee($this->company));

        $this->as($this->owner)->postJson('/api/announcements', [
            'title' => 'Junta general', 'body' => 'El viernes a las 10', 'audience_type' => 'all',
        ])->assertCreated();

        $this->as($employee)->getJson('/api/panel-notifications')
            ->assertJsonPath('data.0.type', 'announcement')
            ->assertJsonPath('data.0.title', 'Junta general');
        $this->as($admin)->getJson('/api/panel-notifications/unread')->assertJsonPath('unread', 0);
        $this->as($this->owner)->getJson('/api/panel-notifications/unread')->assertJsonPath('unread', 0);

        // Leerlo en la campana también lo marca leído en "Avisos"
        $notification = $this->as($employee)->getJson('/api/panel-notifications')->json('data.0.id');
        $this->as($employee)->postJson("/api/panel-notifications/{$notification}/read")->assertOk()->assertJsonPath('unread', 0);
        $this->assertNotNull($this->as($employee)->getJson('/api/notifications')->json('data.0.pivot.read_at'));
    }

    public function test_users_only_touch_their_own_notifications(): void
    {
        $employee = $this->createUserFor($this->company, $this->createEmployee($this->company));
        app(TenantManager::class)->connect($this->company);
        $mine = PanelNotification::query()->create(['user_id' => $this->owner->id, 'type' => 'request_submitted', 'title' => 'A']);
        PanelNotification::query()->create(['user_id' => $this->owner->id, 'type' => 'request_submitted', 'title' => 'B']);

        $this->as($employee)->postJson("/api/panel-notifications/{$mine->id}/read")->assertNotFound();

        $this->as($this->owner)->postJson('/api/panel-notifications/read-all')->assertOk()->assertJsonPath('unread', 0);
        $this->as($this->owner)->getJson('/api/panel-notifications?unread=1')->assertJsonCount(0, 'data');
        $this->as($this->owner)->getJson('/api/panel-notifications')->assertJsonCount(2, 'data');
    }
}
