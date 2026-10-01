<?php

namespace Database\Seeders;

use App\Actions\ActivateCompany;
use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Planes, permisos, ajustes globales y super admin.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::put('trial_days', 14);
        ActivateCompany::ensurePermissionsExist();

        SuperAdmin::query()->updateOrCreate(
            ['email' => 'superadmin@asistcontrol.test'],
            ['name' => 'Super Admin', 'password' => 'password'],
        );

        foreach ($this->plans() as $order => $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], [...$plan, 'sort_order' => $order]);
        }
    }

    private function plans(): array
    {
        return [
            [
                'name' => 'Free', 'slug' => 'free', 'database_tier' => 'basic', 'monthly_price' => 0,
                'description' => 'Para probar con un equipo pequeño.',
                'included_employees' => 5, 'included_offices' => 1,
                'employee_block_size' => 5, 'employee_block_price' => 0, 'extra_office_price' => 0,
                'features' => ['Asistencia por app y kiosko', 'Credenciales con QR', 'Reportes básicos'],
                'includes_payroll' => false,
            ],
            [
                'name' => 'Básico', 'slug' => 'basico', 'database_tier' => 'basic', 'monthly_price' => 499,
                'description' => 'Control de asistencia completo para pymes.',
                'included_employees' => 25, 'included_offices' => 1,
                'employee_block_size' => 10, 'employee_block_price' => 150, 'extra_office_price' => 199,
                'features' => ['Todo lo de Free', 'Vacaciones y permisos', 'Geocerca por oficina', 'Notificaciones push'],
                'includes_payroll' => false,
            ],
            [
                'name' => 'Plus', 'slug' => 'plus', 'database_tier' => 'plus', 'monthly_price' => 1299,
                'description' => 'Varias sucursales, pre-nómina y bonos.',
                'included_employees' => 75, 'included_offices' => 3,
                'employee_block_size' => 25, 'employee_block_price' => 300, 'extra_office_price' => 149,
                'features' => ['Todo lo de Básico', 'Pre-nómina', 'Bonos configurables', 'Base de datos Plus (separada de Free/Básico)'],
                'includes_payroll' => true,
            ],
            [
                'name' => 'Premium', 'slug' => 'premium', 'database_tier' => 'premium', 'monthly_price' => 3499,
                'description' => 'Base de datos dedicada para tu empresa.',
                'included_employees' => 250, 'included_offices' => 10,
                'employee_block_size' => 50, 'employee_block_price' => 500, 'extra_office_price' => 99,
                'features' => ['Todo lo de Plus', 'Base de datos dedicada', 'Soporte prioritario'],
                'includes_payroll' => true,
            ],
        ];
    }
}
