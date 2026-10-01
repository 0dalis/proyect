<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Models\Area;
use App\Models\Company;
use App\Models\Office;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\VerifyCompanyEmail;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    private function register(string $email = 'dueno@empresa.test'): void
    {
        $this->postJson('/api/auth/register', [
            'company_name' => 'Mi Empresa',
            'name' => 'Laura Dueña',
            'email' => $email,
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'accept_terms' => true,
        ])->assertCreated();
    }

    private function verificationUrlFor(User $user): string
    {
        return URL::temporarySignedRoute('web.publico.verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ], absolute: false);
    }

    public function test_registration_leaves_company_pending_and_sends_verification_email(): void
    {
        Notification::fake();

        $this->register();

        $owner = User::query()->where('email', 'dueno@empresa.test')->firstOrFail();

        $this->assertTrue($owner->is_owner);
        $this->assertNull($owner->email_verified_at);
        $this->assertSame(CompanyStatus::Pending, $owner->company->status);
        $this->assertSame('free', $owner->company->plan->slug);
        $this->assertNull($owner->company->database);
        Notification::assertSentTo($owner, VerifyCompanyEmail::class);
    }

    public function test_owner_cannot_log_in_until_email_is_verified(): void
    {
        Notification::fake();
        $this->register();

        $this->fromPanel()->postJson('/api/auth/login', ['email' => 'dueno@empresa.test', 'password' => 'secreto123', 'client' => 'web'])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');
    }

    public function test_verification_then_onboarding_starts_the_trial_and_provisions_the_company(): void
    {
        Notification::fake();
        $this->register();
        $owner = User::query()->where('email', 'dueno@empresa.test')->firstOrFail();

        $this->postJson($this->verificationUrlFor($owner))
            ->assertOk()
            ->assertJsonPath('status', 'verified');

        $company = $owner->company->refresh();
        $this->assertSame(CompanyStatus::Onboarding, $company->status);
        $this->assertNull($company->database);
        setPermissionsTeamId($company->id);
        $this->assertTrue($owner->refresh()->hasRole('owner'));

        $this->fromPanel()->postJson('/api/auth/login', ['email' => 'dueno@empresa.test', 'password' => 'secreto123', 'client' => 'web'])
            ->assertOk()
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('user.company.onboarding', true)
            ->assertJsonMissingPath('token');

        $this->as($owner)->postJson('/api/onboarding/accept', ['authorize_use' => true, 'accept_privacy' => true, 'accept_terms' => true])->assertOk();
        $this->as($owner)->postJson('/api/onboarding/subscribe', ['plan' => 'basico', 'interval' => 'month'])
            ->assertOk()
            ->assertJsonPath('status', 'trial');

        $company->refresh();
        $this->assertSame(CompanyStatus::Trial, $company->status);
        $this->assertSame('basico', $company->plan->slug);
        $this->assertTrue($company->trial_ends_at->isAfter(now()->addDays(13)));
        $this->assertSame('asist_test_pool_basic', $company->database);

        app(TenantManager::class)->connect($company);
        $this->assertSame(1, Office::query()->where('is_default', true)->count());
        $this->assertSame(1, Shift::query()->where('is_default', true)->count());
        $this->assertNotNull($owner->refresh()->terms_accepted_at);
    }

    public function test_tampered_verification_link_is_rejected(): void
    {
        Notification::fake();
        $this->register();
        $owner = User::query()->where('email', 'dueno@empresa.test')->firstOrFail();

        $this->getJson($this->verificationUrlFor($owner).'x')->assertOk()->assertJsonPath('status', 'invalid');
        $this->postJson($this->verificationUrlFor($owner).'x')->assertForbidden()->assertJsonPath('status', 'invalid');
        $this->assertSame(CompanyStatus::Pending, $owner->company->refresh()->status);
    }

    public function test_each_plan_uses_its_own_database_tier(): void
    {
        $basic = $this->createCompany('basico');
        $free = $this->createCompany('free');
        $plus = $this->createCompany('plus');
        $premium = $this->createCompany('premium');

        $this->assertSame('asist_test_pool_basic', $basic->database);
        $this->assertSame('asist_test_pool_basic', $free->database);
        $this->assertSame('asist_test_pool_plus', $plus->database);
        $this->assertSame("asist_test_tenant_{$premium->id}", $premium->database);
    }

    public function test_companies_sharing_a_database_cannot_see_each_other(): void
    {
        $companyA = $this->createCompany('basico');
        $companyB = $this->createCompany('basico');
        $employeeA = $this->createEmployee($companyA, ['first_name' => 'Solo De A']);
        $this->createEmployee($companyB, ['first_name' => 'Solo De B']);

        $ownerB = $this->ownerOf($companyB);

        $this->as($ownerB)->getJson('/api/employees')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Solo De B');

        $this->as($ownerB)->getJson("/api/employees/{$employeeA->getRouteKey()}")->assertNotFound();
        $this->as($ownerB)->putJson("/api/employees/{$employeeA->getRouteKey()}", ['first_name' => 'Hackeado'])->assertNotFound();
    }

    /**
     * El panel guarda lo que devuelve /me como el usuario actual: debe tener
     * la misma forma que "user" en el login (sin envoltura "data").
     */
    public function test_me_returns_the_same_shape_as_login(): void
    {
        $company = $this->createCompany();
        $owner = $this->ownerOf($company);

        $login = $this->fromPanel()->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'web'])
            ->assertOk()
            ->json('user');

        $this->as($owner)->getJson('/api/me')
            ->assertOk()
            ->assertJsonMissingPath('data')
            ->assertJsonPath('role', 'owner')
            ->assertJsonPath('company.id', $company->id)
            ->assertJsonStructure(array_keys($login));
    }

    public function test_suspended_company_cannot_use_the_api(): void
    {
        $company = $this->createCompany();
        $company->update(['status' => CompanyStatus::Suspended]);

        $this->as($this->ownerOf($company))->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'company_inactive');
    }

    public function test_plan_employee_limit_is_enforced(): void
    {
        $company = $this->createCompany('free');
        foreach (range(1, 5) as $i) {
            $this->createEmployee($company);
        }

        app(TenantManager::class)->connect($company);
        $payload = [
            'first_name' => 'Uno', 'last_name' => 'Más', 'pin' => '123456',
            'office_id' => Office::query()->value('id'), 'shift_id' => Shift::query()->value('id'),
            'area_id' => Area::query()->value('id'),
            'employment_type' => 'permanent', 'work_mode' => 'onsite',
        ];

        $this->as($this->ownerOf($company))->postJson('/api/employees', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employee');

        Company::query()->whereKey($company->id)->update(['extra_employee_blocks' => 1]);

        $this->as($this->ownerOf($company))->postJson('/api/employees', $payload)->assertCreated();
    }
}
