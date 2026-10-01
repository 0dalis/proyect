<?php

namespace Tests\Feature;

use App\Actions\RegisterAttendance;
use App\Actions\ResendCompanyExport;
use App\Enums\AttendanceChannel;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyDeletion;
use App\Models\Employee;
use App\Models\Kiosk;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Notifications\CompanyDataDeleted;
use App\Notifications\CompanyDeletionRequested;
use App\Tenancy\TenantManager;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * "Eliminar perfil de empresa de AsistControl": bloqueo inmediato, enlace de
 * exportación de 48 horas y borrado total a los 30 días.
 */
class CompanyDeletionTest extends TestCase
{
    private Company $company;

    private User $owner;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->company = $this->createCompany('basico', ['name' => 'Tortillería Maya']);
        $this->owner = $this->ownerOf($this->company);
        $this->employee = $this->createEmployee($this->company, ['first_name' => 'Rosa']);
        $this->createUserFor($this->company, $this->employee)->createToken('app', ['app']);
    }

    private function requestDeletion(array $overrides = []): TestResponse
    {
        return $this->as($this->owner)->postJson('/api/company/deletion', [
            'password' => 'password',
            'reason' => 'Cerramos la sucursal y ya no necesitamos el sistema.',
            'confirmation' => 'eliminar datos de Tortillería Maya',
            ...$overrides,
        ]);
    }

    /** Token del enlace que llegó al correo del dueño. */
    private function exportToken(): string
    {
        $token = null;

        Notification::assertSentOnDemand(CompanyDeletionRequested::class, function ($notification, $channels, AnonymousNotifiable $notifiable) use (&$token) {
            $url = $notification->toMail($notifiable)->actionUrl;
            $token = basename(parse_url($url, PHP_URL_PATH));

            return array_key_exists($this->owner->email, $notifiable->routes['mail']);
        });

        return $token;
    }

    public function test_it_asks_for_password_reason_and_the_exact_phrase(): void
    {
        $this->requestDeletion(['password' => 'otra'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->requestDeletion(['reason' => 'porque sí'])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->requestDeletion(['confirmation' => 'eliminar datos'])->assertUnprocessable()->assertJsonValidationErrors('confirmation');

        $this->assertNotSame(CompanyStatus::DeletionPending, $this->company->refresh()->status);
        $this->assertSame(0, CompanyDeletion::query()->count());
    }

    public function test_access_is_closed_for_everyone_immediately(): void
    {
        $this->requestDeletion()->assertStatus(202);

        $this->company->refresh();
        $this->assertSame(CompanyStatus::DeletionPending, $this->company->status);
        $this->assertTrue($this->company->deletion_scheduled_for->isSameDay(now()->addDays(30)));

        $this->as($this->owner->fresh())->getJson('/api/me')->assertForbidden()->assertJsonPath('code', 'company_deleted');
        $this->fromPanel()->postJson('/api/auth/login', ['email' => $this->owner->email, 'password' => 'password', 'client' => 'web'])
            ->assertForbidden()
            ->assertJsonPath('code', 'company_deleted');
        $this->assertSame(0, DB::connection('central')->table('personal_access_tokens')->count());

        app(TenantManager::class)->connect($this->company);
        $this->assertSame(0, Kiosk::query()->where('is_active', true)->count());
    }

    public function test_the_deletion_is_recorded_without_losing_the_business_facts(): void
    {
        $this->travel(90)->days();
        $this->requestDeletion();

        $deletion = CompanyDeletion::query()->sole();
        $this->assertSame('Tortillería Maya', $deletion->company_name);
        $this->assertSame('Básico', $deletion->last_plan);
        $this->assertSame(90, $deletion->days_in_system);
        $this->assertSame('Cerramos la sucursal y ya no necesitamos el sistema.', $deletion->reason);
        $this->assertSame(30, $deletion->daysLeft());
    }

    public function test_the_owner_downloads_the_export_for_48_hours(): void
    {
        app(TenantManager::class)->connect($this->company);
        $this->travelTo(now()->setTime(10, 0));
        app(RegisterAttendance::class)->handle($this->employee->refresh(), AttendanceChannel::Manual);

        $this->requestDeletion();
        $token = $this->exportToken();

        $response = $this->get("/api/exports/{$token}")->assertOk();
        $this->assertStringContainsString('asistcontrol-tortilleria-maya-datos.zip', $response->headers->get('content-disposition'));

        $zip = new ZipArchive;
        $zip->open($response->getFile()->getPathname());
        $this->assertNotFalse($zip->locateName('empleados.csv'));
        $this->assertNotFalse($zip->locateName('asistencia.csv'));
        $this->assertNotFalse($zip->locateName('solicitudes.csv'));
        $this->assertStringContainsString('Rosa', $zip->getFromName('empleados.csv'));
        $this->assertSame(2, substr_count($zip->getFromName('asistencia.csv'), "\n"));
        $zip->close();

        $this->travel(49)->hours();
        $this->get("/api/exports/{$token}")->assertRedirect(config('app.frontend_url').'/login?export=expired');
    }

    public function test_support_can_send_a_new_link_while_the_30_days_run(): void
    {
        $this->requestDeletion();
        $oldToken = $this->exportToken();
        $this->travel(3)->days();

        $deletion = CompanyDeletion::query()->sole();
        app(ResendCompanyExport::class)->handle($deletion);

        Notification::assertSentOnDemandTimes(CompanyDeletionRequested::class, 2);
        $this->get("/api/exports/{$oldToken}")->assertRedirect();
        $this->assertTrue($deletion->refresh()->exportLinkIsValid());
        $this->assertSame(2, $deletion->export_links_sent);
    }

    public function test_after_30_days_everything_is_deleted_but_other_companies_in_the_pool(): void
    {
        $neighbor = $this->createCompany('basico');
        $neighborEmployee = $this->createEmployee($neighbor);
        Storage::disk('local')->put("attendance/{$this->company->id}/foto.jpg", 'x');

        $this->requestDeletion();
        $deletion = CompanyDeletion::query()->sole();
        $exportPath = $deletion->export_path;

        $this->travel(29)->days();
        $this->artisan('companies:purge-deleted')->assertSuccessful();
        $this->assertNotNull(Company::query()->find($this->company->id));

        $this->travel(2)->days();
        $this->artisan('companies:purge-deleted')->assertSuccessful();

        $this->assertNull(Company::query()->find($this->company->id));
        $this->assertSame(0, User::query()->where('company_id', $this->company->id)->count());
        $this->assertSame(0, DB::connection('central')->table('roles')->where('company_id', $this->company->id)->count());

        $pool = DB::connection('tenant');
        app(TenantManager::class)->connect($neighbor);
        foreach (['employees', 'offices', 'shifts', 'areas', 'activity_logs', 'attendance_records'] as $table) {
            $this->assertSame(0, $pool->table($table)->where('company_id', $this->company->id)->count(), $table);
        }
        $this->assertTrue(Employee::query()->whereKey($neighborEmployee->id)->exists());

        Storage::disk('local')->assertMissing("attendance/{$this->company->id}/foto.jpg");
        Storage::disk('local')->assertMissing($exportPath);

        $deletion->refresh();
        $this->assertNotNull($deletion->purged_at);
        $this->assertNull($deletion->owner_email);
        $this->assertSame('Tortillería Maya', $deletion->company_name);

        Notification::assertSentOnDemand(CompanyDataDeleted::class);
        $this->expectException(RuntimeException::class);
        app(ResendCompanyExport::class)->handle($deletion);
    }

    public function test_dedicated_database_is_dropped(): void
    {
        $premium = $this->createCompany('premium', ['name' => 'Corporativo Norte']);
        $owner = $this->ownerOf($premium);
        $database = $premium->database;

        $this->as($owner)->postJson('/api/company/deletion', [
            'password' => 'password',
            'reason' => 'Nos cambiamos a otro proveedor de nómina.',
            'confirmation' => 'ELIMINAR DATOS DE  Corporativo Norte',
        ])->assertStatus(202);

        $this->travel(31)->days();
        $this->artisan('companies:purge-deleted')->assertSuccessful();

        $this->assertSame(0, DB::connection('central')->table('information_schema.schemata')->where('schema_name', $database)->count());
    }

    public function test_the_owner_rates_asistcontrol_once_with_the_unique_link(): void
    {
        $this->requestDeletion();
        $this->travel(31)->days();
        $this->artisan('companies:purge-deleted');

        $token = CompanyDeletion::query()->sole()->feedback_token;

        $this->getJson("/api/feedback/{$token}")->assertOk()->assertJsonPath('submitted', false);
        $this->postJson("/api/feedback/{$token}", ['rating' => 4, 'comment' => 'Buen sistema'])->assertOk();
        $this->postJson("/api/feedback/{$token}", ['rating' => 1])->assertStatus(409);
        $this->getJson('/api/feedback/otro-token')->assertNotFound();

        $this->assertSame(4, CompanyDeletion::query()->sole()->feedback_rating);
    }

    public function test_super_admin_sees_deletions_in_progress_and_finished(): void
    {
        $this->requestDeletion();
        $admin = SuperAdmin::query()->firstOrFail();

        $this->actingAs($admin, 'super_admin')->get('/intern/web/services/1/company-deletions')
            ->assertOk()
            ->assertSee('Tortillería Maya')
            ->assertSee('30 días')
            ->assertSee('Eliminadas');
    }
}
