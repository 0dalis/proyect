<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use Tests\TestCase;

/**
 * Telescope solo lo abre un Super Admin, también en local.
 */
class TelescopeAccessTest extends TestCase
{
    protected function setUp(): void
    {
        // phpunit.xml lo apaga para el resto de las pruebas; sin él no hay rutas
        $_ENV['TELESCOPE_ENABLED'] = $_SERVER['TELESCOPE_ENABLED'] = 'true';

        parent::setUp();

        // En local Telescope abre a todos por defecto; aquí no debe
        $this->app->detectEnvironment(fn () => 'local');
    }

    protected function tearDown(): void
    {
        $_ENV['TELESCOPE_ENABLED'] = $_SERVER['TELESCOPE_ENABLED'] = 'false';

        parent::tearDown();
    }

    public function test_guest_is_sent_to_the_super_admin_login(): void
    {
        $this->get('/telescope')->assertRedirect(route('filament.superadmin.auth.login'));
    }

    public function test_company_owner_cannot_open_telescope(): void
    {
        $owner = $this->ownerOf($this->createCompany());

        $this->actingAs($owner, 'web')->get('/telescope')
            ->assertRedirect(route('filament.superadmin.auth.login'));

        $this->actingAs($owner, 'web')->getJson('/telescope/telescope-api/requests/'.fake()->uuid())
            ->assertForbidden();
    }

    public function test_super_admin_can_open_telescope(): void
    {
        $admin = SuperAdmin::query()->firstOrFail();

        $this->actingAs($admin, 'super_admin')->get('/telescope')->assertOk();
        $this->actingAs($admin, 'super_admin')->get('/telescope/requests')->assertOk();
    }
}
