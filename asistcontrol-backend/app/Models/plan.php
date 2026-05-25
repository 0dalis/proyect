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
        'cost' => 'decimal:2',
        'tax'  => 'decimal:2',
        'min_users' => 'integer',
        'max_users' => 'integer',
        'public' => 'boolean',
    ];
}
