<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollConcept extends Model
{
    protected $table = 'payroll_concepts';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'calculation',
        'amount',
        'is_recurring',
        'is_active',
        'description',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeConcepts()
    {
        return $this->hasMany(EmployeeConcept::class);
    }
}
