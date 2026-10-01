<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $query = Attendance::where('company_id', $company->id)
            ->with(['employee:id,first_name,last_name,employee_code,office_id,area_id', 'office:id,name', 'shift:id,name,start_time,end_time']);

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }
        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('area_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('area_id', $request->area_id));
        }

        $query->orderByDesc('date')->orderByDesc('id');

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $attendance = Attendance::where('company_id', $company->id)
            ->with(['employee', 'office', 'shift', 'records', 'corrections.user:id,email'])
            ->findOrFail($id);

        return response()->json(['attendance' => $attendance]);
    }

    public function summary(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rows = Attendance::where('company_id', $company->id)
            ->whereBetween('date', [$request->from, $request->to])
            ->selectRaw('employee_id')
            ->selectRaw('count(*) as total_days')
            ->selectRaw("sum(case when status = 'present' then 1 else 0 end) as present_days")
            ->selectRaw("sum(case when status = 'late' then 1 else 0 end) as late_days")
            ->selectRaw("sum(case when status = 'absent' then 1 else 0 end) as absent_days")
            ->selectRaw("sum(case when status = 'justified' then 1 else 0 end) as justified_days")
            ->selectRaw('coalesce(sum(worked_minutes), 0) as worked_minutes')
            ->selectRaw('coalesce(sum(overtime_minutes), 0) as overtime_minutes')
            ->groupBy('employee_id')
            ->with('employee:id,first_name,last_name,employee_code,office_id,area_id')
            ->get();

        return response()->json(['summary' => $rows]);
    }

    public function correct(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'date' => 'required|date',
            'status' => 'nullable|in:present,late,absent,justified',
            'source' => 'nullable|in:manual,kiosk,mobile',
            'reason' => 'nullable|string|max:500',
            'records' => 'nullable|array',
            'records.*.type' => 'required|in:check_in,check_out,lunch_start,lunch_end',
            'records.*.recorded_at' => 'required|date',
            'records.*.latitude' => 'nullable|numeric|between:-90,90',
            'records.*.longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $company->employees()->findOrFail($request->employee_id);

        $attendance = DB::transaction(function () use ($company, $employee, $request, &$before) {
            $attendance = Attendance::firstOrNew([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'date' => Carbon::parse($request->date)->toDateString(),
            ]);

            $before = $attendance->exists ? $attendance->toArray() : null;

            $attendance->user_id = $employee->user_id;
            $attendance->office_id = $employee->office_id;
            $attendance->shift_id = $employee->shift_id;
            $attendance->source = $request->input('source', 'manual');
            $attendance->status = $request->input('status', $attendance->status ?? 'present');
            $attendance->save();

            foreach ($request->input('records', []) as $record) {
                AttendanceRecord::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'type' => $record['type'],
                    ],
                    [
                        'user_id' => $employee->user_id,
                        'employee_id' => $employee->id,
                        'recorded_at' => Carbon::parse($record['recorded_at']),
                        'latitude' => $record['latitude'] ?? null,
                        'longitude' => $record['longitude'] ?? null,
                        'source' => $request->input('source', 'manual'),
                    ]
                );
            }

            $this->applyMetrics($attendance);

            AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'user_id' => $request->user()->id,
                'action' => $before ? 'update' : 'create',
                'before' => $before,
                'after' => $attendance->fresh()->toArray(),
                'reason' => $request->reason,
            ]);

            return $attendance;
        });

        return response()->json([
            'message' => 'Asistencia registrada correctamente.',
            'attendance' => $attendance->load(['employee', 'records']),
        ]);
    }

    public function kiosk(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'employee_code' => 'required_without_all:pin,qr|string',
            'pin' => 'required_without_all:employee_code,qr|string',
            'qr' => 'required_without_all:employee_code,pin|string',
            'type' => 'required|in:check_in,check_out,lunch_start,lunch_end',
            'office_id' => 'nullable|integer|exists:offices,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('qr')) {
            $parsed = \App\Models\EmployeeCredential::verifyPayload($request->qr);

            if (! $parsed) {
                return response()->json(['message' => 'Código QR inválido o alterado.'], 422);
            }

            $employee = $company->employees()
                ->where('is_active', true)
                ->where('employee_code', $parsed['employee_code'])
                ->first();
        } else {
            $employeeQuery = $company->employees()->where('is_active', true);
            if ($request->filled('employee_code')) {
                $employee = $employeeQuery->where('employee_code', $request->employee_code)->first();
            } else {
                $employee = $employeeQuery->whereNotNull('pin')->get()
                    ->first(fn ($e) => \Illuminate\Support\Facades\Hash::check($request->pin, $e->pin));
            }
        }

        if (! $employee) {
            return response()->json(['message' => 'Empleado no encontrado o inactivo.'], 404);
        }

        $office = $request->filled('office_id')
            ? Office::where('company_id', $company->id)->find($request->office_id)
            : $employee->office;

        if ($office && $request->filled('latitude') && $request->filled('longitude')) {
            if (! $office->isWithinRadius($request->latitude, $request->longitude)) {
                return response()->json([
                    'message' => 'Estás fuera del radio permitido de la oficina.',
                    'distance' => round($office->distanceTo($request->latitude, $request->longitude)),
                    'radius' => $office->radius_meters,
                ], 422);
            }
        }

        $attendance = Attendance::firstOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
            ],
            [
                'user_id' => $employee->user_id,
                'office_id' => $office?->id ?? $employee->office_id,
                'shift_id' => $employee->shift_id,
                'status' => 'present',
                'source' => 'kiosk',
            ]
        );

        AttendanceRecord::updateOrCreate(
            [
                'attendance_id' => $attendance->id,
                'type' => $request->type,
            ],
            [
                'user_id' => $employee->user_id,
                'employee_id' => $employee->id,
                'recorded_at' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'source' => 'kiosk',
            ]
        );

        $this->applyMetrics($attendance);

        return response()->json([
            'message' => 'Marcaje registrado.',
            'employee' => $employee->full_name,
            'type' => $request->type,
            'attendance' => $attendance->fresh(),
        ]);
    }

    private function applyMetrics(Attendance $attendance): void
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
