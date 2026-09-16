<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeConcept extends Model
{
    protected $table = 'employee_concepts';

    protected $fillable = [
        'employee_id',
        'payroll_concept_id',
        'amount_override',
        'is_active',
    ];

    protected $casts = [
        'amount_override' => 'float',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function concept()
    {
        return $this->belongsTo(PayrollConcept::class, 'payroll_concept_id');
    }
}
