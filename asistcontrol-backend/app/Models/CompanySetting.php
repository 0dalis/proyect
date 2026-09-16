<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'company_id',
        'currency',
        'timezone',
        'default_pay_frequency',
        'default_vacation_days',
        'attendance_bonus_enabled',
        'attendance_bonus_amount',
        'late_penalty_amount',
        'absence_penalty_amount',
        'overtime_enabled',
        'settings',
    ];

    protected $casts = [
        'attendance_bonus_enabled' => 'boolean',
        'overtime_enabled' => 'boolean',
        'attendance_bonus_amount' => 'float',
        'late_penalty_amount' => 'float',
        'absence_penalty_amount' => 'float',
        'default_vacation_days' => 'integer',
        'settings' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
