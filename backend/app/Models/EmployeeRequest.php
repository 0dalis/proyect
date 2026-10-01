<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'type', 'starts_on', 'ends_on', 'expected_time', 'reason', 'attendance_record_id'])]
class EmployeeRequest extends Model
{
    use Auditable, BelongsToCompany;

    protected array $auditEvents = ['created'];

    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'status' => RequestStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
