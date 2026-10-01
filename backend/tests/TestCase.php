<?php

namespace Tests;

use App\Actions\ActivateCompany;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Area;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Plan;
use App\Models\Shift;
use App\Models\User;
use App\Tenancy\TenantManager;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Las pruebas crean bases reales (el aprovisionamiento usa CREATE DATABASE,
 * que no se puede envolver en una transacción), así que se limpian a mano.
 */
abstract class TestCase extends BaseTestCase
{
    private static bool $centralMigrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! str_starts_with(config('database.connections.central.database'), 'asist_test_')) {
            throw new RuntimeException('Las pruebas deben usar una base asist_test_*.');
        }

        $this->dropTenantDatabases();

        if (! self::$centralMigrated) {
            $this->artisan('migrate:fresh');
            self::$centralMigrated = true;
        } else {
            $this->truncateCentralTables();
        }

        app(TenantManager::class)->forget();
        $this->seedPlans();
    }

    protected function createCompany(string $planSlug = 'basico', array $attributes = []): Company
    {
        $company = Company::query()->create([
            'name' => $attributes['name'] ?? 'Empresa '.fake()->unique()->company(),
            'slug' => fake()->unique()->slug(3),
            'plan_id' => Plan::query()->where('slug', $planSlug)->value('id'),
            'status' => CompanyStatus::Pending,
        ]);

        $owner = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Dueño',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'is_owner' => true,
        ]);
        $owner->markEmailAsVerified();

        app(ActivateCompany::class)->handle($company->refresh());

        $company->refresh();
        $company->update(collect($attributes)->except('name')->all());

        return $company;
    }

    protected function createEmployee(Company $company, array $attributes = []): Employee
    {
        app(TenantManager::class)->connect($company);

        $employee = new Employee([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'office_id' => Office::query()->where('is_default', true)->value('id'),
            'shift_id' => Shift::query()->where('is_default', true)->value('id'),
            'area_id' => Area::query()->value('id'),
            'employment_type' => 'permanent',
            'work_mode' => 'onsite',
            ...$attributes,
        ]);
        $employee->employee_number = $attributes['employee_number'] ?? (string) fake()->unique()->numberBetween(10000, 99999);
        $employee->setPin($attributes['pin'] ?? '123456');
        $employee->save();
        $employee->issueBadge();

        return $employee;
    }

    /**
     * @param  list<Role>  $roles
     */
    protected function createUserFor(Company $company, Employee $employee, array $roles = [Role::Employee]): User
    {
        app(TenantManager::class)->connect($company);

        $user = User::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'name' => $employee->fullName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ]);
        $user->markEmailAsVerified();
        $user->assignRole(array_map(fn (Role $role) => $role->value, $roles));

        return $user->refresh();
    }

    protected function ownerOf(Company $company): User
    {
        return $company->owner()->firstOrFail();
    }

    /**
     * Petición autenticada como si viniera del panel web (o de la app).
     */
    protected function as(User $user, string $client = 'web'): static
    {
        app(TenantManager::class)->forget();

        return $this->actingAs($user, 'sanctum')->withHeader('X-Client', $client);
    }

    /**
     * Petición como la hace el navegador desde el panel (origen permitido),
     * para que Sanctum use sesión en lugar de token.
     */
    protected function fromPanel(): static
    {
        return $this->withHeaders(['Origin' => 'http://localhost:4200', 'Referer' => 'http://localhost:4200/login']);
    }

    private function seedPlans(): void
    {
        $this->seed(PlatformSeeder::class);
    }

    private function truncateCentralTables(): void
    {
        Schema::connection('central')->disableForeignKeyConstraints();

        foreach (DB::connection('central')->select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];

            if ($table !== 'migrations') {
                DB::connection('central')->table($table)->truncate();
            }
        }

        Schema::connection('central')->enableForeignKeyConstraints();
    }

    private function dropTenantDatabases(): void
    {
        $prefix = config('tenancy.database_prefix');
        $databases = DB::connection('central')->select('SHOW DATABASES');

        foreach ($databases as $row) {
            $name = array_values((array) $row)[0];

            if (str_starts_with($name, $prefix) && $name !== config('database.connections.central.database')) {
                DB::connection('central')->statement("DROP DATABASE `{$name}`");
            }
        }

        DB::purge('tenant');
    }
}
