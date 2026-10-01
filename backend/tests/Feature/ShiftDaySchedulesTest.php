<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Shift;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Horario especial por día: lunes a jueves de 9 a 18 y el viernes de 9 a 17.
 */
class ShiftDaySchedulesTest extends TestCase
{
    private Company $company;

    private Shift $shift;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany('plus', ['payroll_enabled' => true]);
        app(TenantManager::class)->connect($this->company);

        $this->shift = Shift::query()->create([
            'office_id' => Office::query()->value('id'), 'name' => 'Oficina', 'starts_at' => '09:00', 'ends_at' => '18:00',
            'break_minutes' => 60, 'weekdays' => [1, 2, 3, 4, 5], 'tolerance_minutes' => 10, 'absence_after_minutes' => 30,
            'day_schedules' => ['5' => ['starts_at' => '09:00', 'ends_at' => '17:00', 'break_minutes' => 60]],
        ]);
        $this->employee = $this->createEmployee($this->company, ['shift_id' => $this->shift->id, 'hired_on' => '2024-01-01']);
    }

    private function punch(string $time): AttendanceRecord
    {
        app(TenantManager::class)->connect($this->company);

        return app(RegisterAttendance::class)->handle($this->employee->refresh(), AttendanceChannel::KioskPin, [], Carbon::parse($time, 'America/Mexico_City'));
    }

    public function test_the_shift_knows_each_day_schedule(): void
    {
        $this->assertSame('18:00', $this->shift->scheduleFor(4)['ends_at']);
        $this->assertSame('17:00', $this->shift->scheduleFor(5)['ends_at']);
        $this->assertTrue($this->shift->scheduleFor(5)['special']);
        $this->assertSame(480, $this->shift->scheduledMinutes(4));
        $this->assertSame(420, $this->shift->scheduledMinutes(5));
        $this->assertSame(4 * 480 + 420, $this->shift->weeklyMinutes());
    }

    public function test_leaving_at_five_on_friday_is_on_time(): void
    {
        // Viernes 2 de octubre de 2026
        $this->punch('2026-10-02 09:00');
        $out = $this->punch('2026-10-02 17:00');

        $this->assertSame('on_time', $out->status->value);
        $this->assertSame(0, $out->minutes_early);
        $this->assertSame(0, $out->overtime_minutes);
    }

    public function test_overtime_on_friday_starts_at_five(): void
    {
        $this->punch('2026-10-02 09:00');
        $out = $this->punch('2026-10-02 18:00');

        $this->assertSame(60, $out->overtime_minutes);
    }

    public function test_leaving_at_five_on_thursday_is_an_early_leave(): void
    {
        $this->punch('2026-10-01 09:00');
        $out = $this->punch('2026-10-01 17:00');

        $this->assertSame('early_leave', $out->status->value);
        $this->assertSame(60, $out->minutes_early);
    }

    public function test_special_days_are_saved_from_the_panel_only_for_active_days(): void
    {
        $owner = $this->ownerOf($this->company);
        $officeId = Office::query()->value('id');
        $payload = [
            'office_id' => $officeId, 'name' => 'Tienda', 'starts_at' => '09:00', 'ends_at' => '18:00', 'break_minutes' => 60,
            'weekdays' => [1, 2, 3, 4, 5], 'tolerance_minutes' => 10, 'absence_after_minutes' => 30,
        ];

        $this->as($owner)->postJson('/api/shifts', [...$payload, 'day_schedules' => ['6' => ['starts_at' => '09:00', 'ends_at' => '14:00']]])
            ->assertUnprocessable()->assertJsonValidationErrors('day_schedules');

        $this->as($owner)->postJson('/api/shifts', [...$payload, 'day_schedules' => ['5' => ['starts_at' => '09:00', 'ends_at' => '09:00']]])
            ->assertUnprocessable();

        $shift = $this->as($owner)->postJson('/api/shifts', [...$payload, 'day_schedules' => ['5' => ['starts_at' => '09:00', 'ends_at' => '17:00']]])
            ->assertCreated()
            ->assertJsonPath('day_schedules.5.ends_at', '17:00')
            ->json();

        // Quitar el horario especial
        $this->as($owner)->putJson("/api/shifts/{$shift['id']}", ['day_schedules' => []])
            ->assertOk()
            ->assertJsonPath('day_schedules', null);
    }
}
