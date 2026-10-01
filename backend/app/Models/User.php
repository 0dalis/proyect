<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\ResetPasswordLink;
use App\Notifications\VerifyCompanyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Cuenta de acceso (panel web / app). Vive en la BD central para poder
 * autenticarse antes de saber a qué base pertenece la empresa.
 */
#[Fillable(['company_id', 'employee_id', 'name', 'email', 'password', 'avatar_path', 'is_owner', 'app_access', 'web_access'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_owner' => 'boolean',
            'app_access' => 'boolean',
            'web_access' => 'boolean',
            'must_change_password' => 'boolean',
            'blocked_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Empleado ligado (BD de la empresa). Requiere que la empresa esté conectada.
     */
    public function employee(): ?Employee
    {
        return $this->employee_id ? Employee::query()->find($this->employee_id) : null;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * El rol más alto del usuario.
     */
    public function primaryRole(): Role
    {
        if ($this->is_owner) {
            return Role::Owner;
        }

        foreach ([Role::Admin, Role::Manager] as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return Role::Employee;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyCompanyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLink($token));
    }
}
