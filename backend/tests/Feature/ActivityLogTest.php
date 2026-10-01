<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    private Company $company;

    private User $owner;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany();
        $this->owner = $this->ownerOf($this->company);
        $this->employee = $this->createEmployee($this->company, ['first_name' => 'Lucía', 'last_name' => 'Pérez', 'hired_on' => '2026-01-01']);
    }

    private function logs()
    {
        app(TenantManager::class)->connect($this->company);

        return ActivityLog::query()->latest('id');
    }

    public function test_changes_to_an_employee_are_logged_with_before_and_after_and_hidden_pin(): void
    {
        $this->as($this->owner)->putJson("/api/employees/{$this->employee->getRouteKey()}", [
            'position' => 'Supervisora', 'pin' => '987654',
        ])->assertOk();

        $log = $this->logs()->where('action', 'updated')->firstOrFail();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame($this->employee->id, $log->employee_id);
        $this->assertSame('Supervisora', $log->changes['position']['new']);
        $this->assertSame('cambiado', $log->changes['pin_hash']['new']);
        $this->assertStringNotContainsString('987654', json_encode($log->changes));
    }

    public function test_justifying_a_late_arrival_is_linked_to_the_employee_and_the_approver(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 09:25', 'America/Mexico_City'));
        app(TenantManager::class)->connect($this->company);
        $record = app(RegisterAttendance::class)->handle($this->employee->refresh(), AttendanceChannel::KioskPin);

        $admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);
        $this->as($admin)->patchJson("/api/attendance/{$record->id}/justify", ['is_justified' => true])->assertOk();

        $log = $this->logs()->where('action', 'justified')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($this->employee->id, $log->employee_id);
        $this->assertStringContainsString('Lucía Pérez', $log->description);
    }

    public function test_general_actions_like_exports_are_not_linked_to_an_employee(): void
    {
        $this->as($this->owner)->get('/api/reports/attendance?format=csv&from=2026-09-01&to=2026-09-15')->assertOk();

        $log = $this->logs()->where('action', 'exported')->firstOrFail();
        $this->assertNull($log->employee_id);
        $this->assertSame($this->owner->name, $log->user_name);
    }

    public function test_only_the_owner_sees_the_activity_log_by_default(): void
    {
        $admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);
        $manager = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Manager]);

        $this->as($this->owner)->getJson('/api/activity')->assertOk()->assertJsonStructure(['data', 'actions']);
        $this->as($admin)->getJson('/api/activity')->assertForbidden();
        $this->as($manager)->getJson('/api/activity')->assertForbidden();

        // El dueño puede otorgarlo a administradores, nunca a gerentes
        $this->as($this->owner)->putJson('/api/roles/admin/permissions', ['permissions' => ['employees.view', 'audit.view']])->assertOk();
        $this->as($admin->refresh())->getJson('/api/activity')->assertOk();
        $this->as($this->owner)->putJson('/api/roles/manager/permissions', ['permissions' => ['audit.view']])->assertUnprocessable();
    }

    public function test_activity_can_be_filtered_between_general_and_employee_actions(): void
    {
        $this->as($this->owner)->get('/api/reports/attendance?format=csv&from=2026-09-01&to=2026-09-15');
        $this->as($this->owner)->putJson("/api/employees/{$this->employee->getRouteKey()}", ['position' => 'Cajera']);

        $general = collect($this->as($this->owner)->getJson('/api/activity?scope=general')->json('data'));
        $employees = collect($this->as($this->owner)->getJson('/api/activity?scope=employees')->json('data'));

        $this->assertTrue($general->every(fn ($log) => $log['employee_id'] === null));
        $this->assertTrue($general->contains('action', 'exported'));
        $this->assertTrue($employees->every(fn ($log) => $log['employee_id'] !== null));
        $this->assertTrue($employees->contains('action', 'updated'));
    }

    public function test_the_employee_history_shows_what_happened_to_them(): void
    {
        $this->as($this->owner)->putJson("/api/employees/{$this->employee->getRouteKey()}", ['position' => 'Cajera']);
        $this->as($this->owner)->get('/api/reports/attendance?format=csv&from=2026-09-01&to=2026-09-15');

        $history = collect($this->as($this->owner)->getJson("/api/employees/{$this->employee->getRouteKey()}/activity")->assertOk()->json('data'));

        $this->assertTrue($history->contains('action', 'updated'));
        $this->assertFalse($history->contains('action', 'exported'));
    }

    public function test_logins_and_role_changes_are_logged(): void
    {
        $user = $this->createUserFor($this->company, $this->employee);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password', 'client' => 'app', 'company_code' => $user->company->code])->assertOk();
        $this->as($this->owner)->putJson("/api/users/{$user->id}/roles", ['roles' => ['manager']])->assertOk();

        $this->assertTrue($this->logs()->where('action', 'login')->where('user_id', $user->id)->exists());
        $roleLog = $this->logs()->where('action', 'role_changed')->firstOrFail();
        $this->assertSame($this->employee->id, $roleLog->employee_id);
        $this->assertContains('manager', $roleLog->changes['roles']['new']);
    }
}
