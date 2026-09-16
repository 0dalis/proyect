<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';

    protected $fillable = [
        'employee_id',
        'salary_type',
        'base_salary',
        'pay_frequency',
        'daily_hours',
        'overtime_factor',
        'overtime_cap_minutes',
        'currency',
    ];

    protected $casts = [
        'base_salary' => 'float',
        'daily_hours' => 'float',
        'overtime_factor' => 'float',
        'overtime_cap_minutes' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
