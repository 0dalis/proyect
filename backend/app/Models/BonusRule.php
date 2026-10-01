<?php

namespace App\Models;

use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'period', 'amount_type', 'amount', 'conditions', 'employee_ids', 'is_active'])]
class BonusRule extends Model
{
    use Auditable, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'conditions' => 'array',
            'employee_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
