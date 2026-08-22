<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndAdminSeeder extends Seeder{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Crear Empresa Maestra
        $company = Company::firstOrCreate(
            ['name' => 'AsistControl Global'],
            [
                'code' => 'AC-GLOBAL',
                'slug' => 'asistcontrol-global',
                'has_dedicated_db' => false,
                'is_active' => true,
            ]
        );

        // 2. Crear Permisos básicos
        $permissions = [
            'index',
            'create',
            'update',
            'destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Crear Roles por defecto
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'owner']);
        Role::firstOrCreate(['name' => 'empleado']);
        Role::firstOrCreate(['name' => 'employee']);

        // 4. Crear el Super Admin (AÑADIDO EL PIN)
        $user = User::updateOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'company_id' => $company->id,
                'password' => 'password123',
                'is_active' => true,
            ]
        );

        $user->assignRole($superAdminRole);

        $this->command->info('-----------------------------------------');
        $this->command->info('   ¡EXITO TOTAL! Usuario creado.');
        $this->command->info('   Email: admin@sistema.com');
        $this->command->info('   Pass:  password123');
        $this->command->info('   PIN:   12345678');
        $this->command->info('-----------------------------------------');
    }
}
