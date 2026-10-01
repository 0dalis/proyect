<?php

namespace App\Models;

use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'color', 'description'])]
class Area extends Model
{
    use Auditable, BelongsToCompany;

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'area_manager');
    }
}
