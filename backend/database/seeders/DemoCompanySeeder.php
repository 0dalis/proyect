<?php

namespace Database\Seeders;

use App\Actions\ActivateCompany;
use App\Enums\CompanyStatus;
use App\Enums\EmploymentType;
use App\Enums\Role;
use App\Enums\WorkMode;
use App\Models\Area;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Kiosk;
use App\Models\Office;
use App\Models\Plan;
use App\Models\Shift;
use App\Models\User;
use App\Tenancy\TenantManager;
use Illuminate\Database\Seeder;

/**
 * Empresa de ejemplo en el plan Básico (BD compartida) con un almacén:
 * personal de oficina con app y operadores/cargadores solo con kiosko.
 */
class DemoCompanySeeder extends Seeder
{
    public function run(ActivateCompany $activateCompany, TenantManager $tenants): void
    {
        $company = Company::query()->create([
            'name' => 'Almacenes Demo',
            'slug' => 'almacenes-demo',
            'plan_id' => Plan::query()->where('slug', 'basico')->value('id'),
            'status' => CompanyStatus::Pending,
        ]);
        // Código fijo para la demo (en empresas reales se genera al azar)
        $company->forceFill(['code' => 'DEMO2026'])->save();

        $owner = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Dueño Demo',
            'email' => 'owner@demo.test',
            'password' => 'password',
            'is_owner' => true,
        ]);
        $owner->markEmailAsVerified();

        $activateCompany->handle($company->refresh());
        $tenants->connect($company);

        $office = Office::query()->where('is_default', true)->first();
        // Monumento a la Revolución, CDMX
        $office->update(['latitude' => 19.4361, 'longitude' => -99.1546, 'geofence_radius' => 50]);
        $dayShift = Shift::query()->where('is_default', true)->first();
        $nightShift = Shift::query()->create([
            'office_id' => $office->id, 'name' => 'Vigilancia nocturna', 'starts_at' => '22:00', 'ends_at' => '06:00',
            'weekdays' => [1, 2, 3, 4, 5, 6, 7], 'tolerance_minutes' => 10, 'absence_after_minutes' => 30,
        ]);

        $general = Area::query()->first();
        $general->update(['name' => 'Recursos Humanos', 'color' => '#7c3aed']);
        $sales = Area::query()->create(['name' => 'Ventas', 'color' => '#2563eb']);
        $warehouse = Area::query()->create(['name' => 'Almacén', 'color' => '#ea580c']);
        $security = Area::query()->create(['name' => 'Seguridad', 'color' => '#475569']);

        $make = function (array $attributes) use ($office, $dayShift): Employee {
            $employee = new Employee([
                'office_id' => $office->id,
                'shift_id' => $dayShift->id,
                'employment_type' => EmploymentType::Permanent,
                'work_mode' => WorkMode::Onsite,
                'hired_on' => now()->subYear(),
                ...$attributes,
            ]);
            $employee->employee_number = $attributes['employee_number'];
            $employee->setPin('123456');
            $employee->save();
            $employee->issueBadge();

            return $employee;
        };

        $hr = $make(['employee_number' => '00001', 'first_name' => 'Ana', 'last_name' => 'Recursos', 'position' => 'Jefa de RH', 'area_id' => $general->id]);
        $salesManager = $make(['employee_number' => '00002', 'first_name' => 'Carlos', 'last_name' => 'Ventas', 'position' => 'Gerente de ventas', 'area_id' => $sales->id]);
        $seller = $make(['employee_number' => '00003', 'first_name' => 'Lucía', 'last_name' => 'Pérez', 'position' => 'Ejecutiva de ventas', 'area_id' => $sales->id]);
        $make(['employee_number' => '00004', 'first_name' => 'Jorge', 'last_name' => 'Remoto', 'position' => 'Ventas en línea', 'area_id' => $sales->id, 'work_mode' => WorkMode::Remote]);
        $make(['employee_number' => '00005', 'first_name' => 'Pedro', 'last_name' => 'Operador', 'position' => 'Montacarguista', 'area_id' => $warehouse->id]);
        $make(['employee_number' => '00006', 'first_name' => 'Luis', 'last_name' => 'Cargador', 'position' => 'Cargador', 'area_id' => $warehouse->id,
            'employment_type' => EmploymentType::Temporary, 'contract_ends_on' => now()->addMonths(2)]);
        $make(['employee_number' => '00007', 'first_name' => 'Ramón', 'last_name' => 'Vigilante', 'position' => 'Vigilante', 'area_id' => $security->id, 'shift_id' => $nightShift->id]);

        $sales->managers()->attach($salesManager->id);

        $users = [
            [$hr, 'rh@demo.test', [Role::Admin, Role::Employee]],
            [$salesManager, 'gerente@demo.test', [Role::Manager, Role::Employee]],
            [$seller, 'empleado@demo.test', [Role::Employee]],
        ];

        foreach ($users as [$employee, $email, $roles]) {
            $user = User::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'name' => $employee->fullName(),
                'email' => $email,
                'password' => 'password',
            ]);
            $user->markEmailAsVerified();
            $user->assignRole(array_map(fn (Role $role) => $role->value, $roles));
        }

        $company->update(['employees_can_use_web' => true]);

        $kiosk = new Kiosk(['office_id' => $office->id, 'name' => 'Kiosko entrada almacén']);
        $this->command?->info('Token del kiosko demo: '.$kiosk->issueToken());
    }
}
