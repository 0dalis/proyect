<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Enums\AttendanceType;
use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Office;
use App\Models\Shift;
use App\Support\MexicanHolidays;
use App\Support\VacationBalance;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Horas en UTC mostradas por zona de oficina, horas trabajadas y tiempo
 * extra, días festivos, saldo de vacaciones y periodos de pre-nómina cerrados.
 */
class HoursHolidaysAndPayrollPeriodsTest extends TestCase
{
    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany('plus', ['payroll_enabled' => true]);
        app(TenantManager::class)->connect($this->company);
        // Turno de 9 a 18 con una hora de comida: 8 horas de trabajo
        Shift::query()->where('is_default', true)->update(['break_minutes' => 60]);

        $this->employee = $this->createEmployee($this->company, [
            'hired_on' => '2023-06-01', 'salary' => 16000, 'salary_period' => 'monthly',
        ]);
    }

    private function punch(Employee $employee, string $time, string $timezone = 'America/Mexico_City'): AttendanceRecord
    {
        app(TenantManager::class)->connect($this->company);

        return app(RegisterAttendance::class)->handle($employee->refresh(), AttendanceChannel::KioskPin, [], Carbon::parse($time, $timezone));
    }

    public function test_the_database_session_stores_utc(): void
    {
        $zone = DB::connection('tenant')->selectOne('select @@session.time_zone as tz')->tz;

        $this->assertSame('+00:00', $zone);
    }

    public function test_nine_am_in_cancun_and_in_mexico_city_are_both_on_time(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 12:00', 'UTC'));
        $cancun = Office::query()->create(['name' => 'Cancún', 'geofence_radius' => 50, 'timezone' => 'America/Cancun']);
        $shift = Shift::query()->create([
            'office_id' => $cancun->id, 'name' => 'Matutino', 'starts_at' => '09:00', 'ends_at' => '18:00',
            'weekdays' => [1, 2, 3, 4, 5], 'tolerance_minutes' => 10, 'absence_after_minutes' => 30,
        ]);
        $caribe = $this->createEmployee($this->company, ['office_id' => $cancun->id, 'shift_id' => $shift->id]);

        $cdmx = $this->punch($this->employee, '2026-09-28 09:00', 'America/Mexico_City');
        $qroo = $this->punch($caribe, '2026-09-28 09:00', 'America/Cancun');

        // Mismo "9 de la mañana" local, instantes distintos en UTC (Cancún va una hora adelante)
        $this->assertSame('15:00', $cdmx->recorded_at->format('H:i'));
        $this->assertSame('14:00', $qroo->recorded_at->format('H:i'));
        $this->assertSame('on_time', $cdmx->status->value);
        $this->assertSame('on_time', $qroo->status->value);

        $row = collect($this->as($this->ownerOf($this->company))->getJson('/api/attendance?from=2026-09-28&to=2026-09-28')->json('data'))
            ->firstWhere('employee_id', $caribe->id);
        $this->assertSame('America/Cancun', $row['office']['timezone']);
    }

    public function test_only_overtime_after_the_shift_end_is_counted_in_blocks(): void
    {
        // Llegar antes no cuenta: el sueldo es por día y solo se paga lo que pasa de la salida
        $this->punch($this->employee, '2026-09-28 08:00');
        $out = $this->punch($this->employee, '2026-09-28 19:40');

        $this->assertSame(AttendanceType::CheckOut, $out->type);
        // 1 h 40 min después de las 18:00 → 3 bloques de 30 min
        $this->assertSame(90, $out->overtime_minutes);
        $this->assertArrayNotHasKey('worked_minutes', $out->getAttributes());
    }

    public function test_overtime_is_paid_double_up_to_nine_hours_a_week_and_triple_after(): void
    {
        $this->travelTo(Carbon::parse('2026-09-26 12:00', 'America/Mexico_City'));

        // Lunes a viernes, 2 horas extra diarias = 10 horas en la semana
        foreach (['21', '22', '23', '24', '25'] as $day) {
            $this->punch($this->employee, "2026-09-{$day} 09:00");
            $this->punch($this->employee, "2026-09-{$day} 20:00");
        }

        $row = $this->as($this->ownerOf($this->company))->getJson('/api/payroll?from=2026-09-21&to=2026-09-25')
            ->assertOk()->json('rows.0');

        // 16,000 / 30 = 533.33 al día; 8 h de turno → 66.67 la hora
        $this->assertEquals(9.0, $row['overtime']['double_hours']);
        $this->assertEquals(1.0, $row['overtime']['triple_hours']);
        $this->assertEqualsWithDelta(66.67 * (9 * 2 + 1 * 3), $row['overtime']['amount'], 0.5);
        $this->assertSame(600, $row['metrics']['overtime_minutes']);
        $this->assertArrayNotHasKey('worked_minutes', $row['metrics']);
    }

    public function test_official_holidays_are_loaded_and_not_counted_as_absences(): void
    {
        $this->assertArrayHasKey('2026-09-16', MexicanHolidays::forYear(2026));
        $this->assertArrayHasKey('2026-02-02', MexicanHolidays::forYear(2026));
        $this->assertArrayHasKey('2030-10-01', MexicanHolidays::forYear(2030));

        $owner = $this->ownerOf($this->company);
        $this->as($owner)->postJson('/api/holidays/official', ['year' => 2026])->assertOk()->assertJsonPath('added', 7);
        $this->as($owner)->postJson('/api/holidays/official', ['year' => 2026])->assertOk()->assertJsonPath('added', 0);

        $this->travelTo(Carbon::parse('2026-09-19 12:00', 'America/Mexico_City'));
        // Semana del 14 al 18; el miércoles 16 es festivo y no vino
        foreach (['14', '15', '17', '18'] as $day) {
            $this->punch($this->employee, "2026-09-{$day} 09:00");
        }

        $row = collect($this->as($owner)->getJson('/api/reports/attendance?from=2026-09-14&to=2026-09-18')->json('rows'))
            ->firstWhere('employee.id', $this->employee->id);

        $this->assertSame(0, $row['metrics']['absences']);
        $this->assertSame(1, $row['metrics']['holidays']);
    }

    public function test_working_on_a_holiday_pays_double_on_top(): void
    {
        app(TenantManager::class)->connect($this->company);
        Holiday::query()->create(['date' => '2026-09-16', 'name' => 'Independencia', 'is_official' => true]);
        $this->travelTo(Carbon::parse('2026-09-17 12:00', 'America/Mexico_City'));
        $this->punch($this->employee, '2026-09-16 09:00');
        $this->punch($this->employee, '2026-09-16 18:00');

        $row = $this->as($this->ownerOf($this->company))->getJson('/api/payroll?from=2026-09-16&to=2026-09-16')->json('rows.0');

        $this->assertSame(1, $row['metrics']['holidays_worked']);
        $this->assertEqualsWithDelta(16000 / 30 * 2, $row['holiday_pay'], 0.01);
    }

    public function test_vacation_days_follow_the_lft_table(): void
    {
        $this->assertSame(0, VacationBalance::daysFor(0));
        $this->assertSame(12, VacationBalance::daysFor(1));
        $this->assertSame(20, VacationBalance::daysFor(5));
        $this->assertSame(22, VacationBalance::daysFor(10));
        $this->assertSame(24, VacationBalance::daysFor(11));
        $this->assertSame(32, VacationBalance::daysFor(31));
    }

    public function test_employees_can_only_request_the_vacation_days_they_have(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 10:00', 'America/Mexico_City'));
        app(TenantManager::class)->connect($this->company);
        $user = $this->createUserFor($this->company, $this->employee);

        // Ingresó el 1 de junio de 2023: tiene 3 años cumplidos → 16 días
        $this->as($user, 'app')->getJson('/api/me/summary')->assertOk()
            ->assertJsonPath('vacation.entitled', 16)
            ->assertJsonPath('vacation.available', 16);

        // 4 semanas laborables = 20 días: no alcanza
        $this->as($user, 'app')->postJson('/api/requests', [
            'type' => 'vacation', 'starts_on' => '2026-10-05', 'ends_on' => '2026-10-30', 'reason' => 'Viaje',
        ])->assertUnprocessable()->assertJsonValidationErrors('ends_on');

        // Lunes a viernes (5 días laborables; el fin de semana no cuenta)
        $this->as($user, 'app')->postJson('/api/requests', [
            'type' => 'vacation', 'starts_on' => '2026-10-05', 'ends_on' => '2026-10-11', 'reason' => 'Viaje',
        ])->assertCreated();

        $this->as($user, 'app')->getJson('/api/me/summary')
            ->assertJsonPath('vacation.pending', 5)
            ->assertJsonPath('vacation.available', 11);
    }

    public function test_new_employees_have_no_vacation_until_their_first_anniversary(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 10:00', 'America/Mexico_City'));
        $new = $this->createEmployee($this->company, ['hired_on' => '2026-07-01']);
        $user = $this->createUserFor($this->company, $new);

        $this->as($user, 'app')->postJson('/api/requests', [
            'type' => 'vacation', 'starts_on' => '2026-10-05', 'ends_on' => '2026-10-06', 'reason' => 'Viaje',
        ])->assertUnprocessable()->assertJsonFragment(['Aún no cumples un año de antigüedad. Tendrás vacaciones a partir del 01/07/2027.']);
    }

    public function test_a_closed_payroll_period_freezes_the_numbers_and_the_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-09-26 12:00', 'America/Mexico_City'));
        $record = $this->punch($this->employee, '2026-09-21 09:25');
        $owner = $this->ownerOf($this->company);
        $admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin, Role::Employee]);

        $this->as($owner)->postJson('/api/payroll/periods', ['from' => '2026-09-21', 'to' => '2026-09-30'])
            ->assertUnprocessable()->assertJsonValidationErrors('to');

        $period = $this->as($owner)->postJson('/api/payroll/periods', ['from' => '2026-09-21', 'to' => '2026-09-25', 'name' => 'Semana 39'])
            ->assertCreated()->json();

        $this->as($owner)->postJson('/api/payroll/periods', ['from' => '2026-09-25', 'to' => '2026-09-25'])
            ->assertUnprocessable()->assertJsonValidationErrors('from');

        // Las fechas cerradas ya no se modifican
        $this->as($owner)->patchJson("/api/attendance/{$record->id}/justify", ['is_justified' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('is_justified');
        $this->as($owner)->postJson('/api/attendance/manual', ['employee_id' => $this->employee->id, 'recorded_at' => '2026-09-22 09:00'])
            ->assertUnprocessable()->assertJsonValidationErrors('recorded_at');

        // Lo guardado no cambia aunque cambien los datos
        app(TenantManager::class)->connect($this->company);
        Employee::query()->whereKey($this->employee->id)->update(['salary' => 99999]);
        $saved = collect($this->as($owner)->getJson("/api/payroll/periods/{$period['id']}")->assertOk()->json('rows'))
            ->firstWhere('employee.id', $this->employee->id);
        $this->assertEquals(16000, $saved['employee']['salary']);

        // Solo el dueño reabre
        $this->as($admin)->deleteJson("/api/payroll/periods/{$period['id']}")->assertForbidden();
        $this->as($owner)->deleteJson("/api/payroll/periods/{$period['id']}")->assertOk();
        $this->as($owner)->patchJson("/api/attendance/{$record->id}/justify", ['is_justified' => true])->assertOk();
    }

    public function test_daily_salary_pays_each_day_of_the_period(): void
    {
        $this->travelTo(Carbon::parse('2026-09-26 12:00', 'America/Mexico_City'));
        $daily = $this->createEmployee($this->company, ['hired_on' => '2025-01-01', 'salary' => 450, 'salary_period' => 'daily']);
        foreach (['21', '22', '23', '24'] as $day) {
            $this->punch($daily, "2026-09-{$day} 09:00");
        }

        $row = collect($this->as($this->ownerOf($this->company))->getJson('/api/payroll?from=2026-09-21&to=2026-09-27')->json('rows'))
            ->firstWhere('employee.id', $daily->id);

        // 7 días a $450; el viernes faltó → se descuenta un día
        $this->assertEquals(450 * 7, $row['base']);
        $this->assertEquals(450, $row['deductions']);
        $this->as($this->ownerOf($this->company))->putJson("/api/employees/{$daily->getRouteKey()}", ['salary_period' => 'hourly'])
            ->assertUnprocessable();
    }
}
