<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

/**
 * "Mi perfil": cada usuario edita su nombre y su correo; si está ligado a un
 * empleado, su nombre y teléfono se guardan en la ficha.
 */
class ProfileDetailsTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany('plus');
    }

    public function test_owner_updates_name_and_email_with_password(): void
    {
        $owner = $this->ownerOf($this->company);

        $this->as($owner)->getJson('/api/me/profile')
            ->assertOk()->assertJsonPath('linked_employee', false)->assertJsonPath('email', $owner->email);

        // Correo nuevo sin contraseña: no
        $this->as($owner)->putJson('/api/me/profile', ['name' => 'Ana Dueña', 'email' => 'ana@ejemplo.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->as($owner)->putJson('/api/me/profile', [
            'name' => 'Ana Dueña', 'email' => 'ana@ejemplo.com', 'current_password' => 'password',
        ])->assertOk()->assertJsonPath('user.name', 'Ana Dueña')->assertJsonPath('user.email', 'ana@ejemplo.com');

        // Mismo correo: no pide contraseña
        $this->as($owner->refresh())->putJson('/api/me/profile', ['name' => 'Ana', 'email' => 'ana@ejemplo.com'])
            ->assertOk();
    }

    public function test_email_must_be_free(): void
    {
        $owner = $this->ownerOf($this->company);
        $other = $this->createUserFor($this->company, $this->createEmployee($this->company));

        $this->as($owner)->putJson('/api/me/profile', [
            'name' => 'Ana', 'email' => $other->email, 'current_password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_employee_edits_the_name_and_phone_of_their_record(): void
    {
        $employee = $this->createEmployee($this->company);
        $user = $this->createUserFor($this->company, $employee);

        $this->as($user, 'app')->putJson('/api/me/profile', ['name' => 'Otro', 'email' => $user->email])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'first_name', 'last_name']);

        $this->as($user, 'app')->putJson('/api/me/profile', [
            'first_name' => 'Luis', 'last_name' => 'Pérez', 'phone' => '+52 55 1234 5678', 'email' => $user->email,
        ])->assertOk()->assertJsonPath('user.name', 'Luis Pérez')->assertJsonPath('user.employee.name', 'Luis Pérez');

        $this->assertSame('+52 55 1234 5678', $employee->refresh()->phone);
        $this->assertSame('Luis Pérez', User::query()->find($user->id)->name);
    }

    public function test_closing_other_sessions_requires_password(): void
    {
        $owner = $this->ownerOf($this->company);
        $owner->createToken('app');

        $this->as($owner)->postJson('/api/me/sessions/close', ['current_password' => 'mal'])->assertUnprocessable();
        $this->as($owner)->postJson('/api/me/sessions/close', ['current_password' => 'password'])->assertOk();

        $this->assertSame(0, $owner->tokens()->count());
    }
}
