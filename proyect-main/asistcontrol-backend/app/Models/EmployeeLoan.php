<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    protected $table = 'employee_loans';

    protected $fillable = [
        'employee_id',
        'concept',
        'total_amount',
        'installments',
        'paid_installments',
        'installment_amount',
        'start_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'installment_amount' => 'float',
        'installments' => 'integer',
        'paid_installments' => 'integer',
        'start_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getRemainingAttribute(): float
    {
        return max(0, ($this->installments - $this->paid_installments) * $this->installment_amount);
    }
}
