<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Company;
use App\Models\Employee;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany();
        $this->employee = $this->createEmployee($this->company, ['hired_on' => '2026-01-01']);
        $this->travelTo(Carbon::parse('2026-09-26 12:00', 'America/Mexico_City'));

        // Semana del 21 al 25: lunes a tiempo, martes retardo, miércoles falta, jueves y viernes a tiempo
        foreach (['21 08:55', '22 09:20', '24 09:00', '25 09:05'] as $moment) {
            app(TenantManager::class)->connect($this->company);
            app(RegisterAttendance::class)->handle($this->employee->refresh(), AttendanceChannel::KioskPin, [], Carbon::parse("2026-09-{$moment}", 'America/Mexico_City'));
        }
    }

    public function test_weekly_stats_have_a_bucket_per_day(): void
    {
        $data = $this->as($this->ownerOf($this->company))
            ->getJson("/api/employees/{$this->employee->getRouteKey()}/stats?period=week&date=2026-09-23")
            ->assertOk()
            ->json();

        $this->assertCount(7, $data['buckets']);
        $byDay = collect($data['buckets'])->keyBy('key');
        $this->assertSame(1, $byDay['2026-09-21']['on_time']);
        $this->assertSame(1, $byDay['2026-09-22']['late']);
        $this->assertSame(1, $byDay['2026-09-23']['absent']);
        $this->assertSame(1, $data['metrics']['unjustified_absences']);
    }

    public function test_yearly_stats_are_grouped_by_month(): void
    {
        $data = $this->as($this->ownerOf($this->company))
            ->getJson("/api/employees/{$this->employee->getRouteKey()}/stats?period=year&date=2026-09-23")
            ->assertOk()
            ->json();

        $this->assertCount(12, $data['buckets']);
        $september = collect($data['buckets'])->firstWhere('key', '2026-09');
        $this->assertSame(3, $september['on_time']);
        $this->assertSame(1, $september['late']);
    }

    public function test_report_pdf_is_downloaded_and_logged(): void
    {
        $owner = $this->ownerOf($this->company);

        $this->as($owner)
            ->post("/api/employees/{$this->employee->getRouteKey()}/report.pdf", ['period' => 'month', 'date' => '2026-09-10'])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        app(TenantManager::class)->connect($this->company);
        $log = ActivityLog::query()->where('action', 'downloaded')->firstOrFail();
        $this->assertSame($this->employee->id, $log->employee_id);
        $this->assertSame($owner->id, $log->user_id);
    }

    public function test_report_prints_the_calendar_capture_at_the_end(): void
    {
        $owner = $this->ownerOf($this->company);
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $pdf = $this->as($owner)
            ->post("/api/employees/{$this->employee->getRouteKey()}/report.pdf", [
                'period' => 'month',
                'date' => '2026-09-10',
                'calendar_capture' => "data:image/png;base64,{$png}",
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->getContent();

        $this->assertStringContainsString('/Subtype /Image', $pdf);
    }

    public function test_report_is_downloadable_without_a_usable_calendar_capture(): void
    {
        $owner = $this->ownerOf($this->company);

        $pdf = $this->as($owner)
            ->post("/api/employees/{$this->employee->getRouteKey()}/report.pdf", [
                'period' => 'month',
                'date' => '2026-09-10',
                'calendar_capture' => 'data:image/png;base64,no-es-base64@@',
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->getContent();

        $this->assertStringNotContainsString('/Subtype /Image', $pdf);
    }

    public function test_report_reads_the_period_from_the_request_body(): void
    {
        $owner = $this->ownerOf($this->company);

        $this->as($owner)
            ->post("/api/employees/{$this->employee->getRouteKey()}/report.pdf", ['period' => 'week', 'date' => '2026-09-10'])
            ->assertOk();

        app(TenantManager::class)->connect($this->company);
        $log = ActivityLog::query()->where('action', 'downloaded')->firstOrFail();
        $this->assertStringContainsString('07/09/2026 – 13/09/2026', $log->description);
    }

    public function test_detail_includes_the_linked_user(): void
    {
        $user = $this->createUserFor($this->company, $this->employee);

        $this->as($this->ownerOf($this->company))
            ->getJson("/api/employees/{$this->employee->getRouteKey()}")
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'employee');
    }

    public function test_managers_cannot_open_employees_outside_their_areas(): void
    {
        app(TenantManager::class)->connect($this->company);
        $otherArea = Area::query()->create(['name' => 'Otra']);
        $managerEmployee = $this->createEmployee($this->company, ['area_id' => $otherArea->id]);
        $otherArea->managers()->attach($managerEmployee->id);
        $manager = $this->createUserFor($this->company, $managerEmployee, [Role::Manager]);

        $this->as($manager)->getJson("/api/employees/{$this->employee->getRouteKey()}/stats")->assertNotFound();
        $this->as($manager)->post("/api/employees/{$this->employee->getRouteKey()}/report.pdf")->assertNotFound();
    }
}
