<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use App\Notifications\VerifyCompanyEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Página "Confirmar cuenta": ya activa → al login; vencido → se envía otro;
 * pendiente → botón con reCAPTCHA. Registro y login también usan reCAPTCHA.
 */
class EmailVerificationTest extends TestCase
{
    private function registerOwner(array $overrides = []): User
    {
        $this->postJson('/api/auth/register', [
            'company_name' => 'Ferretería Luna',
            'name' => 'Ana Luna',
            'email' => 'ana@luna.test',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'accept_terms' => true,
            'recaptcha_token' => 'token-ok',
            ...$overrides,
        ])->assertCreated();

        return User::query()->where('email', 'ana@luna.test')->firstOrFail();
    }

    private function link(User $user, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('web.publico.verification.verify', now()->addMinutes($minutes), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ], absolute: false);
    }

    /** Respuesta que dará Google en la siguiente verificación. */
    private array $google = [];

    private function fakeRecaptcha(float $score = 0.9, string $action = 'verify_email'): void
    {
        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret_key' => 'secret']);

        if ($this->google === []) {
            Http::fake(['www.google.com/recaptcha/*' => fn () => Http::response($this->google)]);
        }

        $this->google = ['success' => true, 'score' => $score, 'action' => $action];
    }

    public function test_the_email_links_to_the_angular_page_with_a_signed_relative_url(): void
    {
        Notification::fake();
        $owner = $this->registerOwner();

        Notification::assertSentTo($owner, VerifyCompanyEmail::class, function (VerifyCompanyEmail $notification) use ($owner) {
            $url = $notification->toMail($owner)->actionUrl;

            return str_starts_with($url, config('app.frontend_url')."/verificar-cuenta/{$owner->id}/")
                && str_contains($url, 'signature=');
        });
    }

    public function test_pending_link_shows_the_confirm_button(): void
    {
        Notification::fake();
        $owner = $this->registerOwner();

        $this->getJson($this->link($owner))
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('email', 'a**@luna.test');
    }

    public function test_confirming_requires_a_human_according_to_recaptcha(): void
    {
        Notification::fake();
        $owner = $this->registerOwner();

        $this->fakeRecaptcha(score: 0.2);
        $this->postJson($this->link($owner), ['recaptcha_token' => 'bot'])->assertUnprocessable()->assertJsonValidationErrors('recaptcha_token');
        $this->postJson($this->link($owner))->assertUnprocessable()->assertJsonValidationErrors('recaptcha_token');
        $this->assertNull($owner->refresh()->email_verified_at);

        $this->fakeRecaptcha(score: 0.9);
        $this->postJson($this->link($owner), ['recaptcha_token' => 'humano'])->assertOk()->assertJsonPath('status', 'verified');
        $this->assertNotNull($owner->refresh()->email_verified_at);
        $this->assertSame(CompanyStatus::Onboarding, $owner->company->status);
    }

    public function test_already_verified_account_is_sent_to_the_login(): void
    {
        Notification::fake();
        $owner = $this->registerOwner();
        $this->postJson($this->link($owner))->assertJsonPath('status', 'verified');

        $this->getJson($this->link($owner))->assertOk()->assertJsonPath('status', 'already_verified');
        $this->postJson($this->link($owner))->assertOk()->assertJsonPath('status', 'already_verified');
    }

    public function test_expired_link_sends_a_new_one(): void
    {
        Notification::fake();
        $owner = $this->registerOwner();
        $expired = $this->link($owner, minutes: 1);

        $this->travel(2)->minutes();

        $this->getJson($expired)->assertOk()->assertJsonPath('status', 'expired');
        $this->postJson($expired)->assertOk()->assertJsonPath('status', 'expired');
        $this->assertNull($owner->refresh()->email_verified_at);

        // Uno del registro y uno nuevo (el segundo intento no reenvía de inmediato)
        Notification::assertSentToTimes($owner, VerifyCompanyEmail::class, 2);
    }

    public function test_register_and_login_are_protected_by_recaptcha(): void
    {
        Notification::fake();
        $this->fakeRecaptcha(score: 0.1, action: 'register');

        $this->postJson('/api/auth/register', [
            'company_name' => 'Bot SA', 'name' => 'Bot', 'email' => 'bot@bot.test',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'accept_terms' => true,
            'recaptcha_token' => 'x',
        ])->assertUnprocessable()->assertJsonValidationErrors('recaptcha_token');

        $owner = $this->ownerOf($this->createCompany());

        // Acción equivocada: el token de otro formulario no sirve
        $this->fromPanel()->postJson('/api/auth/login', [
            'email' => $owner->email, 'password' => 'password', 'client' => 'web', 'recaptcha_token' => 'x',
        ])->assertUnprocessable()->assertJsonValidationErrors('recaptcha_token');

        $this->fakeRecaptcha(score: 0.9, action: 'login');
        $this->fromPanel()->postJson('/api/auth/login', [
            'email' => $owner->email, 'password' => 'password', 'client' => 'web', 'recaptcha_token' => 'ok',
        ])->assertOk();
    }

    public function test_public_config_exposes_only_the_site_key(): void
    {
        config(['services.recaptcha.site_key' => 'site-key', 'services.recaptcha.secret_key' => 'secret-key']);

        $this->getJson('/api/config')
            ->assertOk()
            ->assertJsonPath('recaptcha_site_key', 'site-key')
            ->assertDontSee('secret-key');
    }

    public function test_registration_no_longer_asks_for_a_plan(): void
    {
        Notification::fake();

        $this->registerOwner(['plan' => 'premium']);

        $this->assertSame('free', Company::query()->latest('id')->first()->plan->slug);
    }

    public function test_unverified_accounts_are_deleted_after_30_days(): void
    {
        Notification::fake();
        $owner = $this->registerOwner();
        $verified = $this->createCompany();

        $this->travel(29)->days();
        $this->artisan('accounts:prune-unverified')->assertSuccessful();
        $this->assertNotNull(User::query()->find($owner->id));

        $this->travel(2)->days();
        $this->artisan('accounts:prune-unverified')->assertSuccessful();
        $this->assertNull(User::query()->find($owner->id));
        $this->assertNull(Company::query()->find($owner->company_id));
        $this->assertNotNull(Company::query()->find($verified->id));
    }
}
