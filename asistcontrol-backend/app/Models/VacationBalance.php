<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacationBalance extends Model
{
    protected $table = 'vacation_balances';

    protected $fillable = [
        'employee_id',
        'year',
        'days_entitled',
        'days_used',
    ];

    protected $casts = [
        'year' => 'integer',
        'days_entitled' => 'float',
        'days_used' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDaysAvailableAttribute(): float
    {
        return max(0, $this->days_entitled - $this->days_used);
    }
}
