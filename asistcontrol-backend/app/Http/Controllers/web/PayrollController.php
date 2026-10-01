<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayrollConcept;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function __construct(private PayrollCalculator $calculator) {}

    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    // ---------------- Configuración ----------------

    public function settings(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $settings = $company->setting()->firstOrCreate(['company_id' => $company->id]);

        return response()->json(['settings' => $settings]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:100',
            'default_pay_frequency' => 'nullable|in:weekly,biweekly,monthly',
            'attendance_bonus_enabled' => 'boolean',
            'attendance_bonus_amount' => 'nullable|numeric|min:0',
            'late_penalty_amount' => 'nullable|numeric|min:0',
            'absence_penalty_amount' => 'nullable|numeric|min:0',
            'overtime_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $settings = $company->setting()->firstOrCreate(['company_id' => $company->id]);
        $settings->update($request->only([
            'currency', 'timezone', 'default_pay_frequency', 'attendance_bonus_enabled',
            'attendance_bonus_amount', 'late_penalty_amount', 'absence_penalty_amount', 'overtime_enabled',
        ]));

        return response()->json(['message' => 'Configuración actualizada.', 'settings' => $settings]);
    }

    // ---------------- Conceptos ----------------

    public function concepts(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $concepts = $company->payrollConcepts()->orderBy('type')->orderBy('name')->get();

        return response()->json(['concepts' => $concepts]);
    }

    public function storeConcept(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:bonus,deduction',
            'calculation' => 'required|in:fixed,percentage,per_hour,per_day',
            'amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $concept = $company->payrollConcepts()->create($request->all());

        return response()->json(['message' => 'Concepto creado.', 'concept' => $concept], 201);
    }

    public function updateConcept(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $concept = $company->payrollConcepts()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:bonus,deduction',
            'calculation' => 'required|in:fixed,percentage,per_hour,per_day',
            'amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $concept->update($request->all());

        return response()->json(['message' => 'Concepto actualizado.', 'concept' => $concept]);
    }

    public function deleteConcept(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $concept = $company->payrollConcepts()->findOrFail($id);
        $concept->delete();

        return response()->json(['message' => 'Concepto eliminado.']);
    }

    // ---------------- Periodos ----------------

    public function periods(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $periods = $company->payrollPeriods()
            ->withCount('items')
            ->orderByDesc('start_date')
            ->get();

        return response()->json(['periods' => $periods]);
    }

    public function storePeriod(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:weekly,biweekly,monthly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $period = $company->payrollPeriods()->create([
            'name' => $request->name,
            'frequency' => $request->frequency,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Periodo creado.', 'period' => $period], 201);
    }

    public function showPeriod(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $period = $company->payrollPeriods()
            ->with(['items.employee:id,first_name,last_name,employee_code,office_id,area_id'])
            ->findOrFail($id);

        return response()->json(['period' => $period]);
    }

    public function calculatePeriod(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $period = $company->payrollPeriods()->findOrFail($id);

        if ($period->isClosed()) {
            return response()->json(['message' => 'El periodo ya está cerrado.'], 422);
        }

        $period = $this->calculator->calculate($period);

        return response()->json([
            'message' => 'Nómina calculada correctamente.',
            'period' => $period->load('items.employee:id,first_name,last_name,employee_code'),
        ]);
    }

    public function closePeriod(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $period = $company->payrollPeriods()->findOrFail($id);

        if ($period->status === 'draft') {
            return response()->json(['message' => 'Primero debes calcular la nómina.'], 422);
        }

        DB::transaction(function () use ($period) {
            foreach ($period->items as $item) {
                $employee = $item->employee;
                if (! $employee) {
                    continue;
                }
                foreach ($employee->loans()->where('status', 'active')->get() as $loan) {
                    $loan->increment('paid_installments');
                    if ($loan->paid_installments >= $loan->installments) {
                        $loan->update(['status' => 'paid']);
                    }
                }
            }

            $period->update(['status' => 'closed', 'closed_at' => now()]);
        });

        return response()->json(['message' => 'Periodo cerrado y bloqueado.', 'period' => $period]);
    }

    public function deletePeriod(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $period = $company->payrollPeriods()->findOrFail($id);

        if ($period->isClosed()) {
            return response()->json(['message' => 'No se puede eliminar un periodo cerrado.'], 422);
        }

        $period->delete();

        return response()->json(['message' => 'Periodo eliminado.']);
    }
}
