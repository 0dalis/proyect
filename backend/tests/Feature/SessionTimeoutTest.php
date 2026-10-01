<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * La sesión web dura SESSION_LIFETIME minutos (120) sin actividad. Cada
 * petición la renueva; al vencer, la API responde 401 con session_expired.
 */
class SessionTimeoutTest extends TestCase
{
    public function test_web_session_lasts_two_hours_of_inactivity(): void
    {
        $this->assertSame(120, (int) config('session.lifetime'));
        $this->assertFalse((bool) config('session.expire_on_close'));
    }

    public function test_request_without_session_gets_session_expired_code(): void
    {
        $this->fromPanel()->getJson('/api/me')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.',
                'code' => 'session_expired',
            ]);
    }

    public function test_ping_keeps_the_session_alive_and_reports_the_idle_limit(): void
    {
        $owner = $this->ownerOf($this->createCompany());

        $this->as($owner)->getJson('/api/session/ping')
            ->assertOk()
            ->assertExactJson(['idle_minutes' => 120]);
    }

    public function test_ping_requires_a_session(): void
    {
        $this->fromPanel()->getJson('/api/session/ping')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_expired');
    }

    public function test_session_expires_after_two_hours_without_activity(): void
    {
        $owner = $this->ownerOf($this->createCompany());
        $cookie = config('session.cookie');

        $session = $this->fromPanel()
            ->postJson('/api/auth/login', ['email' => $owner->email, 'password' => 'password', 'client' => 'web'])
            ->assertOk()
            ->getCookie($cookie)
            ->getValue();

        // Actividad a los 100 minutos: renueva el plazo.
        $this->travel(100)->minutes();
        $this->freshRequest($cookie, $session)->getJson('/api/session/ping')->assertOk();

        // 110 minutos después de esa actividad sigue viva (210 desde el login).
        $this->travel(110)->minutes();
        $this->freshRequest($cookie, $session)->getJson('/api/me')->assertOk();

        // 121 minutos sin actividad: vencida.
        $this->travel(121)->minutes();
        $this->freshRequest($cookie, $session)->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_expired');
    }

    /** Petición nueva que solo trae la cookie, como el navegador. */
    private function freshRequest(string $cookie, string $session): static
    {
        // En pruebas la app (y el objeto de sesión) se reutiliza entre
        // peticiones; se limpia para que solo cuente lo guardado en el servidor.
        $this->app['auth']->forgetGuards();
        $this->app['session.store']->flush();

        return $this->fromPanel()->withCredentials()->withCookie($cookie, $session);
    }
}
