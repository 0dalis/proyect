<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\EmployeeAppAccess;
use App\Support\ActivityLogger;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Usuario de la app de un empleado (interruptor "Acceso a la app" de su ficha).
 * No tiene costo ni límite propio: el plan solo cuenta empleados.
 *
 * Al activarlo se crea el usuario con una contraseña temporal y se le envía
 * al empleado su correo, el código de la empresa y esa contraseña. En su
 * primer acceso debe cambiarla y crear su PIN de 6 dígitos.
 */
class ManageAppAccess
{
    public function __construct(private TenantManager $tenants) {}

    public function enable(Employee $employee, string $email): User
    {
        $company = $this->tenants->currentOrFail();
        $user = $this->userOf($employee);

        $this->ensureEmailIsFree($email, $user);

        if ($user) {
            $emailChanged = strcasecmp($user->email, $email) !== 0;
            $wasOff = ! $user->app_access;
            $user->forceFill(['email' => $email, 'app_access' => true])->save();

            // Correo nuevo: las credenciales viajan a la nueva dirección
            if ($emailChanged) {
                $this->sendCredentials($user, $employee);
            }

            if ($wasOff || $emailChanged) {
                ActivityLogger::log('access_changed', $user, "Activó la app de {$employee->fullName()} ({$email})", employeeId: $employee->id);
            }

            return $user;
        }

        $user = User::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'name' => $employee->fullName(),
            'email' => $email,
            'password' => Str::password(32),
            'app_access' => true,
            'web_access' => true,
        ]);
        $user->markEmailAsVerified();
        $user->assignRole(Role::Employee->value);

        $this->sendCredentials($user, $employee);
        ActivityLogger::log('access_changed', $user, "Dio acceso a la app a {$employee->fullName()} ({$email})", employeeId: $employee->id);

        return $user;
    }

    /**
     * Apaga la app: la cuenta (y sus roles) se conserva para volver a activarla.
     * Sigue pudiendo checar en el kiosko con su PIN o credencial.
     */
    public function disable(Employee $employee): void
    {
        $user = $this->userOf($employee);

        if (! $user || ! $user->app_access) {
            return;
        }

        $user->forceFill(['app_access' => false])->save();
        $user->tokens()->delete();
        DB::connection('central')->table('sessions')->where('user_id', $user->id)->delete();
        Device::query()->where('employee_id', $employee->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        ActivityLogger::log('access_changed', $user, "Quitó la app a {$employee->fullName()}", employeeId: $employee->id);
    }

    /**
     * Nueva contraseña temporal (por ejemplo, si el empleado perdió el correo).
     */
    public function resend(Employee $employee): void
    {
        $user = $this->userOf($employee);

        if (! $user || ! $user->app_access) {
            throw ValidationException::withMessages(['app_access' => 'Este empleado no tiene la app activada.']);
        }

        $this->sendCredentials($user, $employee);
        ActivityLogger::log('access_changed', $user, "Reenvió el acceso a la app a {$employee->fullName()}", employeeId: $employee->id);
    }

    public function userOf(Employee $employee): ?User
    {
        return User::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->first();
    }

    private function sendCredentials(User $user, Employee $employee): void
    {
        $temporary = self::temporaryPassword();

        $user->forceFill(['password' => $temporary, 'must_change_password' => true])->save();
        $user->tokens()->delete();

        $user->notify(new EmployeeAppAccess($this->tenants->currentOrFail(), $temporary));
    }

    private function ensureEmailIsFree(string $email, ?User $current): void
    {
        $taken = User::query()
            ->where('email', $email)
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['email' => 'Ese correo ya tiene una cuenta en AsistControl.']);
        }
    }

    /** Fácil de dictar o copiar: 10 caracteres sin símbolos ambiguos. */
    public static function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';

        for ($i = 0; $i < 10; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }
}
