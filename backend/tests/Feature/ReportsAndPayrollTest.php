<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Enums\Role;
use App\Models\BonusRule;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportsAndPayrollTest extends TestCase
{
    private Company $company;

    private Employee $punctual;

    private Employee $late;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany('plus', ['payroll_enabled' => true, 'bonuses_enabled' => true]);
        // Semana del lunes 21 al viernes 25 de septiembre de 2026
        $this->punctual = $this->createEmployee($this->company, ['hired_on' => '2026-01-01', 'salary' => 7500, 'salary_period' => 'biweekly']);
        $this->late = $this->createEmployee($this->company, ['hired_on' => '2026-01-01', 'salary' => 7500, 'salary_period' => 'biweekly']);

        $this->travelTo(Carbon::parse('2026-09-26 12:00', 'America/Mexico_City'));

        foreach (['21', '22', '23', '24', '25'] as $day) {
            $this->punchAt($this->punctual, "2026-09-{$day} 08:58");
        }

        // Retardos lunes a miércoles, jueves no vino, viernes a tiempo
        foreach (['21', '22', '23'] as $day) {
            $this->punchAt($this->late, "2026-09-{$day} 09:20");
        }
        $this->punchAt($this->late, '2026-09-25 09:00');
    }

    private function punchAt(Employee $employee, string $time): void
    {
        app(TenantManager::class)->connect($this->company);
        app(RegisterAttendance::class)->handle($employee->refresh(), AttendanceChannel::KioskPin, [], Carbon::parse($time, 'America/Mexico_City'));
    }

    private function period(): array
    {
        return ['from' => '2026-09-21', 'to' => '2026-09-25'];
    }

    public function test_attendance_report_counts_lates_and_missing_days(): void
    {
        $rows = collect($this->as($this->ownerOf($this->company))->getJson('/api/reports/attendance?'.http_build_query($this->period()))
            ->assertOk()->json('rows'))->keyBy('employee.id');

        $this->assertSame(5, $rows[$this->punctual->id]['metrics']['on_time']);
        $this->assertSame(0, $rows[$this->punctual->id]['metrics']['absences']);

        $late = $rows[$this->late->id]['metrics'];
        $this->assertSame(3, $late['unjustified_lates']);
        $this->assertSame(1, $late['unjustified_absences']);
        $this->assertSame(4, $late['worked_days']);
        $this->assertSame(60, $late['minutes_late']);
    }

    public function test_approved_leave_is_not_counted_as_absence(): void
    {
        app(TenantManager::class)->connect($this->company);
        $request = $this->late->hasMany(EmployeeRequest::class)->create([
            'type' => 'leave', 'starts_on' => '2026-09-24', 'reason' => 'Trámite',
        ]);
        $request->forceFill(['status' => 'approved'])->save();

        $rows = collect($this->as($this->ownerOf($this->company))->getJson('/api/reports/attendance?'.http_build_query($this->period()))->json('rows'))->keyBy('employee.id');

        $this->assertSame(0, $rows[$this->late->id]['metrics']['unjustified_absences']);
        $this->assertSame(1, $rows[$this->late->id]['metrics']['excused_days']);
    }

    public function test_report_downloads_as_csv(): void
    {
        $response = $this->as($this->ownerOf($this->company))->get('/api/reports/attendance?format=csv&'.http_build_query($this->period()));

        $response->assertOk();
        $this->assertStringContainsString('Retardos sin justificar', $response->streamedContent());
    }

    public function test_punctuality_bonus_only_for_fewer_than_three_unjustified_lates(): void
    {
        $owner = $this->ownerOf($this->company);

        $this->as($owner)->postJson('/api/bonus-rules', [
            'name' => 'Puntualidad', 'period' => 'biweekly', 'amount_type' => 'fixed', 'amount' => 500,
            'conditions' => [['metric' => 'unjustified_lates', 'operator' => '<', 'value' => 3]],
        ])->assertCreated();

        $rows = collect($this->as($owner)->getJson('/api/payroll?'.http_build_query($this->period()))->assertOk()->json('rows'))->keyBy('employee.id');

        $this->assertSame(500, (int) $rows[$this->punctual->id]['bonus_total']);
        $this->assertSame(0, (int) $rows[$this->late->id]['bonus_total']);

        // 7500 quincenal = 500 diarios; 5 días de periodo y 1 falta sin justificar
        $this->assertEquals(2500, $rows[$this->late->id]['base']);
        $this->assertEquals(500, $rows[$this->late->id]['deductions']);
        $this->assertEquals(2000, $rows[$this->late->id]['total']);
        $this->assertEquals(3000, $rows[$this->punctual->id]['total']);
    }

    public function test_justifying_lates_makes_the_employee_eligible_again(): void
    {
        app(TenantManager::class)->connect($this->company);
        BonusRule::query()->create([
            'name' => 'Puntualidad', 'period' => 'biweekly', 'amount_type' => 'percent', 'amount' => 10,
            'conditions' => [['metric' => 'unjustified_lates', 'operator' => '<', 'value' => 3]],
        ]);
        $recordId = $this->late->attendanceRecords()->where('status', 'late')->value('id');

        $admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);
        $this->as($admin)->patchJson("/api/attendance/{$recordId}/justify", ['is_justified' => true])->assertOk();

        $rows = collect($this->as($admin)->getJson('/api/payroll?'.http_build_query($this->period()))->json('rows'))->keyBy('employee.id');
        $this->assertEquals(250, $rows[$this->late->id]['bonus_total']); // 10% de 2500
    }

    public function test_modules_can_be_turned_off_by_the_owner(): void
    {
        $owner = $this->ownerOf($this->company);
        $this->as($owner)->patchJson('/api/company/settings', ['payroll_enabled' => false, 'bonuses_enabled' => false])->assertOk();

        $this->as($owner->refresh())->getJson('/api/payroll?'.http_build_query($this->period()))
            ->assertForbidden()->assertJsonPath('code', 'module_disabled');
        $this->as($owner)->getJson('/api/bonus-rules')->assertForbidden();
    }

    public function test_managers_cannot_see_payroll(): void
    {
        $manager = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Manager]);

        $this->as($manager)->getJson('/api/payroll?'.http_build_query($this->period()))->assertForbidden();
    }

    public function test_manual_punch_uses_shift_rules(): void
    {
        $admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);

        $this->as($admin)->postJson('/api/attendance/manual', [
            'employee_id' => $this->late->id, 'recorded_at' => '2026-09-24 09:10',
        ])->assertCreated()->assertJsonPath('status', 'on_time')->assertJsonPath('type', 'check_in');
    }

    public function test_employee_sees_own_summary_and_changes_pin(): void
    {
        $user = $this->createUserFor($this->company, $this->late);

        $this->as($user, 'app')->getJson('/api/me/summary')->assertOk()->assertJsonPath('metrics.unjustified_lates', 3);

        $this->as($user, 'app')->putJson('/api/me/pin', ['current_password' => 'mal', 'pin' => '567890', 'pin_confirmation' => '567890'])
            ->assertUnprocessable();
        $this->as($user, 'app')->putJson('/api/me/pin', ['current_password' => 'password', 'pin' => '567890', 'pin_confirmation' => '567890'])
            ->assertOk();

        $this->assertTrue($this->late->refresh()->checkPin('567890'));
    }

    public function test_dashboard_is_scoped_and_subscription_shows_usage(): void
    {
        $owner = $this->ownerOf($this->company);

        $this->as($owner)->getJson('/api/dashboard')->assertOk()->assertJsonCount(14, 'series');
        $this->as($owner)->getJson('/api/company/subscription')
            ->assertOk()
            ->assertJsonPath('usage.employees.used', 2)
            ->assertJsonPath('plan.slug', 'plus');

        $employee = $this->createUserFor($this->company, $this->createEmployee($this->company));
        $this->as($employee, 'app')->getJson('/api/company/subscription')->assertForbidden();
    }
}
