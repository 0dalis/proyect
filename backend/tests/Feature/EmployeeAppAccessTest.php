<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Plan;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\EmployeeAppAccess;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * El plan solo cuenta empleados; cualquiera de ellos puede tener usuario para
 * la app (interruptor en su ficha). Entra con correo + código de empresa +
 * contraseña temporal y en su primer acceso crea su contraseña y su PIN.
 */
class EmployeeAppAccessTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        app(TenantManager::class)->connect($this->company);

        return [
            'first_name' => 'Luis', 'last_name' => 'Pérez',
            'office_id' => Office::query()->value('id'), 'shift_id' => Shift::query()->value('id'),
            'area_id' => Area::query()->value('id'),
            'employment_type' => 'permanent', 'work_mode' => 'onsite',
            ...$overrides,
        ];
    }

    /** Contraseña temporal que llegó por correo. */
    private function temporaryPasswordOf(User $user): string
    {
        $password = null;

        Notification::assertSentTo($user, EmployeeAppAccess::class, function (EmployeeAppAccess $notification) use ($user, &$password) {
            $lines = implode("\n", $notification->toMail($user)->introLines);
            preg_match('/Contraseña temporal:\*\* (\S+)/', $lines, $match);
            $password = $match[1] ?? null;

            return str_contains($lines, $this->company->code);
        });

        return $password;
    }

    public function test_every_company_gets_a_unique_code(): void
    {
        $other = $this->createCompany();

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $this->company->code);
        $this->assertNotSame($this->company->code, $other->code);
        $this->as($this->owner)->getJson('/api/me')->assertJsonPath('company.code', $this->company->code);
    }

    public function test_plans_only_limit_employees(): void
    {
        $plan = Plan::query()->where('slug', 'basico')->first()->toPublicArray();

        $this->assertArrayNotHasKey('included_users', $plan);
        $this->assertArrayNotHasKey('extra_user_price', $plan);
        $this->as($this->owner)->getJson('/api/me')->assertJsonMissingPath('company.limits.users');
    }

    public function test_employee_created_with_the_app_switch_receives_code_and_temporary_password(): void
    {
        $this->as($this->owner)->postJson('/api/employees', $this->payload([
            'app_access' => true, 'email' => 'luis@empresa.test',
        ]))->assertCreated()->assertJsonPath('app_access', true);

        $user = User::query()->where('email', 'luis@empresa.test')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->app_access);
        $this->assertNotNull($user->employee_id);
        $this->assertNotEmpty($this->temporaryPasswordOf($user));
    }

    public function test_without_app_the_employee_needs_a_six_digit_pin_for_the_kiosk(): void
    {
        $this->as($this->owner)->postJson('/api/employees', $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('pin');
        $this->as($this->owner)->postJson('/api/employees', $this->payload(['pin' => '1234']))
            ->assertUnprocessable()->assertJsonValidationErrors('pin');
        $this->as($this->owner)->postJson('/api/employees', $this->payload(['app_access' => true]))
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->as($this->owner)->postJson('/api/employees', $this->payload(['pin' => '123456']))->assertCreated();
        $this->assertSame(0, User::query()->where('company_id', $this->company->id)->where('is_owner', false)->count());
    }

    public function test_the_app_logs_in_with_email_company_code_and_password_then_must_change_it(): void
    {
        $this->as($this->owner)->postJson('/api/employees', $this->payload(['app_access' => true, 'email' => 'luis@empresa.test']));
        $user = User::query()->where('email', 'luis@empresa.test')->firstOrFail();
        $temporary = $this->temporaryPasswordOf($user);
        $other = $this->createCompany();

        $login = fn (array $data) => $this->postJson('/api/auth/login', [
            'email' => 'luis@empresa.test', 'password' => $temporary, 'client' => 'app', ...$data,
        ]);

        $login([])->assertUnprocessable()->assertJsonValidationErrors('company_code');
        $login(['company_code' => $other->code])->assertUnprocessable()->assertJsonValidationErrors('email');

        $token = $login(['company_code' => strtolower($this->company->code)])
            ->assertOk()
            ->assertJsonPath('user.must_change_password', true)
            ->json('token');

        // Como la app: solo con su token (sin el dueño que actuó antes en la prueba)
        $app = function () use ($token) {
            $this->app['auth']->forgetGuards();

            return $this->withToken($token)->withHeader('X-Client', 'app');
        };

        // Hasta cambiar la contraseña y crear el PIN, no hay nada más
        $app()->getJson('/api/requests')->assertForbidden()->assertJsonPath('code', 'password_change_required');
        $app()->getJson('/api/me')->assertOk();

        $app()->postJson('/api/me/first-access', [
            'password' => $temporary, 'password_confirmation' => $temporary, 'pin' => '246810', 'pin_confirmation' => '246810',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $app()->postJson('/api/me/first-access', [
            'password' => 'nueva1234', 'password_confirmation' => 'nueva1234', 'pin' => '1234', 'pin_confirmation' => '1234',
        ])->assertUnprocessable()->assertJsonValidationErrors('pin');

        $app()->postJson('/api/me/first-access', [
            'password' => 'nueva1234', 'password_confirmation' => 'nueva1234', 'pin' => '246810', 'pin_confirmation' => '246810',
        ])->assertOk()->assertJsonPath('user.must_change_password', false);

        $app()->getJson('/api/requests')->assertOk();
        app(TenantManager::class)->connect($this->company);
        $this->assertTrue(Employee::query()->findOrFail($user->employee_id)->checkPin('246810'));
    }

    public function test_the_switch_turns_the_app_off_and_on_again_keeping_the_account(): void
    {
        $employee = $this->createEmployee($this->company);

        $this->as($this->owner)->putJson("/api/employees/{$employee->getRouteKey()}/app-access", ['enabled' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->as($this->owner)->putJson("/api/employees/{$employee->getRouteKey()}/app-access", ['enabled' => true, 'email' => 'ana@empresa.test'])
            ->assertOk()->assertJsonPath('user.app_access', true);

        $user = User::query()->where('email', 'ana@empresa.test')->firstOrFail();
        $user->createToken('app', ['app']);

        $this->as($this->owner)->putJson("/api/employees/{$employee->getRouteKey()}/app-access", ['enabled' => false])
            ->assertOk()->assertJsonPath('user.app_access', false);
        $this->assertSame(0, $user->tokens()->count());

        $this->as($this->owner)->putJson("/api/employees/{$employee->getRouteKey()}/app-access", ['enabled' => true, 'email' => 'ana@empresa.test'])
            ->assertOk();
        $this->assertSame(1, User::query()->where('employee_id', $employee->id)->where('company_id', $this->company->id)->count());

        $this->as($this->owner)->postJson("/api/employees/{$employee->getRouteKey()}/app-access/resend")->assertOk();
        Notification::assertSentToTimes($user, EmployeeAppAccess::class, 2);
    }

    public function test_an_email_can_only_belong_to_one_account(): void
    {
        $employee = $this->createEmployee($this->company);

        $this->as($this->owner)->putJson("/api/employees/{$employee->getRouteKey()}/app-access", ['enabled' => true, 'email' => $this->owner->email])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_app_users_never_exceed_the_employee_limit(): void
    {
        $free = $this->createCompany('free');
        $owner = $this->ownerOf($free);
        foreach (range(1, 5) as $i) {
            $this->createEmployee($free);
        }
        $extra = $this->createEmployee($free);

        // El sexto empleado queda fuera del plan: ni app ni cambios
        $this->as($owner)->putJson("/api/employees/{$extra->getRouteKey()}/app-access", ['enabled' => true, 'email' => 'sexto@empresa.test'])
            ->assertForbidden()->assertJsonPath('code', 'employee_locked');
        $this->assertNull(User::query()->where('email', 'sexto@empresa.test')->first());
    }

    public function test_employees_without_the_app_check_their_attendance_at_the_kiosk(): void
    {
        $employee = $this->createEmployee($this->company, ['employee_number' => '00077', 'pin' => '135790']);
        $token = $this->as($this->owner)->postJson('/api/kiosks', ['name' => 'Entrada', 'office_id' => $employee->office_id])->json('token');
        $kiosk = fn () => $this->withHeader('X-Kiosk-Token', $token);

        $kiosk()->postJson('/api/kiosk/punch', ['method' => 'pin', 'employee_number' => '00077', 'pin' => '135790'])->assertCreated();

        $kiosk()->postJson('/api/kiosk/my-attendance', ['method' => 'pin', 'employee_number' => '00077', 'pin' => '000000'])
            ->assertUnprocessable();
        $kiosk()->postJson('/api/kiosk/my-attendance', ['method' => 'pin', 'employee_number' => '00077', 'pin' => '135790'])
            ->assertOk()
            ->assertJsonPath('employee.employee_code', $employee->employee_code)
            ->assertJsonCount(1, 'days')
            ->assertJsonStructure(['days' => [['date', 'check_in', 'check_out', 'status_label']]]);
    }
}
