<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model{
    use HasFactory;
    protected $fillable = [
        'nombre',
        'tipo',
        'precio',
        'annual_price',
        'per_extra_user_price',
        'per_extra_office_price',
        'iva',
        'min_users',
        'max_users',
        'max_offices',
        'caracteristicas',
        'features',
        'stripe_price_id',
        'stripe_annual_price_id',
        'public',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'per_extra_user_price' => 'decimal:2',
        'per_extra_office_price' => 'decimal:2',
        'iva'  => 'decimal:2',
        'min_users' => 'integer',
        'max_users' => 'integer',
        'max_offices' => 'integer',
        'caracteristicas' => 'array',
        'features' => 'array',
        'public' => 'boolean',
    ];

    public function companies()
    {
        return $this->hasMany(Company::class, 'plan_id');
    }
}
