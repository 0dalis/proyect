<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ShiftsController extends Controller
{
    private function getCompany(Request $request)
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $offices = $company->offices()
            ->with(['shifts' => fn ($q) => $q->orderBy('start_time')])
            ->orderBy('name')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'name' => $office->name,
                    'shifts' => $office->shifts->map(fn ($s) => $this->shiftPayload($s))->values(),
                ];
            })
            ->values();

        $employees = $company->employees()
            ->where(function ($query) {
                $query->whereNull('user_id')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->whereDoesntHave('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'owner');
                        });
                    });
            })
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'office_id', 'shift_id']);

        return response()->json([
            'offices' => $offices,
            'employees' => $employees,
        ]);
    }

    public function storeShift(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'office_id' => 'required|integer|exists:offices,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'cross_midnight' => 'boolean',
            'lunch_start' => 'nullable|date_format:H:i',
            'lunch_end' => 'nullable|date_format:H:i',
            'tolerance_minutes' => 'integer|min:0|max:120',
            'early_leave_minutes' => 'integer|min:0|max:120',
            'work_hours_expected' => 'nullable|integer|min:0|max:1440',
            'is_active' => 'boolean',
        ], [
            'start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $office = $company->offices()->find($request->office_id);

        if (! $office) {
            return response()->json(['message' => 'La oficina no pertenece a tu empresa.'], 422);
        }

        $shift = $office->shifts()->create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'cross_midnight' => $request->boolean('cross_midnight', false),
            'lunch_start' => $request->lunch_start,
            'lunch_end' => $request->lunch_end,
            'tolerance_minutes' => $request->input('tolerance_minutes', 10),
            'early_leave_minutes' => $request->input('early_leave_minutes', 0),
            'work_hours_expected' => $request->work_hours_expected,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Turno creado correctamente.',
            'shift' => $this->shiftPayload($shift),
        ], 201);
    }

    public function updateShift(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $shift = $this->companyShift($company, $id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'cross_midnight' => 'boolean',
            'lunch_start' => 'nullable|date_format:H:i',
            'lunch_end' => 'nullable|date_format:H:i',
            'tolerance_minutes' => 'integer|min:0|max:120',
            'early_leave_minutes' => 'integer|min:0|max:120',
            'work_hours_expected' => 'nullable|integer|min:0|max:1440',
            'is_active' => 'boolean',
        ], [
            'start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shift->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'cross_midnight' => $request->boolean('cross_midnight', false),
            'lunch_start' => $request->lunch_start,
            'lunch_end' => $request->lunch_end,
            'tolerance_minutes' => $request->input('tolerance_minutes', $shift->tolerance_minutes),
            'early_leave_minutes' => $request->input('early_leave_minutes', $shift->early_leave_minutes),
            'work_hours_expected' => $request->work_hours_expected,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Turno actualizado correctamente.',
            'shift' => $this->shiftPayload($shift),
        ]);
    }

    public function deleteShift(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $shift = $this->companyShift($company, $id);

        $shift->delete();

        return response()->json(['message' => 'Turno eliminado correctamente.']);
    }

    public function assignShift(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'shift_id' => 'nullable|integer|exists:shifts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $company->employees()->find($request->employee_id);

        if (! $employee) {
            return response()->json(['message' => 'El empleado no pertenece a tu empresa.'], 422);
        }

        $shiftId = $request->shift_id;

        if ($shiftId) {
            $shift = $this->companyShift($company, $shiftId);

            if ($shift->office_id !== $employee->office_id) {
                return response()->json([
                    'message' => 'El turno debe pertenecer a la oficina del empleado.',
                ], 422);
            }
        }

        $employee->update(['shift_id' => $shiftId]);

        return response()->json([
            'message' => $shiftId ? 'Turno asignado correctamente.' : 'Turno desasignado correctamente.',
        ]);
    }

    private function companyShift($company, int $id): Shift
    {
        return Shift::whereHas('office', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->findOrFail($id);
    }

    private function shiftPayload(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'office_id' => $shift->office_id,
            'name' => $shift->name,
            'start_time' => Carbon::parse($shift->start_time)->format('H:i'),
            'end_time' => Carbon::parse($shift->end_time)->format('H:i'),
            'cross_midnight' => (bool) $shift->cross_midnight,
            'lunch_start' => $shift->lunch_start ? Carbon::parse($shift->lunch_start)->format('H:i') : null,
            'lunch_end' => $shift->lunch_end ? Carbon::parse($shift->lunch_end)->format('H:i') : null,
            'tolerance_minutes' => (int) $shift->tolerance_minutes,
            'early_leave_minutes' => (int) $shift->early_leave_minutes,
            'work_hours_expected' => $shift->work_hours_expected,
            'is_active' => (bool) $shift->is_active,
        ];
    }
}
