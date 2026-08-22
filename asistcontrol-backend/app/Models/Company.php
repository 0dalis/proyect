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
        'setup_step',
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

    /**
     * Genera un código de empresa único con formato AC-XXXXXX.
     * Usa un alfabeto sin caracteres ambiguos (excluye O, 0, I, 1, L)
     * y reintenta hasta obtener uno que no exista en la base de datos.
     */
    public static function generateUniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $maxAttempts = 20;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = 'AC-' . substr(str_shuffle($alphabet), 0, 6);

            if (!static::where('code', $code)->exists()) {
                return $code;
            }
        }

        // Fallback poco probable: uniqid con base32 asegura unicidad casi absoluta.
        return 'AC-' . strtoupper(substr(str_replace(['O', 'I', 'L'], 'X', base_convert((string) uniqid(), 16, 36)), 0, 6));
    }

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
