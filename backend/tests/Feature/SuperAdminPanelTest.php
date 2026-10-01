<?php

namespace Tests\Feature;

use App\Filament\Pages\RolesAndPermissions;
use App\Filament\Widgets\DatabaseHealth;
use App\Filament\Widgets\PlatformOverview;
use App\Filament\Widgets\RatingsModeration;
use App\Filament\Widgets\SystemHealth;
use App\Models\SuperAdmin;
use App\Support\PlatformMetrics;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    public function test_guests_are_sent_to_the_super_admin_login(): void
    {
        $this->get('/intern/web/services/1/companies')->assertRedirect('/intern/web/services/1/login');
    }

    public function test_company_users_cannot_enter_the_super_admin_panel(): void
    {
        $company = $this->createCompany();

        $this->actingAs($this->ownerOf($company), 'web')
            ->get('/intern/web/services/1/companies')
            ->assertRedirect('/intern/web/services/1/login');
    }

    public function test_super_admin_sees_companies_plans_and_settings(): void
    {
        $company = $this->createCompany('plus', ['name' => 'Transportes del Norte']);
        $admin = SuperAdmin::query()->firstOrFail();

        $this->actingAs($admin, 'super_admin')->get('/intern/web/services/1/companies')
            ->assertOk()
            ->assertSee('Transportes del Norte');

        $this->actingAs($admin, 'super_admin')->get("/intern/web/services/1/companies/{$company->id}")
            ->assertOk()
            ->assertSee('asist_test_pool_plus');

        $this->actingAs($admin, 'super_admin')->get('/intern/web/services/1/plans')->assertOk()->assertSee('Premium');
        $this->actingAs($admin, 'super_admin')->get('/intern/web/services/1/platform-settings')->assertOk();
        $this->actingAs($admin, 'super_admin')->get('/intern/web/services/1/super-admins')->assertOk();
        $this->actingAs($admin, 'super_admin')->get('/intern/web/services/1')->assertOk();
    }

    public function test_dashboard_uses_the_previous_system_layout(): void
    {
        $paying = $this->createCompany('plus', ['name' => 'Transportes del Norte']);
        $paying->update(['status' => 'active', 'extra_offices' => 1]);
        $this->createEmployee($paying);
        $this->createCompany('premium'); // en prueba: no cuenta como ingreso
        $pastDue = $this->createCompany('basico');
        $pastDue->update(['status' => 'past_due']);

        $this->actingAs(SuperAdmin::query()->firstOrFail(), 'super_admin');

        Livewire::test(PlatformOverview::class)
            // Tarjetas con icono
            ->assertSee(['Empresas', 'Usuarios', 'Empleados', 'Oficinas', 'Áreas'])
            ->assertSee('bi-building', false)
            // Tarjetas de suscripción
            ->assertSee(['Suscripción activa', 'En prueba', 'Por vencer', 'Pago vencido', 'Sin plan de pago'])
            // Gráficas, planes y detalle por empresa
            ->assertSee('Ingresos mensuales estimados')
            ->assertSee('Crecimiento del sistema')
            ->assertSee('Distribución de planes')
            ->assertSee('Detalle por empresa')
            ->assertSee('Transportes del Norte')
            ->assertSee('Pago vencido');

        Livewire::test(DatabaseHealth::class)
            ->assertSee('asist_test_central')
            ->assertSee('asist_test_pool_plus')
            ->assertSee('Dedicada');

        Livewire::test(SystemHealth::class)->assertSee('PHP '.PHP_VERSION);
        Livewire::test(RatingsModeration::class)->assertSee('Opiniones de los clientes');
    }

    public function test_revenue_and_subscription_buckets(): void
    {
        $paying = $this->createCompany('plus');
        $paying->update(['status' => 'active', 'extra_offices' => 1]);
        $this->createCompany('premium');
        $pastDue = $this->createCompany('plus');
        $pastDue->update(['status' => 'past_due']);

        $metrics = app(PlatformMetrics::class);

        // Plus 1299 + oficina extra 149, más el Plus con pago vencido (1299)
        $this->assertSame(2747.0, $metrics->monthlyRevenue());
        $this->assertSame(1299.0, $metrics->revenueAtRisk());
        $this->assertSame(
            ['active' => 1, 'trial' => 1, 'ending' => 0, 'past_due' => 1, 'no_plan' => 0],
            $metrics->subscriptionBuckets(),
        );
    }

    public function test_the_panel_lives_at_its_previous_route(): void
    {
        $this->get('/superadmin')->assertNotFound();
        $this->get('/intern/web/services/1/login')->assertOk();
    }

    public function test_super_admin_sees_roles_and_permissions(): void
    {
        $company = $this->createCompany('basico', ['name' => 'Ferretería Sol']);
        $admin = SuperAdmin::query()->firstOrFail();

        $this->actingAs($admin, 'super_admin')->get('/intern/web/services/1/roles-and-permissions')
            ->assertOk()
            ->assertSee('Permisos de cada rol')
            ->assertSee('Ver la bitácora de actividad')
            ->assertSee('audit.view')
            ->assertSee('Gerente');

        $this->actingAs($admin, 'super_admin');
        Livewire::test(RolesAndPermissions::class)
            ->set('companyId', $company->id)
            ->assertSee('Crear y editar empleados')
            ->assertSee('El dueño siempre tiene todos los permisos.');
    }
}
