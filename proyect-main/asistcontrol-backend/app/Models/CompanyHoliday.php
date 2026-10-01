<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyHoliday extends Model
{
    protected $table = 'company_holidays';

    protected $fillable = ['company_id', 'name', 'date', 'is_paid'];

    protected $casts = [
        'date' => 'date',
        'is_paid' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
