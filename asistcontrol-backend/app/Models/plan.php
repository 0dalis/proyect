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
        'iva',
        'min_users',
        'max_users',
        'public',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'iva'  => 'decimal:2',
        'min_users' => 'integer',
        'max_users' => 'integer',
        'public' => 'boolean',
    ];

    public function companies()
    {
        return $this->hasMany(Company::class, 'plan_id');
    }
}
