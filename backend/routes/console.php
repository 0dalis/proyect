<?php

use App\Models\Company;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('tenants:migrate', function (TenantManager $tenants) {
    // Los pools compartidos se migran una sola vez
    $companies = Company::query()->whereNotNull('database')->get()->unique('database');

    foreach ($companies as $company) {
        $this->info("Migrando {$company->database}...");
        $tenants->provision($company);
        $this->line(trim(Artisan::output()));
    }
})->purpose('Corre las migraciones de empresa en todas las bases (pools y dedicadas)');

// Telescope guarda cada petición; se conservan solo las últimas 48 horas
Schedule::command('telescope:prune --hours=48')->daily();

// Suscripciones: 3 días después de un cobro fallido, la empresa pasa a Free
Schedule::command('billing:downgrade-overdue')->hourly();

// Registros cuyo dueño no confirmó el correo en 30 días
Schedule::command('accounts:prune-unverified')->daily();

// Bajas: borrado definitivo 30 días después de la solicitud
Schedule::command('companies:purge-deleted')->hourly();

// Avisos de fin de prueba: 3 días antes del primer cobro y el día del cobro
Schedule::command('billing:trial-reminders')->hourly();
