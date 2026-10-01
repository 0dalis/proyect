<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $query = $company->employees()
            ->with(['office:id,name', 'area:id,name', 'shift:id,name', 'compensation', 'concepts.concept'])
            ->orderBy('first_name');

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        return response()->json(['employees' => $query->get()]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()
            ->with(['office', 'area', 'shift', 'compensation', 'concepts.concept', 'vacationBalances'])
            ->findOrFail($id);

        return response()->json(['employee' => $employee]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:10|regex:/^[A-Z0-9]+$/|unique:employees,employee_code,NULL,id,company_id,' . $company->id,
            'office_id' => 'required|exists:offices,id',
            'area_id' => 'nullable|exists:areas,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'is_area_manager' => 'boolean',
            'is_active' => 'boolean',
            'pin' => 'nullable|string|min:4|max:10',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
            'hired_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $company->employees()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'employee_code' => $request->employee_code,
            'office_id' => $request->office_id,
            'area_id' => $request->area_id,
            'shift_id' => $request->shift_id,
            'is_area_manager' => $request->boolean('is_area_manager'),
            'pin' => $request->input('pin', ''),
            'is_active' => $request->boolean('is_active', true),
            'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account,
            'position' => $request->position,
            'hired_at' => $request->hired_at,
        ]);

        if ($request->filled('pin')) {
            $employee->update(['pin' => $request->pin]);
        }

        return response()->json(['message' => 'Empleado creado.', 'employee' => $employee], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:10|regex:/^[A-Z0-9]+$/|unique:employees,employee_code,' . $employee->id . ',id,company_id,' . $company->id,
            'office_id' => 'required|exists:offices,id',
            'area_id' => 'nullable|exists:areas,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'is_area_manager' => 'boolean',
            'is_active' => 'boolean',
            'pin' => 'nullable|string|min:4|max:10',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
            'hired_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'first_name', 'last_name', 'employee_code', 'office_id', 'area_id', 'shift_id',
            'bank_name', 'bank_account', 'position', 'hired_at',
        ]);
        $data['is_area_manager'] = $request->boolean('is_area_manager');
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->filled('pin')) {
            $data['pin'] = $request->pin;
        }

        $employee->update($data);

        return response()->json(['message' => 'Empleado actualizado.', 'employee' => $employee]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);
        $employee->delete();

        return response()->json(['message' => 'Empleado eliminado.']);
    }

    public function generateCode(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $prefix = Str::substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(Str::ascii($company->name))), 0, 4) ?: 'EMP';

        for ($i = 0; $i < 30; $i++) {
            $code = Str::substr($prefix . substr(str_shuffle($alphabet), 0, 6), 0, 10);
            if (! $company->employees()->where('employee_code', $code)->exists()) {
                return response()->json(['code' => $code]);
            }
        }

        return response()->json(['code' => Str::substr($prefix . substr(str_shuffle($alphabet), 0, 6), 0, 10)]);
    }

    public function updateCompensation(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'salary_type' => 'required|in:fixed,hourly',
            'base_salary' => 'required|numeric|min:0',
            'pay_frequency' => 'required|in:weekly,biweekly,monthly',
            'daily_hours' => 'nullable|numeric|min:1|max:24',
            'overtime_factor' => 'nullable|numeric|min:1|max:5',
            'overtime_cap_minutes' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $compensation = $employee->compensation()->updateOrCreate(
            ['employee_id' => $employee->id],
            $request->only([
                'salary_type', 'base_salary', 'pay_frequency', 'daily_hours',
                'overtime_factor', 'overtime_cap_minutes', 'currency',
            ])
        );

        return response()->json(['message' => 'Compensación actualizada.', 'compensation' => $compensation]);
    }

    public function syncConcepts(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'concepts' => 'array',
            'concepts.*.payroll_concept_id' => 'required|integer|exists:payroll_concepts,id',
            'concepts.*.amount_override' => 'nullable|numeric|min:0',
            'concepts.*.is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($employee, $request) {
            $employee->concepts()->delete();
            foreach ($request->input('concepts', []) as $concept) {
                $employee->concepts()->create([
                    'payroll_concept_id' => $concept['payroll_concept_id'],
                    'amount_override' => $concept['amount_override'] ?? null,
                    'is_active' => $concept['is_active'] ?? true,
                ]);
            }
        });

        return response()->json([
            'message' => 'Conceptos actualizados.',
            'employee' => $employee->load('concepts.concept'),
        ]);
    }
}
