<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function registrar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:check_in,check_out,lunch_start,lunch_end',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'timestamp' => 'nullable|date',
            'office_id' => 'nullable|integer|exists:offices,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['is_active' => true, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (! $user->is_active) {
            return response()->json([
                'is_active' => false,
                'message' => 'Usuario inactivo. Contacte a su administrador.',
            ], 403);
        }

        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'is_active' => true,
                'message' => 'El usuario no está vinculado a un empleado.',
            ], 404);
        }

        $type = $request->input('type') ?: $this->detectType($employee->id);
        $lat = $request->input('lat', $request->input('latitude'));
        $lng = $request->input('lng', $request->input('longitude'));

        try {
            $result = $this->attendanceService->mark(
                $employee,
                $type,
                $lat !== null ? (float) $lat : null,
                $lng !== null ? (float) $lng : null,
                'mobile',
                $request->input('office_id'),
                $request->input('timestamp')
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'is_active' => true,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'is_active' => true,
            'message' => 'Marcaje registrado.',
            'type' => $type,
            'attendance' => $result['attendance'],
            'distance' => $result['distance'],
            'radius' => $result['radius'],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['records' => []]);
        }

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $records = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$from, $to])
            ->with('records')
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'records' => $records,
            'summary' => [
                'present' => $records->where('status', 'present')->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'worked_minutes' => (int) $records->sum('worked_minutes'),
                'overtime_minutes' => (int) $records->sum('overtime_minutes'),
            ],
        ]);
    }

    private function detectType(int $employeeId): string
    {
        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', now()->toDateString())
            ->with('records')
            ->first();

        if (! $attendance) {
            return 'check_in';
        }

        $types = $attendance->records->pluck('type')->all();

        if (! in_array('check_in', $types, true)) {
            return 'check_in';
        }
        if (! in_array('check_out', $types, true)) {
            return 'check_out';
        }

        return 'check_in';
    }
}
