<?php

namespace Tests\Feature;

use App\Filament\Pages\PlatformSettings;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Http\Middleware\RequireSuperAdminTwoFactor;
use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\SuperAdminAuditLog;
use App\Models\SystemSetting;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bitácora del Super Admin y verificación en dos pasos (opcional u
 * obligatoria desde Ajustes).
 */
class SuperAdminSecurityTest extends TestCase
{
    private const PANEL = '/intern/web/services/1';

    public function test_company_actions_are_written_to_the_audit_log(): void
    {
        $company = $this->createCompany('plus', ['name' => 'Transportes del Norte']);
        $this->actingAs(SuperAdmin::query()->firstOrFail(), 'super_admin');

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('extendTrial')->table($company), ['days' => 5])
            ->callAction(TestAction::make('suspend')->table($company))
            ->callAction(TestAction::make('reactivate')->table($company));

        $actions = SuperAdminAuditLog::query()->where('company_id', $company->id)->pluck('action')->all();
        $this->assertSame(['company.trial_extended', 'company.suspended', 'company.reactivated'], $actions);

        Livewire::test(ListAuditLogs::class)->assertOk()->assertSee('Suspendió Transportes del Norte');
        $this->get(self::PANEL.'/audit-log')->assertOk();
    }

    public function test_panel_login_and_plan_changes_are_logged(): void
    {
        $admin = SuperAdmin::query()->firstOrFail();
        $this->actingAs($admin, 'super_admin');
        event(new Login('super_admin', $admin, false));

        Plan::query()->where('slug', 'plus')->firstOrFail()->update(['monthly_price' => 1399]);

        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'login', 'super_admin_id' => $admin->id], 'central');
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'plan.updated'], 'central');
    }

    public function test_two_factor_is_optional_by_default(): void
    {
        $admin = SuperAdmin::query()->firstOrFail();

        $this->assertFalse(RequireSuperAdminTwoFactor::isRequired());
        $this->actingAs($admin, 'super_admin')->get(self::PANEL.'/companies')->assertOk();
        $this->actingAs($admin, 'super_admin')->get(self::PANEL.'/profile')->assertOk();
    }

    public function test_when_required_admins_without_two_factor_only_reach_their_profile(): void
    {
        $admin = SuperAdmin::query()->firstOrFail();
        SystemSetting::put(RequireSuperAdminTwoFactor::SETTING, true);

        $this->actingAs($admin, 'super_admin')->get(self::PANEL.'/companies')
            ->assertRedirect(self::PANEL.'/profile');
        $this->actingAs($admin, 'super_admin')->get(self::PANEL.'/profile')->assertOk();

        $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $this->actingAs($admin->fresh(), 'super_admin')->get(self::PANEL.'/companies')->assertOk();
    }

    public function test_settings_make_two_factor_required_only_after_enabling_it_yourself(): void
    {
        $admin = SuperAdmin::query()->firstOrFail();
        $this->actingAs($admin, 'super_admin');

        Livewire::test(PlatformSettings::class)
            ->assertSee('Verificación en dos pasos')
            ->set('data.mfa_required', true)
            ->call('save');
        $this->assertFalse(RequireSuperAdminTwoFactor::isRequired());

        $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $this->actingAs($admin->fresh(), 'super_admin');

        Livewire::test(PlatformSettings::class)->set('data.mfa_required', true)->call('save');
        $this->assertTrue(RequireSuperAdminTwoFactor::isRequired());
        $this->assertDatabaseHas('super_admin_audit_logs', ['description' => 'Hizo obligatoria la verificación en dos pasos'], 'central');
    }

    public function test_two_factor_secret_and_recovery_codes_are_encrypted(): void
    {
        $admin = SuperAdmin::query()->firstOrFail();

        $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $admin->saveAppAuthenticationRecoveryCodes(['codigo-1', 'codigo-2']);

        $raw = DB::connection('central')->table('super_admins')->where('id', $admin->id)->value('app_authentication_secret');
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $raw);
        $this->assertSame('JBSWY3DPEHPK3PXP', $admin->fresh()->getAppAuthenticationSecret());
        $this->assertSame(['codigo-1', 'codigo-2'], $admin->fresh()->getAppAuthenticationRecoveryCodes());
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'mfa.enabled'], 'central');
    }
}
