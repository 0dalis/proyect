<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'company_id',
        'email',
        'password',
        'pending_password',
        'is_active',
        'device_token',
        'activation_token',
        'activation_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /* --------------------------------------------------------------------------
    | Relaciones
    | -------------------------------------------------------------------------- */

    // Un usuario pertenece opcionalmente a un empleado (si es que ese usuario es también un trabajador)
    public function employee()
    {
        return $this->hasOne(\App\Models\Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /* --------------------------------------------------------------------------
    | Mutators
    | -------------------------------------------------------------------------- */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }
}
