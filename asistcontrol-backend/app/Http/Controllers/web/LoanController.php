<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployeeLoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);

        return response()->json(['loans' => $employee->loans()->orderByDesc('created_at')->get()]);
    }

    public function store(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);

        $validator = Validator::make($request->all(), [
            'concept' => 'required|string|max:150',
            'total_amount' => 'required|numeric|min:0.01',
            'installments' => 'required|integer|min:1|max:120',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loan = $employee->loans()->create([
            'concept' => $request->concept,
            'total_amount' => $request->total_amount,
            'installments' => $request->installments,
            'paid_installments' => 0,
            'installment_amount' => round($request->total_amount / $request->installments, 2),
            'start_date' => $request->start_date,
            'status' => 'active',
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => 'Préstamo registrado.', 'loan' => $loan], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $loan = EmployeeLoan::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'concept' => 'required|string|max:150',
            'total_amount' => 'required|numeric|min:0.01',
            'installments' => 'required|integer|min:1|max:120',
            'paid_installments' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,paid,cancelled',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loan->update([
            'concept' => $request->concept,
            'total_amount' => $request->total_amount,
            'installments' => $request->installments,
            'paid_installments' => $request->input('paid_installments', $loan->paid_installments),
            'installment_amount' => round($request->total_amount / $request->installments, 2),
            'status' => $request->input('status', $loan->status),
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => 'Préstamo actualizado.', 'loan' => $loan]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $loan = EmployeeLoan::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))->findOrFail($id);
        $loan->delete();

        return response()->json(['message' => 'Préstamo eliminado.']);
    }
}
