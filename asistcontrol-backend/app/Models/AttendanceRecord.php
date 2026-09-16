<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'employee_id',
        'type',
        'recorded_at',
        'latitude',
        'longitude',
        'photo_path',
        'source',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isCheckIn(): bool
    {
        return $this->type === 'check_in';
    }

    public function isCheckOut(): bool
    {
        return $this->type === 'check_out';
    }

    public function isLunchStart(): bool
    {
        return $this->type === 'lunch_start';
    }

    public function isLunchEnd(): bool
    {
        return $this->type === 'lunch_end';
    }
}