<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable; // 1. Importar el Trait de Cashier

class Company extends Model
{
    use HasFactory, Billable; // 2. Agregar el Trait aquí

    protected $fillable = [
        'name',
        'code',
        'slug',
        'plan_id',
        'trial_ends_at',
        'subscription_ends_at',
        'has_dedicated_db',
        'custom_styles_path',
        'logo_path',
        'is_active',
        // Campos que Cashier administra automáticamente:
        'stripe_id',
        'pm_type',
        'pm_last_four',
    ];

    protected $casts = [
        'has_dedicated_db' => 'boolean',
        'is_active'        => 'boolean',
        'trial_ends_at'    => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    // --- Relaciones ---

    public function plan() {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function offices() {
        return $this->hasMany(Office::class);
    }

    public function users() {
        return $this->hasMany(User::class);
    }

    public function areas() {
        return $this->hasMany(Area::class);
    }

    public function employees() {
        return $this->hasMany(Employee::class);
    }

    public function notifications() {
        return $this->hasMany(Notification::class);
    }

    // --- Métodos de Ayuda y Estado ---

    public function isActive(): bool {
        return $this->is_active;
    }

    /**
     * Revisa si la empresa está en periodo de prueba
     * (Aprovechamos el motor de Cashier o tu campo local)
     */
    public function isOnTrial(): bool {
        return $this->onGenericTrial() || ($this->trial_ends_at && now()->lt($this->trial_ends_at));
    }

    /**
     * Revisa si la empresa tiene suscripción activa a través de Stripe
     */
    public function hasActiveSubscription(): bool {
        // 'default' es el nombre genérico de la suscripción en Cashier
        return $this->subscribed('default') || ($this->subscription_ends_at && now()->lt($this->subscription_ends_at));
    }

    protected static function booted() {
        static::creating(function ($company) {
            if (!$company->slug) {
                $company->slug = Str::slug($company->name);
            }
        });
    }
}
