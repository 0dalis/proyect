<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Office;
use Illuminate\Support\Carbon;

class AttendanceService
{
    /**
     * Registra un marcaje (entrada/salida/descanso) para un empleado y recalcula métricas.
     *
     * @return array{attendance: Attendance, distance: ?float, radius: ?int}
     */
    public function mark(
        Employee $employee,
        string $type,
        ?float $latitude,
        ?float $longitude,
        string $source = 'mobile',
        ?int $officeId = null,
        ?string $recordedAt = null
    ): array {
        $office = $officeId
            ? Office::where('company_id', $employee->company_id)->find($officeId)
            : $employee->office;

        $distance = null;
        if ($office && $latitude !== null && $longitude !== null) {
            $distance = $office->distanceTo($latitude, $longitude);
            if ($distance > $office->radius_meters) {
                throw new \RuntimeException('Estás fuera del radio permitido de la oficina.');
            }
        }

        $attendance = Attendance::firstOrCreate(
            [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
            ],
            [
                'user_id' => $employee->user_id,
                'office_id' => $office?->id ?? $employee->office_id,
                'shift_id' => $employee->shift_id,
                'status' => 'present',
                'source' => $source,
            ]
        );

        AttendanceRecord::updateOrCreate(
            [
                'attendance_id' => $attendance->id,
                'type' => $type,
            ],
            [
                'user_id' => $employee->user_id,
                'employee_id' => $employee->id,
                'recorded_at' => $recordedAt ? Carbon::parse($recordedAt) : now(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'source' => $source,
            ]
        );

        $this->applyMetrics($attendance);

        return [
            'attendance' => $attendance->fresh(),
            'distance' => $distance !== null ? round($distance) : null,
            'radius' => $office?->radius_meters,
        ];
    }

    public function applyMetrics(Attendance $attendance): void
    {
        $attendance->load('records');

        $get = fn (string $type) => $attendance->records->firstWhere('type', $type);

        $checkIn = $get('check_in');
        $checkOut = $get('check_out');
        $lunchStart = $get('lunch_start');
        $lunchEnd = $get('lunch_end');

        $worked = 0;
        if ($checkIn && $checkOut) {
            $worked = Carbon::parse($checkIn->recorded_at)->diffInMinutes(Carbon::parse($checkOut->recorded_at));
            if ($lunchStart && $lunchEnd) {
                $worked -= Carbon::parse($lunchStart->recorded_at)->diffInMinutes(Carbon::parse($lunchEnd->recorded_at));
            }
        }
        $worked = max(0, (int) $worked);

        $shift = $attendance->shift;
        $lateMinutes = 0;
        $earlyMinutes = 0;
        $expectedMinutes = $shift ? $shift->getDurationMinutes() : 0;

        if ($shift && $checkIn) {
            $shiftStart = Carbon::parse($attendance->date->toDateString() . ' ' . Carbon::parse($shift->start_time)->format('H:i:s'));
            $limit = $shiftStart->copy()->addMinutes($shift->tolerance_minutes);
            if (Carbon::parse($checkIn->recorded_at)->gt($limit)) {
                $lateMinutes = $shiftStart->diffInMinutes(Carbon::parse($checkIn->recorded_at));
            }
        }

        if ($shift && $checkOut) {
            $shiftEnd = Carbon::parse($attendance->date->toDateString() . ' ' . Carbon::parse($shift->end_time)->format('H:i:s'));
            if ($shift->cross_midnight) {
                $shiftEnd->addDay();
            }
            $limit = $shiftEnd->copy()->subMinutes($shift->early_leave_minutes);
            if (Carbon::parse($checkOut->recorded_at)->lt($limit)) {
                $earlyMinutes = Carbon::parse($checkOut->recorded_at)->diffInMinutes($shiftEnd);
            }
        }

        $overtime = $expectedMinutes > 0 ? max(0, $worked - $expectedMinutes) : 0;

        if ($lateMinutes > 0) {
            $attendance->status = 'late';
        } elseif ($attendance->status !== 'absent' && $attendance->status !== 'justified') {
            $attendance->status = 'present';
        }

        $attendance->worked_minutes = $worked;
        $attendance->late_minutes = (int) $lateMinutes;
        $attendance->early_minutes = (int) $earlyMinutes;
        $attendance->overtime_minutes = (int) $overtime;
        $attendance->save();
    }
}
