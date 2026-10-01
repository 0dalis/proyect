<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Cómo se protege la comunicación entre el panel (Angular) y la API.
 */
class SecurityTest extends TestCase
{
    public function test_protected_endpoints_require_authentication(): void
    {
        foreach (['/api/me', '/api/employees', '/api/attendance', '/api/activity', '/api/dashboard'] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    public function test_web_login_opens_a_session_instead_of_returning_a_token(): void
    {
        $owner = $this->ownerOf($this->createCompany());

        $this->fromPanel()
            ->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'web'])
            ->assertOk()
            ->assertJsonMissingPath('token');

        $this->assertAuthenticatedAs($owner, 'web');
        $this->assertSame(0, $owner->tokens()->count());
    }

    public function test_web_login_outside_the_panel_is_rejected(): void
    {
        $owner = $this->ownerOf($this->createCompany());

        // Postman, curl u otro sistema: sin el origen del panel no hay sesión
        $this->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'web'])
            ->assertForbidden()
            ->assertJsonPath('code', 'web_session_required');

        $this->withHeaders(['Origin' => 'https://sitio-malicioso.test'])
            ->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'web'])
            ->assertForbidden();
    }

    public function test_the_mobile_app_still_gets_a_token_with_the_app_ability(): void
    {
        $owner = $this->ownerOf($this->createCompany());

        $token = $this->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'app', 'company_code' => $owner->company->code])
            ->assertOk()
            ->json('token');

        $this->assertNotEmpty($token);
        $this->assertSame(['app'], $owner->tokens()->first()->abilities);
    }

    public function test_cors_only_allows_the_panel_origin_with_credentials(): void
    {
        $preflight = fn (string $origin) => $this->call('OPTIONS', '/api/me', server: [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $allowed = $preflight('http://localhost:4200');
        $this->assertSame('http://localhost:4200', $allowed->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('true', $allowed->headers->get('Access-Control-Allow-Credentials'));

        $this->assertNull($preflight('https://sitio-malicioso.test')->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_the_csrf_cookie_endpoint_is_available_to_the_panel(): void
    {
        $this->fromPanel()->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN');
    }

    public function test_api_responses_carry_security_headers(): void
    {
        $response = $this->getJson('/api/plans')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("default-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_logout_ends_the_web_session(): void
    {
        $owner = $this->ownerOf($this->createCompany());
        $this->fromPanel()->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'web']);

        $this->fromPanel()->withHeader('X-Client', 'web')->postJson('/api/auth/logout')->assertOk();

        $this->assertGuest('web');
    }
}
