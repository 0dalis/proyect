<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Shift;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    private const OFFICE_LAT = 19.4361;

    private const OFFICE_LNG = -99.1546;

    private Company $company;

    private Employee $employee;

    private User $user;

    /** @var \OpenSSLAsymmetricKey */
    private $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany();
        Office::query()->update(['latitude' => self::OFFICE_LAT, 'longitude' => self::OFFICE_LNG, 'geofence_radius' => 30]);

        $this->employee = $this->createEmployee($this->company);
        $this->user = $this->createUserFor($this->company, $this->employee);

        // Llave que en el celular vive en Keystore / Secure Enclave
        $this->privateKey = openssl_pkey_get_private(file_get_contents(base_path('tests/Fixtures/device-a.pem')));
        $this->as($this->user, 'app')->postJson('/api/devices', [
            'device_identifier' => 'pixel-8',
            'platform' => 'android',
            'public_key' => openssl_pkey_get_details($this->privateKey)['key'],
        ])->assertCreated()->assertJsonPath('approved', true);
    }

    /**
     * Lunes 28/09/2026 a la hora indicada, hora de CDMX.
     */
    private function at(string $time, string $date = '2026-09-28'): void
    {
        $this->travelTo(Carbon::parse("{$date} {$time}", 'America/Mexico_City'));
    }

    private function punch(array $overrides = [], ?float $latitude = self::OFFICE_LAT, ?float $longitude = self::OFFICE_LNG)
    {
        $signedAt = now()->timestamp;
        openssl_sign("{$this->employee->id}|pixel-8|{$signedAt}", $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return $this->as($this->user, 'app')->postJson('/api/attendance/punch', [
            'method' => 'biometric',
            'device_identifier' => 'pixel-8',
            'signed_at' => $signedAt,
            'signature' => base64_encode($signature),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => 8,
            ...$overrides,
        ]);
    }

    public function test_check_in_within_tolerance_is_on_time(): void
    {
        $this->at('09:14');

        $this->punch()->assertCreated()
            ->assertJsonPath('type', 'check_in')
            ->assertJsonPath('status', 'on_time')
            ->assertJsonPath('minutes_late', 0);
    }

    public function test_check_in_after_tolerance_is_late(): void
    {
        $this->at('09:20');

        $this->punch()->assertCreated()
            ->assertJsonPath('status', 'late')
            ->assertJsonPath('minutes_late', 20);
    }

    public function test_check_in_after_absence_limit_counts_as_absence(): void
    {
        $this->at('09:45');

        $this->punch()->assertCreated()->assertJsonPath('status', 'absent');
    }

    public function test_second_punch_is_check_out_and_detects_early_leave(): void
    {
        $this->at('09:00');
        $this->punch()->assertCreated();

        $this->at('17:30');
        $this->punch()->assertCreated()
            ->assertJsonPath('type', 'check_out')
            ->assertJsonPath('status', 'early_leave')
            ->assertJsonPath('minutes_early', 30);

        $this->at('18:30');
        $this->punch()->assertUnprocessable()->assertJsonValidationErrors('attendance');
    }

    public function test_repeated_punch_within_minutes_is_ignored(): void
    {
        $this->at('09:00');
        $this->punch()->assertCreated();

        $this->at('09:02');
        $this->punch()->assertOk()->assertJsonPath('duplicate', true)->assertJsonPath('type', 'check_in');
    }

    public function test_punch_outside_the_geofence_is_rejected(): void
    {
        $this->at('09:00');

        // ~110 m al norte de la oficina; el radio es 30 m
        $this->punch(latitude: self::OFFICE_LAT + 0.001)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attendance');
    }

    public function test_punch_without_location_is_rejected(): void
    {
        $this->at('09:00');

        $this->punch(latitude: null, longitude: null)->assertUnprocessable();
    }

    public function test_permanent_home_office_skips_geofence_but_keeps_lateness_rules(): void
    {
        $this->employee->update(['work_mode' => WorkMode::Remote]);
        $this->at('09:25');

        $this->punch(latitude: 20.6597, longitude: -103.3496) // Guadalajara
            ->assertCreated()
            ->assertJsonPath('status', 'late');

        $record = $this->employee->attendanceRecords()->first();
        $this->assertTrue($record->geofence_skipped);
        $this->assertEqualsWithDelta(20.6597, $record->latitude, 0.0001);
    }

    public function test_home_office_days_skip_geofence_only_on_those_days(): void
    {
        // Home office solo los lunes
        $this->employee->remoteWorkPeriods()->create(['starts_on' => '2026-09-01', 'weekdays' => [1]]);

        $this->at('09:00', '2026-09-28'); // lunes
        $this->punch(latitude: 20.6597, longitude: -103.3496)->assertCreated();

        $this->at('09:00', '2026-09-29'); // martes
        $this->punch(latitude: 20.6597, longitude: -103.3496)->assertUnprocessable();
    }

    public function test_night_shift_check_out_after_midnight_belongs_to_previous_day(): void
    {
        app(TenantManager::class)->connect($this->company);
        $night = Shift::query()->create([
            'office_id' => $this->employee->office_id, 'name' => 'Nocturno', 'starts_at' => '22:00', 'ends_at' => '06:00',
            'weekdays' => [1, 2, 3, 4, 5, 6, 7], 'tolerance_minutes' => 10, 'absence_after_minutes' => 30,
        ]);
        $this->employee->update(['shift_id' => $night->id]);

        $this->at('22:05', '2026-09-28');
        $this->punch()->assertCreated()->assertJsonPath('status', 'on_time')->assertJsonPath('work_date', '2026-09-28');

        $this->at('06:02', '2026-09-29');
        $this->punch()->assertCreated()
            ->assertJsonPath('type', 'check_out')
            ->assertJsonPath('status', 'on_time')
            ->assertJsonPath('work_date', '2026-09-28');
    }

    public function test_invalid_biometric_signature_is_rejected(): void
    {
        $this->at('09:00');

        $this->punch(['signature' => base64_encode('firma-falsa')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attendance');
    }

    public function test_pin_punch_from_app(): void
    {
        $this->at('09:00');

        $this->as($this->user, 'app')->postJson('/api/attendance/punch', [
            'method' => 'pin', 'device_identifier' => 'pixel-8', 'pin' => '000000',
            'latitude' => self::OFFICE_LAT, 'longitude' => self::OFFICE_LNG,
        ])->assertUnprocessable()->assertJsonValidationErrors('pin');

        $this->as($this->user, 'app')->postJson('/api/attendance/punch', [
            'method' => 'pin', 'device_identifier' => 'pixel-8', 'pin' => '123456',
            'latitude' => self::OFFICE_LAT, 'longitude' => self::OFFICE_LNG,
        ])->assertCreated()->assertJsonPath('status', 'on_time');
    }

    public function test_second_phone_needs_admin_approval(): void
    {
        $key = openssl_pkey_get_private(file_get_contents(base_path('tests/Fixtures/device-b.pem')));

        $this->as($this->user, 'app')->postJson('/api/devices', [
            'device_identifier' => 'iphone-15',
            'platform' => 'ios',
            'public_key' => openssl_pkey_get_details($key)['key'],
        ])->assertCreated()->assertJsonPath('approved', false);
    }

    public function test_approved_late_arrival_notice_marks_late_punch_as_justified(): void
    {
        $manager = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);

        $this->at('08:00');
        $requestId = $this->as($this->user, 'app')->postJson('/api/requests', [
            'type' => 'late_arrival', 'starts_on' => '2026-09-28', 'expected_time' => '10:00', 'reason' => 'Cita médica',
        ])->assertCreated()->json('id');

        $this->as($manager)->postJson("/api/requests/{$requestId}/review", ['decision' => 'approved'])->assertOk();

        $this->at('09:25');
        $this->punch()->assertCreated()->assertJsonPath('status', 'late');

        $this->assertTrue($this->employee->attendanceRecords()->first()->is_justified);
    }
}
