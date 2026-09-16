<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    protected $table = 'payroll_items';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'worked_days',
        'worked_minutes',
        'expected_minutes',
        'overtime_minutes',
        'late_count',
        'absence_count',
        'base_amount',
        'overtime_amount',
        'bonuses_amount',
        'deductions_amount',
        'gross_amount',
        'net_amount',
        'breakdown',
    ];

    protected $casts = [
        'worked_days' => 'float',
        'worked_minutes' => 'integer',
        'expected_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'late_count' => 'integer',
        'absence_count' => 'integer',
        'base_amount' => 'float',
        'overtime_amount' => 'float',
        'bonuses_amount' => 'float',
        'deductions_amount' => 'float',
        'gross_amount' => 'float',
        'net_amount' => 'float',
        'breakdown' => 'array',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
