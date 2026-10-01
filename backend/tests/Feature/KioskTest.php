<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KioskTest extends TestCase
{
    private Company $company;

    private Employee $employee;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany();
        $this->employee = $this->createEmployee($this->company, ['employee_number' => '00042', 'pin' => '432100']);

        app(TenantManager::class)->connect($this->company);
        $officeId = Office::query()->value('id');

        $this->token = $this->as($this->ownerOf($this->company))->postJson('/api/kiosks', [
            'name' => 'Entrada almacén',
            'office_id' => $officeId,
        ])->assertCreated()->json('token');

        $this->travelTo(Carbon::parse('2026-09-28 09:05', 'America/Mexico_City'));
    }

    private function kiosk(?string $token = null)
    {
        app(TenantManager::class)->forget();

        return $this->withHeader('X-Kiosk-Token', $token ?? $this->token);
    }

    public function test_kiosk_needs_a_valid_token(): void
    {
        $this->kiosk('1|1|incorrecto')->getJson('/api/kiosk/me')->assertUnauthorized();
        $this->kiosk()->getJson('/api/kiosk/me')->assertOk()->assertJsonPath('kiosk.name', 'Entrada almacén');
    }

    public function test_employee_checks_in_with_badge_qr_without_geofence(): void
    {
        $this->kiosk()->postJson('/api/kiosk/punch', ['method' => 'qr', 'qr' => $this->employee->badge_token])
            ->assertCreated()
            ->assertJsonPath('type', 'check_in')
            ->assertJsonPath('status', 'on_time')
            ->assertJsonPath('employee.employee_code', $this->employee->employee_code);
    }

    public function test_reissued_badge_invalidates_the_old_qr(): void
    {
        $oldToken = $this->employee->badge_token;

        $this->as($this->ownerOf($this->company))->postJson("/api/employees/{$this->employee->getRouteKey()}/badge")->assertOk();

        $this->kiosk()->postJson('/api/kiosk/punch', ['method' => 'qr', 'qr' => $oldToken])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr');
    }

    public function test_expired_temporary_badge_is_rejected(): void
    {
        $this->employee->forceFill(['badge_expires_on' => '2026-09-27'])->save();

        $this->kiosk()->postJson('/api/kiosk/punch', ['method' => 'qr', 'qr' => $this->employee->badge_token])
            ->assertUnprocessable();
    }

    public function test_the_kiosk_only_accepts_six_digit_pins(): void
    {
        foreach (['4321', '43210', '4321000'] as $pin) {
            $this->kiosk()->postJson('/api/kiosk/punch', ['method' => 'pin', 'employee_number' => '00042', 'pin' => $pin])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('pin');
            $this->kiosk()->postJson('/api/kiosk/my-attendance', ['method' => 'pin', 'employee_number' => '00042', 'pin' => $pin])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('pin');
        }

        $this->assertSame(0, $this->employee->attendanceRecords()->count());
    }

    public function test_employee_checks_in_with_number_and_pin_and_photo(): void
    {
        $this->kiosk()->postJson('/api/kiosk/punch', ['method' => 'pin', 'employee_number' => '00042', 'pin' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pin');

        $photo = 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        $this->kiosk()->postJson('/api/kiosk/punch', ['method' => 'pin', 'employee_number' => '00042', 'pin' => '432100', 'photo' => $photo])
            ->assertCreated();

        $this->assertNotNull($this->employee->attendanceRecords()->first()->photo_path);
    }

    public function test_kiosk_of_one_company_cannot_find_employees_of_another(): void
    {
        $other = $this->createCompany();
        $stranger = $this->createEmployee($other, ['employee_number' => '00042', 'pin' => '432100']);

        $this->kiosk()->postJson('/api/kiosk/punch', ['method' => 'qr', 'qr' => $stranger->badge_token])
            ->assertUnprocessable();
    }

    public function test_badge_pdf_is_generated(): void
    {
        $this->as($this->ownerOf($this->company))
            ->get("/api/employees/{$this->employee->getRouteKey()}/badge.pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_news_board_shows_only_kiosk_announcements(): void
    {
        $owner = $this->ownerOf($this->company);
        $this->as($owner)->postJson('/api/announcements', ['title' => 'Posada el viernes', 'body' => 'Todos invitados', 'audience_type' => 'all', 'show_on_kiosk' => true])->assertCreated();
        $this->as($owner)->postJson('/api/announcements', ['title' => 'Solo gerentes', 'body' => 'Junta', 'audience_type' => 'roles', 'audience_ids' => ['manager']])->assertCreated();

        $this->kiosk()->getJson('/api/kiosk/news')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Posada el viernes');
    }
}
