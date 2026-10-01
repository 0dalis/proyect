<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\EmployeeAppAccess;
use App\Notifications\ResetPasswordLink;
use App\Notifications\TrialEnding;
use App\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Olvidé mi contraseña, avisos de fin de prueba y plantilla de correo de marca.
 */
class PasswordResetAndEmailsTest extends TestCase
{
    /** Token del enlace que llegó al correo. */
    private function resetToken(User $user): string
    {
        $token = null;

        Notification::assertSentTo($user, ResetPasswordLink::class, function (ResetPasswordLink $notification) use ($user, &$token) {
            parse_str(parse_url($notification->toMail($user)->actionUrl, PHP_URL_QUERY), $query);
            $token = $query['token'];

            return str_starts_with($notification->toMail($user)->actionUrl, config('app.frontend_url').'/restablecer-contrasena?')
                && $query['email'] === $user->email;
        });

        return $token;
    }

    public function test_forgot_password_does_not_reveal_whether_the_email_exists(): void
    {
        Notification::fake();
        $owner = $this->ownerOf($this->createCompany());

        $known = $this->postJson('/api/auth/password/forgot', ['email' => $owner->email])->assertOk()->json('message');
        $unknown = $this->postJson('/api/auth/password/forgot', ['email' => 'nadie@ninguna.test'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTo($owner, ResetPasswordLink::class);
        Notification::assertSentTimes(ResetPasswordLink::class, 1);
    }

    public function test_the_link_sets_a_new_password_and_closes_every_session(): void
    {
        Notification::fake();
        $owner = $this->ownerOf($this->createCompany());
        $owner->forceFill(['must_change_password' => true])->save();
        $owner->createToken('app', ['app']);

        $this->postJson('/api/auth/password/forgot', ['email' => $owner->email]);
        $token = $this->resetToken($owner);

        $this->postJson('/api/auth/password/reset', [
            'token' => 'otro', 'email' => $owner->email, 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/auth/password/reset', [
            'token' => $token, 'email' => $owner->email, 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
        ])->assertOk();

        $owner->refresh();
        $this->assertTrue(Hash::check('nueva1234', $owner->password));
        $this->assertFalse($owner->must_change_password);
        $this->assertSame(0, $owner->tokens()->count());

        // El enlace solo sirve una vez
        $this->postJson('/api/auth/password/reset', [
            'token' => $token, 'email' => $owner->email, 'password' => 'otra12345', 'password_confirmation' => 'otra12345',
        ])->assertUnprocessable();
    }

    public function test_the_link_expires_after_an_hour(): void
    {
        Notification::fake();
        $owner = $this->ownerOf($this->createCompany());
        $this->postJson('/api/auth/password/forgot', ['email' => $owner->email]);
        $token = $this->resetToken($owner);

        $this->travel(61)->minutes();

        $this->postJson('/api/auth/password/reset', [
            'token' => $token, 'email' => $owner->email, 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
        ])->assertUnprocessable();
        $this->assertSame(1, DB::connection('central')->table('password_reset_tokens')->count());
    }

    public function test_the_owner_is_reminded_three_days_before_the_first_charge_and_on_the_day(): void
    {
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-10-01 10:00', 'America/Mexico_City'));
        $company = $this->createCompany('plus');
        // Prueba hasta el 15: el cobro es el 14
        $company->forceFill(['status' => CompanyStatus::Trial, 'trial_ends_at' => Carbon::parse('2026-10-15 10:00', 'America/Mexico_City'), 'billing_interval' => 'month'])->save();
        $owner = $this->ownerOf($company);

        $this->travelTo(Carbon::parse('2026-10-10 10:00', 'America/Mexico_City'));
        $this->artisan('billing:trial-reminders');
        Notification::assertNothingSent();

        $this->travelTo(Carbon::parse('2026-10-11 10:00', 'America/Mexico_City'));
        $this->artisan('billing:trial-reminders');
        $this->artisan('billing:trial-reminders');
        Notification::assertSentToTimes($owner, TrialEnding::class, 1);

        $this->travelTo(Carbon::parse('2026-10-14 09:00', 'America/Mexico_City'));
        $this->artisan('billing:trial-reminders');
        Notification::assertSentToTimes($owner, TrialEnding::class, 2);

        Notification::assertSentTo($owner, TrialEnding::class, function (TrialEnding $notification) use ($owner) {
            $mail = $notification->toMail($owner);

            return str_contains($mail->subject, 'termina en 3 días') || str_contains($mail->subject, 'Hoy se cobra');
        });
    }

    public function test_free_companies_get_no_trial_reminders(): void
    {
        Notification::fake();
        $company = $this->createCompany('free');
        $company->forceFill(['status' => CompanyStatus::Trial, 'trial_ends_at' => now()->addDays(2), 'plan_id' => Plan::free()->id])->save();

        $this->artisan('billing:trial-reminders');

        Notification::assertNothingSent();
    }

    public function test_emails_use_the_asistcontrol_template_in_spanish(): void
    {
        $company = $this->createCompany();
        $owner = $this->ownerOf($company);

        $html = (string) (new ResetPasswordLink('abc'))->toMail($owner)->render();

        $this->assertStringContainsString('JALY SYSTEMS', $html);
        $this->assertStringContainsString('Si el botón', $html);
        $this->assertStringContainsString('#3b82f6', $html);
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString('Laravel', $html);

        app(TenantManager::class)->connect($company);
        $credentials = (string) (new EmployeeAppAccess($company, 'Temp12345'))->toMail($owner)->render();
        $this->assertStringContainsString($company->code, $credentials);
    }
}
