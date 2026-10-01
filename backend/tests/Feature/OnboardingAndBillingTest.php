<?php

namespace Tests\Feature;

use App\Actions\ActivateCompany;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\PaymentFailed;
use App\Notifications\PlanDowngraded;
use App\Notifications\PlanStarted;
use App\Support\PlanSeats;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\TestCase;

class OnboardingAndBillingTest extends TestCase
{
    /**
     * Empresa recién confirmada: el dueño está en el modal de bienvenida.
     */
    private function onboardingCompany(): Company
    {
        $company = Company::query()->create([
            'name' => 'Panadería Sol',
            'slug' => 'panaderia-sol-'.Str::random(4),
            'plan_id' => Plan::free()->id,
            'status' => CompanyStatus::Pending,
        ]);
        $owner = User::query()->create([
            'company_id' => $company->id, 'name' => 'Sol', 'email' => 'sol@pan.test',
            'password' => 'password', 'is_owner' => true,
        ]);
        $owner->markEmailAsVerified();
        app(ActivateCompany::class)->prepareOwner($company->refresh());

        return $company->refresh();
    }

    private function accept(User $owner): void
    {
        $this->as($owner)->postJson('/api/onboarding/accept', [
            'authorize_use' => true, 'accept_privacy' => true, 'accept_terms' => true,
        ])->assertOk();
    }

    public function test_owner_in_onboarding_only_reaches_the_welcome_modal(): void
    {
        $owner = $this->ownerOf($this->onboardingCompany());

        $this->as($owner)->getJson('/api/me')->assertOk()->assertJsonPath('company.onboarding', true);
        $this->as($owner)->getJson('/api/onboarding')->assertOk()
            ->assertJsonCount(4, 'plans')
            ->assertJsonPath('billing_mode', 'demo')
            ->assertJsonPath('accepted', false);

        $this->as($owner)->getJson('/api/dashboard')->assertForbidden()->assertJsonPath('code', 'onboarding_required');
        $this->as($owner)->getJson('/api/employees')->assertForbidden()->assertJsonPath('code', 'onboarding_required');
    }

    public function test_plan_cannot_be_chosen_before_accepting_the_terms(): void
    {
        $owner = $this->ownerOf($this->onboardingCompany());

        $this->as($owner)->postJson('/api/onboarding/accept', ['authorize_use' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accept_privacy', 'accept_terms']);

        $this->as($owner)->postJson('/api/onboarding/subscribe', ['plan' => 'plus', 'interval' => 'month'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('accept_terms');
    }

    public function test_free_plan_starts_active_without_trial_or_payroll(): void
    {
        Notification::fake();
        $company = $this->onboardingCompany();
        $owner = $this->ownerOf($company);
        $this->accept($owner);

        $this->as($owner)->postJson('/api/onboarding/subscribe', ['plan' => 'free', 'interval' => 'month'])
            ->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('trial_ends_at', null)
            ->assertJsonPath('user.company.onboarding', false)
            ->assertJsonPath('user.company.includes_payroll', false);

        $company->refresh();
        $this->assertSame(CompanyStatus::Active, $company->status);
        $this->assertNull($company->trial_ends_at);
        $this->assertNotNull($company->database);
        Notification::assertSentTo($owner, PlanStarted::class);

        $this->as($owner)->getJson('/api/dashboard')->assertOk();
        $this->as($owner)->getJson('/api/payroll?from=2026-09-01&to=2026-09-15')
            ->assertForbidden()
            ->assertJsonPath('code', 'module_not_in_plan');
        $this->as($owner)->patchJson('/api/company/settings', ['payroll_enabled' => true])->assertUnprocessable();
    }

    public function test_paid_plan_starts_the_trial_and_charges_one_day_before_it_ends(): void
    {
        // Reloj fijo: en un equipo lento pasan varios segundos entre crear y comparar
        $this->freezeTime();
        Notification::fake();
        $company = $this->onboardingCompany();
        $owner = $this->ownerOf($company);
        $this->accept($owner);

        $response = $this->as($owner)->postJson('/api/onboarding/subscribe', ['plan' => 'plus', 'interval' => 'year'])
            ->assertOk()
            ->assertJsonPath('status', 'trial')
            ->assertJsonPath('interval', 'year')
            ->assertJsonPath('plan.yearly_price', 1299 * 11);

        $trialEnds = $company->refresh()->trial_ends_at;
        $this->assertSame(CompanyStatus::Trial, $company->status);
        $this->assertSame('year', $company->billing_interval);
        $this->assertSame('asist_test_pool_plus', $company->database);
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $trialEnds->timestamp, 5);
        $this->assertSame(
            $trialEnds->copy()->subDay()->toDateString(),
            Carbon::parse($response->json('charge_on'))->toDateString(),
        );
    }

    public function test_the_app_cannot_be_used_while_onboarding(): void
    {
        $owner = $this->ownerOf($this->onboardingCompany());

        $this->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'app', 'company_code' => $owner->company->code])
            ->assertForbidden()
            ->assertJsonPath('code', 'onboarding_required');
    }

    public function test_failed_payment_marks_past_due_and_notifies_the_owner(): void
    {
        Notification::fake();
        $company = $this->createCompany('plus');
        $company->forceFill(['stripe_id' => 'cus_123'])->save();

        $this->stripe('invoice.payment_failed', 'cus_123');
        $this->stripe('invoice.payment_failed', 'cus_123');

        $company->refresh();
        $this->assertSame(CompanyStatus::PastDue, $company->status);
        $this->assertNotNull($company->past_due_since);
        // Stripe reintenta; el aviso sale una sola vez
        Notification::assertSentToTimes($this->ownerOf($company), PaymentFailed::class, 1);

        $this->stripe('invoice.paid', 'cus_123', amountPaid: 129900);
        $this->assertSame(CompanyStatus::Active, $company->refresh()->status);
        $this->assertNull($company->past_due_since);
    }

    public function test_three_days_without_payment_downgrades_to_free(): void
    {
        Notification::fake();
        $company = $this->createCompany('plus', ['payroll_enabled' => true]);
        $company->forceFill(['stripe_id' => 'cus_456'])->save();
        $this->stripe('invoice.payment_failed', 'cus_456');

        $this->travel(2)->days();
        $this->artisan('billing:downgrade-overdue')->assertSuccessful();
        $this->assertSame('plus', $company->refresh()->plan->slug);

        $this->travel(1)->days();
        $this->travel(1)->minutes();
        $this->artisan('billing:downgrade-overdue')->assertSuccessful();

        $company->refresh();
        $this->assertSame('free', $company->plan->slug);
        $this->assertSame(CompanyStatus::Active, $company->status);
        $this->assertFalse($company->payroll_enabled);
        // Los datos se quedan en su base
        $this->assertSame('asist_test_pool_plus', $company->database);
        Notification::assertSentTo($this->ownerOf($company), PlanDowngraded::class);
    }

    public function test_employees_over_the_free_limit_are_listed_but_locked(): void
    {
        $company = $this->createCompany('basico');
        $employees = collect(range(1, 7))->map(fn () => $this->createEmployee($company));
        $lockedEmployee = $employees->last();
        $lockedUser = $this->createUserFor($company, $lockedEmployee);
        $freeUser = $this->createUserFor($company, $employees->first());

        $company->update(['plan_id' => Plan::free()->id]);
        $company->refresh();
        $owner = $this->ownerOf($company);

        app(TenantManager::class)->connect($company);
        $this->assertCount(2, PlanSeats::lockedEmployeeIds($company));

        $list = collect($this->as($owner)->getJson('/api/employees?per_page=50')->assertOk()->json('data'));
        $this->assertCount(7, $list);
        $this->assertSame(2, $list->where('locked_by_plan', true)->count());

        $this->as($owner)->getJson("/api/employees/{$lockedEmployee->getRouteKey()}")->assertOk()->assertJsonPath('locked_by_plan', true);
        $this->as($owner)->putJson("/api/employees/{$lockedEmployee->getRouteKey()}", ['first_name' => 'Nuevo'])
            ->assertForbidden()
            ->assertJsonPath('code', 'employee_locked');
        $this->as($owner)->postJson('/api/attendance/manual', [
            'employee_id' => $lockedEmployee->id, 'type' => 'check_in', 'recorded_at' => now()->toDateTimeString(), 'reason' => 'Olvidó checar',
        ])->assertForbidden();

        // Ni entra ni checa en el kiosko
        $this->fromPanel()->postJson('/api/auth/login', ['email' => $lockedUser->email, 'password' => 'password', 'client' => 'app', 'company_code' => $company->code])
            ->assertForbidden()
            ->assertJsonPath('code', 'employee_locked');
        $this->fromPanel()->postJson('/api/auth/login', ['email' => $freeUser->email, 'password' => 'password', 'client' => 'app', 'company_code' => $company->code])
            ->assertOk();

        $token = $this->as($owner)->postJson('/api/kiosks', ['name' => 'Entrada', 'office_id' => $lockedEmployee->office_id])
            ->assertCreated()->json('token');
        $this->withHeader('X-Kiosk-Token', $token)
            ->postJson('/api/kiosk/punch', ['method' => 'pin', 'employee_number' => $lockedEmployee->employee_number, 'pin' => '123456'])
            ->assertUnprocessable();

        // Al pagar un plan mayor, se desbloquean
        $company->update(['plan_id' => Plan::query()->where('slug', 'plus')->value('id')]);
        $this->as($owner->fresh())->putJson("/api/employees/{$lockedEmployee->getRouteKey()}", ['first_name' => 'Nuevo'])->assertOk();
    }

    public function test_owner_can_upgrade_from_free(): void
    {
        Notification::fake();
        $company = $this->createCompany('free');
        $company->update(['status' => CompanyStatus::Active]);
        $owner = $this->ownerOf($company);

        $this->as($owner)->postJson('/api/company/billing/subscribe', ['plan' => 'free', 'interval' => 'month'])
            ->assertUnprocessable();

        $this->as($owner)->postJson('/api/company/billing/subscribe', ['plan' => 'plus', 'interval' => 'month'])
            ->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('trial_ends_at', null);

        $this->assertSame('plus', $company->refresh()->plan->slug);
    }

    public function test_only_the_owner_changes_the_plan(): void
    {
        $company = $this->createCompany('basico');
        $admin = $this->createUserFor($company, $this->createEmployee($company), [Role::Admin, Role::Employee]);

        $this->as($admin)->postJson('/api/company/billing/subscribe', ['plan' => 'plus', 'interval' => 'month'])->assertForbidden();
        $this->as($admin)->postJson('/api/company/deletion', [])->assertForbidden();
    }

    private function stripe(string $type, string $customer, int $amountPaid = 0): void
    {
        event(new WebhookReceived([
            'type' => $type,
            'data' => ['object' => ['customer' => $customer, 'amount_paid' => $amountPaid]],
        ]));
    }
}
