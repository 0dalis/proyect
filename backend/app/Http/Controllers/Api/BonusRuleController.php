<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonusRule;
use App\Support\AttendanceSummary;
use App\Support\PayrollCalculator;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BonusRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'rules' => BonusRule::query()->latest()->get(),
            'metrics' => AttendanceSummary::METRICS,
            'operators' => PayrollCalculator::OPERATORS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(BonusRule::query()->create($this->validated($request)), 201);
    }

    public function update(Request $request, BonusRule $bonusRule): JsonResponse
    {
        $bonusRule->update($this->validated($request));

        return response()->json($bonusRule);
    }

    public function destroy(BonusRule $bonusRule): JsonResponse
    {
        $bonusRule->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'period' => ['required', 'in:weekly,biweekly,monthly'],
            'amount_type' => ['required', 'in:fixed,percent'],
            'amount' => ['required', 'numeric', 'min:0', Rule::when($request->input('amount_type') === 'percent', ['max:100'])],
            'conditions' => ['required', 'array', 'min:1', 'max:10'],
            'conditions.*.metric' => ['required', Rule::in(array_keys(AttendanceSummary::METRICS))],
            'conditions.*.operator' => ['required', Rule::in(PayrollCalculator::OPERATORS)],
            'conditions.*.value' => ['required', 'numeric', 'min:0'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => [TenantRule::exists('employees')],
            'is_active' => ['boolean'],
        ], [
            'conditions.required' => 'Agrega al menos una condición.',
        ]);
    }
}
