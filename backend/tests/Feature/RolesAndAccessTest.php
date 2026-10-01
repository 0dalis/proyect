<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Area;
use App\Models\Company;
use App\Models\Office;
use App\Models\User;
use App\Support\EncryptedId;
use App\Tenancy\TenantManager;
use Tests\TestCase;

class RolesAndAccessTest extends TestCase
{
    private Company $company;

    private User $owner;

    private User $admin;

    private User $manager;

    private User $seller;

    private User $warehouseWorker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany();
        $this->owner = $this->ownerOf($this->company);

        app(TenantManager::class)->connect($this->company);
        $sales = Area::query()->create(['name' => 'Ventas']);
        $warehouse = Area::query()->create(['name' => 'Almacén']);

        $this->admin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin, Role::Employee]);
        $managerEmployee = $this->createEmployee($this->company, ['area_id' => $sales->id]);
        $sales->managers()->attach($managerEmployee->id);
        $this->manager = $this->createUserFor($this->company, $managerEmployee, [Role::Manager, Role::Employee]);
        // Con antigüedad: tiene días de vacaciones (art. 76 LFT)
        $this->seller = $this->createUserFor($this->company, $this->createEmployee($this->company, ['area_id' => $sales->id, 'hired_on' => '2020-01-06']));
        $this->warehouseWorker = $this->createUserFor($this->company, $this->createEmployee($this->company, ['area_id' => $warehouse->id]));
    }

    private function requestFrom(User $user, string $type): int
    {
        return $this->as($user, 'app')->postJson('/api/requests', [
            // De lunes a jueves: días laborables del turno
            'type' => $type, 'starts_on' => now()->next('Monday')->toDateString(), 'ends_on' => now()->next('Monday')->addDays(3)->toDateString(),
            'expected_time' => '10:00', 'reason' => 'Motivo',
        ])->assertCreated()->json('id');
    }

    public function test_only_the_owner_can_appoint_admins(): void
    {
        $this->as($this->admin)->putJson("/api/users/{$this->seller->id}/roles", ['roles' => ['admin']])
            ->assertUnprocessable();

        $this->as($this->owner)->putJson("/api/users/{$this->seller->id}/roles", ['roles' => ['admin']])
            ->assertOk()
            ->assertJsonFragment(['admin']);
    }

    public function test_admin_can_appoint_managers_but_cannot_touch_other_admins_or_the_owner(): void
    {
        $this->as($this->admin)->putJson("/api/users/{$this->seller->id}/roles", ['roles' => ['manager']])->assertOk();

        $otherAdmin = $this->createUserFor($this->company, $this->createEmployee($this->company), [Role::Admin]);
        $this->as($this->admin)->putJson("/api/users/{$otherAdmin->id}/roles", ['roles' => []])->assertForbidden();
        $this->as($this->admin)->patchJson("/api/users/{$this->owner->id}/access", ['blocked' => true])->assertForbidden();
    }

    public function test_manager_only_sees_employees_of_the_areas_they_manage(): void
    {
        $response = $this->as($this->manager)->getJson('/api/employees')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($this->seller->employee_id));
        $this->assertFalse($ids->contains($this->warehouseWorker->employee_id));

        $this->as($this->manager)->getJson('/api/employees/'.EncryptedId::encode($this->warehouseWorker->employee_id))->assertNotFound();
    }

    public function test_manager_approves_justifications_but_not_vacations(): void
    {
        $justification = $this->requestFrom($this->seller, 'justification');
        $vacation = $this->requestFrom($this->seller, 'vacation');

        $this->as($this->manager)->postJson("/api/requests/{$justification}/review", ['decision' => 'approved'])->assertOk();
        $this->as($this->manager)->postJson("/api/requests/{$vacation}/review", ['decision' => 'approved'])->assertForbidden();
        $this->as($this->admin)->postJson("/api/requests/{$vacation}/review", ['decision' => 'approved'])->assertOk();
    }

    public function test_manager_cannot_review_requests_outside_their_areas(): void
    {
        $request = $this->requestFrom($this->warehouseWorker, 'justification');

        $this->as($this->manager)->postJson("/api/requests/{$request}/review", ['decision' => 'approved'])->assertForbidden();
    }

    public function test_owner_can_remove_approval_permission_from_managers(): void
    {
        $this->as($this->owner)->putJson('/api/roles/manager/permissions', [
            'permissions' => ['employees.view', 'attendance.view', 'requests.view'],
        ])->assertOk();

        $request = $this->requestFrom($this->seller, 'justification');
        $this->as($this->manager)->postJson("/api/requests/{$request}/review", ['decision' => 'approved'])->assertForbidden();
        $this->as($this->manager)->getJson('/api/requests')->assertOk();
    }

    public function test_locked_permissions_cannot_be_granted_and_only_owner_edits_permissions(): void
    {
        $this->as($this->owner)->putJson('/api/roles/manager/permissions', ['permissions' => ['vacations.approve']])
            ->assertUnprocessable();

        $this->as($this->admin)->putJson('/api/roles/manager/permissions', ['permissions' => ['requests.view']])
            ->assertForbidden();

        $this->as($this->owner)->putJson('/api/roles/owner/permissions', ['permissions' => []])
            ->assertStatus(422);
    }

    public function test_blocked_user_loses_access_but_employee_record_remains(): void
    {
        $this->as($this->admin)->patchJson("/api/users/{$this->seller->id}/access", ['blocked' => true])->assertOk();

        $this->as($this->seller->refresh(), 'app')->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'user_blocked');

        $this->as($this->owner)->getJson('/api/employees/'.EncryptedId::encode($this->seller->employee_id))->assertOk();
    }

    public function test_owner_decides_whether_employees_can_use_the_web_panel(): void
    {
        $this->as($this->seller)->getJson('/api/me')->assertForbidden()->assertJsonPath('code', 'web_access_denied');
        $this->as($this->seller, 'app')->getJson('/api/me')->assertOk();
        $this->as($this->manager)->getJson('/api/me')->assertOk();

        $this->as($this->owner)->patchJson('/api/company/settings', ['employees_can_use_web' => true])->assertOk();
        $this->as($this->seller->refresh())->getJson('/api/me')->assertOk();

        $this->as($this->admin)->patchJson('/api/company/settings', ['employees_can_use_web' => false])->assertForbidden();
    }

    public function test_plain_employee_cannot_manage_the_company(): void
    {
        $this->as($this->seller, 'app')->getJson('/api/users')->assertForbidden();
        $this->as($this->seller, 'app')->postJson('/api/offices', ['name' => 'X', 'geofence_radius' => 50])->assertForbidden();
    }

    public function test_geofence_radius_must_be_between_10_and_100_meters(): void
    {
        foreach ([5, 101] as $radius) {
            $this->as($this->owner)->putJson('/api/offices/'.$this->defaultOfficeId(), ['geofence_radius' => $radius])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('geofence_radius');
        }

        $this->as($this->owner)->putJson('/api/offices/'.$this->defaultOfficeId(), [
            'geofence_radius' => 10, 'latitude' => 19.43, 'longitude' => -99.15,
        ])->assertOk()->assertJsonPath('geofence_radius', 10);
    }

    public function test_announcement_to_an_area_reaches_only_that_area(): void
    {
        app(TenantManager::class)->connect($this->company);
        $warehouseId = Area::query()->where('name', 'Almacén')->value('id');

        $this->as($this->admin)->postJson('/api/announcements', [
            'title' => 'Horas extra el día 15', 'body' => 'Limpieza después de la fiesta',
            'audience_type' => 'areas', 'audience_ids' => [$warehouseId],
        ])->assertCreated()->assertJsonPath('recipients_count', 1);

        $this->as($this->warehouseWorker, 'app')->getJson('/api/notifications')->assertJsonCount(1, 'data');
        $this->as($this->seller, 'app')->getJson('/api/notifications')->assertJsonCount(0, 'data');
    }

    private function defaultOfficeId(): int
    {
        app(TenantManager::class)->connect($this->company);

        return Office::query()->where('is_default', true)->value('id');
    }
}
