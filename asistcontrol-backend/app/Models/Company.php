<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
        'has_dedicated_db',
        'custom_styles_path',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'has_dedicated_db' => 'boolean',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function offices(){
        return $this->hasMany(Office::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }

    public function areas(){
        return $this->hasMany(Area::class);
    }

    public function notifications(){
        return $this->hasMany(Notification::class);
    }

    public function isActive(): bool{
        return $this->is_active;
    }

    public function isOnTrial(): bool{
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function hasActiveSubscription(): bool{
        return $this->subscription_ends_at && now()->lt($this->subscription_ends_at);
    }
    protected static function booted(){
        static::creating(function ($company) {
            if (!$company->slug) {
                $company->slug = Str::slug($company->name);
            }
        });
    }
}