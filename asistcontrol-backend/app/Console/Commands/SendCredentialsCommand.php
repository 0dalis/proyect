<?php

namespace App\Console\Commands;

use App\Mail\CredencialesEmpleadoMail;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendCredentialsCommand extends Command
{
    protected $signature = 'employees:send-credentials
        {--company= : ID de la empresa (opcional; si se omite, procesa todas las empresas activas).}
        {--regenerate-missing : Regenera credenciales para empleados con acceso a la app que no tienen password pendiente (casos legacy).}';

    protected $description = 'Envía por correo las credenciales de acceso a los empleados con password pendiente y limpia el campo.';

    public function handle(): int
    {
        $query = Company::where('is_active', true);

        if ($companyId = $this->option('company')) {
            $query->where('id', $companyId);
        }

        foreach ($query->get() as $company) {
            $sent = $this->processCompany($company);
            $this->info("Empresa {$company->name}: {$sent} correo(s) de credenciales enviado(s).");
        }

        return self::SUCCESS;
    }

    protected function processCompany(Company $company): int
    {
        $sent = 0;
        $regenerateMissing = $this->option('regenerate-missing');

        $employees = $company->employees()
            ->whereNotNull('user_id')
            ->whereHas('user', function ($userQuery) {
                $userQuery->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'owner');
                });
            })
            ->with('user')
            ->get();

        foreach ($employees as $employee) {
            $user = $employee->user;

            if (! $user) {
                continue;
            }

            if (empty($user->pending_password)) {
                if (! $regenerateMissing) {
                    $this->warn("  {$user->email}: sin password pendiente. Usa --regenerate-missing para regenerarlo.");
                    continue;
                }

                $password = Str::random(12);
                $user->update([
                    'password' => $password,
                    'pending_password' => $password,
                ]);

                $this->warn("  Credenciales regeneradas para {$user->email} ({$employee->full_name}).");
            }

            try {
                Mail::to($user->email)->send(new CredencialesEmpleadoMail($user, $employee, $user->pending_password));
                $user->update(['pending_password' => null]);
                $sent++;
                $this->info("  Correo enviado a {$user->email}.");
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $this->error("  No se pudo enviar a {$user->email}: {$msg}");
            }
        }

        return $sent;
    }
}
