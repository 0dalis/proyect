<?php

namespace Tests\Feature;

use App\Support\EmployeeCode;
use Tests\TestCase;

class EmployeeCodeTest extends TestCase
{
    public function test_the_prefix_takes_two_letters_per_word_of_the_company(): void
    {
        $this->assertSame('ALDE', EmployeeCode::prefix('Almacenes Demo'));
        $this->assertSame('EMDE', EmployeeCode::prefix('Empresa Demo'));
        $this->assertSame('EMPR', EmployeeCode::prefix('empresa'));
        $this->assertSame('CAAR', EmployeeCode::prefix('Café Aroma'));
        $this->assertSame('FALU', EmployeeCode::prefix('Farmacias Luna'));
        // Una sola palabra corta: sus primeras letras rellenadas con X
        $this->assertSame('ZAPX', EmployeeCode::prefix('Zap'));
        // Sin letras: se usa el código corto de la empresa y si tampoco da, EMPR
        $this->assertSame('DEMO', EmployeeCode::prefix('1234', 'DEMO2026'));
        $this->assertSame('EMPR', EmployeeCode::prefix('1234'));
    }

    public function test_a_new_employee_gets_the_code_of_his_company(): void
    {
        $company = $this->createCompany('basico', ['name' => 'Almacenes Demo']);
        $first = $this->createEmployee($company);
        $second = $this->createEmployee($company);

        $this->assertSame('ALDE-0001', $first->employee_code);
        $this->assertSame('ALDE-0002', $second->employee_code);
        // El número interno sigue generándose: es lo que se teclea con el PIN
        $this->assertMatchesRegularExpression('/^\d{5}$/', $first->employee_number);
    }

    public function test_the_sequence_is_per_company(): void
    {
        $one = $this->createCompany('basico', ['name' => 'Almacenes Demo']);
        $other = $this->createCompany('basico', ['name' => 'Farmacias Luna']);

        $this->assertSame('ALDE-0001', $this->createEmployee($one)->employee_code);
        $this->assertSame('FALU-0001', $this->createEmployee($other)->employee_code);
    }

    public function test_the_panel_shows_the_code_and_hides_the_internal_number(): void
    {
        $company = $this->createCompany('basico', ['name' => 'Almacenes Demo']);
        $employee = $this->createEmployee($company);
        $owner = $this->ownerOf($company);

        $detail = $this->as($owner)->getJson("/api/employees/{$employee->getRouteKey()}")->assertOk()->json();

        $this->assertSame('ALDE-0001', $detail['employee_code']);
        $this->assertArrayNotHasKey('employee_number', $detail);

        $list = $this->as($owner)->getJson('/api/employees')->assertOk()->json('data.0');

        $this->assertSame('ALDE-0001', $list['employee_code']);
        $this->assertArrayNotHasKey('employee_number', $list);
    }

    public function test_the_list_can_be_searched_by_code(): void
    {
        $company = $this->createCompany('basico', ['name' => 'Almacenes Demo']);
        $employee = $this->createEmployee($company);

        $found = $this->as($this->ownerOf($company))->getJson('/api/employees?search=ALDE-0001')
            ->assertOk()->json('data');

        $this->assertCount(1, $found);
        $this->assertSame($employee->employee_code, $found[0]['employee_code']);
    }

    public function test_the_kiosk_accepts_the_numeric_part_of_the_printed_code(): void
    {
        $company = $this->createCompany('basico', ['name' => 'Almacenes Demo']);
        $employee = $this->createEmployee($company, ['pin' => '432100']);
        $token = $this->as($this->ownerOf($company))->postJson('/api/kiosks', [
            'name' => 'Entrada',
            'office_id' => $employee->office_id,
        ])->assertCreated()->json('token');

        $this->withHeader('X-Kiosk-Token', $token)
            ->postJson('/api/kiosk/punch', ['method' => 'pin', 'employee_number' => '0001', 'pin' => '432100'])
            ->assertCreated()
            ->assertJsonPath('employee.employee_code', 'ALDE-0001');
    }

    public function test_the_kiosk_still_accepts_the_internal_number(): void
    {
        $company = $this->createCompany('basico', ['name' => 'Almacenes Demo']);
        $employee = $this->createEmployee($company, ['employee_number' => '00042', 'pin' => '432100']);
        $token = $this->as($this->ownerOf($company))->postJson('/api/kiosks', [
            'name' => 'Entrada',
            'office_id' => $employee->office_id,
        ])->assertCreated()->json('token');

        $this->withHeader('X-Kiosk-Token', $token)
            ->postJson('/api/kiosk/punch', ['method' => 'pin', 'employee_number' => '00042', 'pin' => '432100'])
            ->assertCreated();
    }
}
