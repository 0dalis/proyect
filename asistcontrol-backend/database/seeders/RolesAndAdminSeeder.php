<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Crear Empresa Maestra
        $company = Company::firstOrCreate(
            ['name' => 'AsistControl Global'],
            [
                'code' => 'AC-GLOBAL',
                'plan' => 'enterprise',
                'has_dedicated_db' => false,
                'is_active' => true,
            ]
        );

        // 2. Crear Permisos básicos
        $permissions = [
            'manage companies',
            'manage all users',
            'view reports',
            'mark attendance'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Crear Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'employee']);

        // 4. Crear el Super Admin (AÑADIDO EL PIN)
        $user = User::updateOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'company_id' => $company->id,
                'first_name' => 'Alejandro',
                'last_name' => 'Admin',
                'password' => 'password123',
                'pin' => 123456, // <--- Solución al error Field 'pin'
                'employee_code' => 'SA-001',
                'is_active' => true,
            ]
        );

        $user->assignRole($superAdminRole);

        $this->command->info('-----------------------------------------');
        $this->command->info('   ¡EXITO TOTAL! Usuario creado.');
        $this->command->info('   Email: admin@sistema.com');
        $this->command->info('   Pass:  password123');
        $this->command->info('   PIN:   1234');
        $this->command->info('-----------------------------------------');
    }
}