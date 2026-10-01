<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\EmployeeAppAccess;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Carga masiva: importar nombre, apellidos y sueldo, y luego organizarlos
 * (oficina, turno, área, tipo y app) desde una tabla.
 */
class EmployeeBulkTest extends TestCase
{
    private Company $company;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->company = $this->createCompany('basico');
        $this->owner = $this->ownerOf($this->company);
    }

    private function import(array $rows): TestResponse
    {
        return $this->as($this->owner)->postJson('/api/employees/import', ['rows' => $rows]);
    }

    private function sampleRows(): array
    {
        return [
            ['first_name' => 'Ana', 'last_name' => 'López', 'salary' => 9500, 'salary_period' => 'monthly'],
            ['first_name' => 'Beto', 'last_name' => 'Ruiz', 'salary' => 450, 'salary_period' => 'daily'],
            ['first_name' => 'Caro', 'last_name' => 'Díaz'],
        ];
    }

    public function test_imported_employees_are_created_without_organization(): void
    {
        $this->import($this->sampleRows())->assertCreated()->assertJsonPath('created', 3)->assertJsonPath('pending', 3);

        app(TenantManager::class)->connect($this->company);
        $ana = Employee::query()->where('first_name', 'Ana')->firstOrFail();
        $this->assertNull($ana->office_id);
        $this->assertNull($ana->shift_id);
        $this->assertNull($ana->employment_type);
        $this->assertEquals(9500, $ana->salary);
        $this->assertSame('monthly', $ana->salary_period);
        $this->assertNotNull($ana->employee_number);
        $this->assertNull(Employee::query()->where('first_name', 'Caro')->value('salary'));

        $this->as($this->owner)->getJson('/api/employees/setup')->assertOk()->assertJsonPath('count', 3);
        $list = collect($this->as($this->owner)->getJson('/api/employees?per_page=50')->json('data'));
        $this->assertSame(3, $list->where('needs_setup', true)->count());
    }

    public function test_the_import_is_checked_row_by_row_and_against_the_plan(): void
    {
        $this->import([['first_name' => 'Sin apellido']])
            ->assertUnprocessable()->assertJsonValidationErrors('rows.0.last_name');
        $this->import([['first_name' => 'A', 'last_name' => 'B', 'salary' => 100]])
            ->assertUnprocessable()->assertJsonValidationErrors('rows.0.salary_period');

        // Básico permite 25
        $rows = array_fill(0, 26, ['first_name' => 'Uno', 'last_name' => 'Más']);
        $this->import($rows)->assertUnprocessable()->assertJsonValidationErrors('rows');

        app(TenantManager::class)->connect($this->company);
        $this->assertSame(0, Employee::query()->count());
    }

    public function test_employees_without_shift_cannot_check_in_and_reports_still_work(): void
    {
        $this->import($this->sampleRows())->assertCreated();
        app(TenantManager::class)->connect($this->company);
        $ana = Employee::query()->where('first_name', 'Ana')->firstOrFail();

        try {
            app(RegisterAttendance::class)->handle($ana, AttendanceChannel::KioskPin);
            $this->fail('No debió checar sin turno');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('turno', $e->getMessage());
        }

        $this->as($this->owner)->getJson('/api/reports/attendance?from=2026-09-01&to=2026-09-15')->assertOk();
        $this->as($this->owner)->getJson("/api/employees/{$ana->getRouteKey()}")->assertOk()->assertJsonPath('needs_setup', true);
    }

    public function test_organizing_assigns_everything_and_gives_app_access(): void
    {
        $this->import($this->sampleRows())->assertCreated();
        app(TenantManager::class)->connect($this->company);
        $ids = Employee::query()->orderBy('id')->pluck('id')->all();
        $office = Office::query()->firstOrFail();
        $shift = Shift::query()->where('office_id', $office->id)->firstOrFail();
        $area = Area::query()->firstOrFail();
        $row = fn (int $id, array $extra = []) => [
            'id' => $id, 'office_id' => $office->id, 'shift_id' => $shift->id, 'area_id' => $area->id,
            'employment_type' => 'permanent', 'app_access' => false, ...$extra,
        ];

        // Turno de otra oficina
        $other = Office::query()->create(['name' => 'Norte', 'geofence_radius' => 50, 'timezone' => 'America/Monterrey']);
        $this->as($this->owner)->postJson('/api/employees/organize', ['employees' => [$row($ids[0], ['office_id' => $other->id])]])
            ->assertUnprocessable()->assertJsonValidationErrors('employees.0.shift_id');
        // Temporal sin fin de contrato y app sin correo
        $this->as($this->owner)->postJson('/api/employees/organize', ['employees' => [
            $row($ids[0], ['employment_type' => 'temporary']),
            $row($ids[1], ['app_access' => true]),
        ]])->assertUnprocessable()->assertJsonValidationErrors(['employees.0.contract_ends_on', 'employees.1.email']);

        $this->as($this->owner)->postJson('/api/employees/organize', ['employees' => [
            $row($ids[0], ['app_access' => true, 'email' => 'ana@empresa.test']),
            $row($ids[1], ['employment_type' => 'temporary', 'contract_ends_on' => '2026-12-31']),
            $row($ids[2]),
        ]])->assertOk()->assertJsonPath('organized', 3)->assertJsonPath('with_app', 1)->assertJsonPath('pending', 0);

        app(TenantManager::class)->connect($this->company);
        $this->assertSame(0, Employee::query()->needsSetup()->count());
        $this->assertSame('temporary', Employee::query()->find($ids[1])->employment_type->value);
        Notification::assertSentTo(User::query()->where('email', 'ana@empresa.test')->firstOrFail(), EmployeeAppAccess::class);
    }

    public function test_the_activity_log_no_longer_stores_the_ip(): void
    {
        $this->import($this->sampleRows());

        app(TenantManager::class)->connect($this->company);
        $log = ActivityLog::query()->latest('id')->firstOrFail();
        $this->assertArrayNotHasKey('ip_address', $log->getAttributes());

        $csv = $this->as($this->owner)->get('/api/activity?format=csv')->streamedContent();
        $this->assertStringNotContainsString('IP', strtok($csv, "\n"));
    }
}
