<?php

namespace App\Models;

use App\Enums\AttendanceChannel;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'office_id', 'shift_id', 'work_date', 'type', 'recorded_at', 'channel', 'status',
    'minutes_late', 'minutes_early', 'overtime_minutes', 'latitude', 'longitude', 'accuracy_meters', 'distance_meters',
    'geofence_skipped', 'photo_path', 'device_id', 'kiosk_id', 'is_justified',
])]
class AttendanceRecord extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'recorded_at' => 'datetime',
            'type' => AttendanceType::class,
            'channel' => AttendanceChannel::class,
            'status' => AttendanceStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'float',
            'distance_meters' => 'float',
            'geofence_skipped' => 'boolean',
            'is_justified' => 'boolean',
            'overtime_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
