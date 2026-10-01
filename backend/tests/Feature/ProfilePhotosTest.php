<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Foto de la cuenta (navbar), logotipo de la empresa (marca del sidebar) y
 * foto del empleado (ficha y credencial). En la base solo vive la ruta
 * relativa; los archivos se guardan en el disco privado, separados por empresa.
 */
class ProfilePhotosTest extends TestCase
{
    private Company $company;

    private Employee $employee;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->company = $this->createCompany('basico', ['employees_can_use_web' => true]);
        $this->employee = $this->createEmployee($this->company);
        $this->admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin, Role::Employee]);
    }

    public function test_owner_uploads_logo_and_every_user_can_see_it(): void
    {
        $this->as($this->ownerOf($this->company))
            ->postJson('/api/company/logo', ['photo' => UploadedFile::fake()->image('logo.png')])
            ->assertOk();

        $path = $this->company->refresh()->logo_path;
        $url = '/api/company/logo?v='.basename($path);
        $this->assertStringStartsWith("logos/{$this->company->id}/", $path);

        // El logo no es privado de una persona: cualquier usuario lo ve
        $employee = $this->createUserFor($this->company, $this->employee);
        $me = $this->as($employee)->getJson('/api/me')->assertOk()->json();

        $this->assertSame($url, $me['company']['logo_url']);

        $this->as($employee)->getJson('/api/company/logo')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_only_the_owner_can_change_the_logo(): void
    {
        $photo = ['photo' => UploadedFile::fake()->image('logo.png')];

        $this->as($this->admin)->postJson('/api/company/logo', $photo)->assertForbidden();

        $employee = $this->createUserFor($this->company, $this->employee);
        $this->as($employee)->postJson('/api/company/logo', $photo)->assertForbidden();
        $this->as($employee)->deleteJson('/api/company/logo')->assertForbidden();
    }

    public function test_owner_can_remove_the_logo(): void
    {
        $this->as($this->ownerOf($this->company))
            ->postJson('/api/company/logo', ['photo' => UploadedFile::fake()->image('logo.png')])
            ->assertOk();

        $path = $this->company->refresh()->logo_path;
        Storage::disk('local')->assertExists($path);

        $this->as($this->ownerOf($this->company))
            ->deleteJson('/api/company/logo')
            ->assertOk()
            ->assertJsonPath('logo_url', null);

        $this->assertNull($this->company->refresh()->logo_path);
        Storage::disk('local')->assertMissing($path);
        $this->as($this->ownerOf($this->company))->get('/api/company/logo')->assertNotFound();
    }

    public function test_user_can_upload_replace_and_remove_their_avatar(): void
    {
        $user = $this->createUserFor($this->company, $this->employee);

        $response = $this->as($user)
            ->postJson('/api/me/avatar', ['photo' => UploadedFile::fake()->image('yo.png')])
            ->assertOk();

        $first = $user->refresh()->avatar_path;
        $this->assertStringStartsWith("avatars/{$this->company->id}/", $first);
        $this->assertSame('/api/me/avatar?v='.basename($first), $response->json('user.avatar_url'));
        Storage::disk('local')->assertExists($first);

        $this->as($user)->getJson('/api/me/avatar')->assertOk()->assertHeader('Content-Type', 'image/png');

        // Al subir otra, la anterior se borra: nunca quedan archivos huérfanos
        $this->as($user)
            ->postJson('/api/me/avatar', ['photo' => UploadedFile::fake()->image('otra.jpg')])
            ->assertOk();

        $second = $user->refresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);

        $this->as($user)->deleteJson('/api/me/avatar')->assertOk()->assertJsonPath('user.avatar_url', null);

        $this->assertNull($user->refresh()->avatar_path);
        Storage::disk('local')->assertMissing($second);
        $this->as($user)->getJson('/api/me/avatar')->assertNotFound();
    }

    public function test_avatar_rejects_files_that_are_not_small_images(): void
    {
        $user = $this->createUserFor($this->company, $this->employee);

        $this->as($user)
            ->postJson('/api/me/avatar', ['photo' => UploadedFile::fake()->create('notas.txt', 10, 'text/plain')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');

        $this->as($user)
            ->postJson('/api/me/avatar', ['photo' => UploadedFile::fake()->create('grande.jpg', 3000, 'image/jpeg')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');

        $this->as($user)->postJson('/api/me/avatar')->assertUnprocessable();

        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_employee_photo_appears_in_their_detail_and_is_served_to_the_team(): void
    {
        $key = $this->employee->getRouteKey();

        $response = $this->as($this->admin)
            ->postJson("/api/employees/{$key}/photo", ['photo' => UploadedFile::fake()->image('empleado.png')])
            ->assertOk();

        $path = $this->employee->refresh()->photo_path;
        $this->assertStringStartsWith("photos/{$this->company->id}/", $path);
        $this->assertPhotoUrl($response->json('photo_url'), $path);
        Storage::disk('local')->assertExists($path);

        $detail = $this->as($this->ownerOf($this->company))
            ->getJson("/api/employees/{$key}")
            ->assertOk()
            ->json();
        $this->assertPhotoUrl($detail['photo_url'], $path);

        $this->as($this->ownerOf($this->company))
            ->getJson("/api/employees/{$key}/photo")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        // Un empleado plano no tiene employees.view
        $this->as($this->createUserFor($this->company, $this->createEmployee($this->company)))
            ->getJson("/api/employees/{$key}/photo")
            ->assertForbidden();

        $this->as($this->admin)
            ->deleteJson("/api/employees/{$key}/photo")
            ->assertOk()
            ->assertJsonPath('photo_url', null);

        $this->assertNull($this->employee->refresh()->photo_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_only_whoever_manages_employees_can_change_a_photo(): void
    {
        $photo = ['photo' => UploadedFile::fake()->image('empleado.png')];
        $key = $this->employee->getRouteKey();

        // El gerente puede ver empleados (employees.view), pero no editarlos
        $manager = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Manager, Role::Employee]);
        $this->as($manager)->postJson("/api/employees/{$key}/photo", $photo)->assertForbidden();

        $plain = $this->createUserFor($this->company, $this->createEmployee($this->company));
        $this->as($plain)->postJson("/api/employees/{$key}/photo", $photo)->assertForbidden();

        $this->assertNull($this->employee->refresh()->photo_path);
    }

    public function test_another_company_cannot_read_a_photo(): void
    {
        $this->as($this->admin)
            ->postJson("/api/employees/{$this->employee->getRouteKey()}/photo", ['photo' => UploadedFile::fake()->image('empleado.png')])
            ->assertOk();

        $path = $this->employee->refresh()->photo_path;
        $other = $this->createCompany();
        $otherEmployee = $this->createEmployee($other);

        $this->as($this->ownerOf($other))
            ->getJson("/api/employees/{$otherEmployee->getRouteKey()}/photo")
            ->assertNotFound();

        Storage::disk('local')->assertExists($path);
    }

    public function test_serving_routes_need_a_session(): void
    {
        $this->getJson('/api/me/avatar')->assertUnauthorized();
        $this->getJson('/api/company/logo')->assertUnauthorized();
        $this->getJson('/api/employees/'.$this->employee->getRouteKey().'/photo')->assertUnauthorized();
    }

    public function test_photo_changes_are_logged_without_the_storage_path(): void
    {
        $this->as($this->admin)
            ->postJson("/api/employees/{$this->employee->getRouteKey()}/photo", ['photo' => UploadedFile::fake()->image('empleado.png')])
            ->assertOk();

        $log = ActivityLog::query()->where('description', 'like', 'Cambió la foto%')->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString('photos/', (string) $log->changes);
        $this->assertStringNotContainsString($this->employee->refresh()->photo_path, (string) $log->description);
    }

    /**
     * El token cifrado del empleado cambia en cada llamada (IV aleatorio), así
     * que solo se puede comparar la forma de la URL, no el token completo.
     */
    private function assertPhotoUrl(?string $actual, string $path): void
    {
        $this->assertMatchesRegularExpression(
            '#^/api/employees/[^/]+/photo\?v='.preg_quote(basename($path), '#').'$#',
            (string) $actual,
        );
    }
}
