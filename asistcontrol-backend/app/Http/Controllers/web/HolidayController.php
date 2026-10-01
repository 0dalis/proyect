<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyHoliday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $holidays = $company->holidays()->orderBy('date')->get();

        return response()->json(['holidays' => $holidays]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'date' => 'required|date',
            'is_paid' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $holiday = $company->holidays()->updateOrCreate(
            ['date' => $request->date],
            ['name' => $request->name, 'is_paid' => $request->boolean('is_paid', true)]
        );

        return response()->json(['message' => 'Día festivo guardado.', 'holiday' => $holiday], 201);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $holiday = $company->holidays()->findOrFail($id);
        $holiday->delete();

        return response()->json(['message' => 'Día festivo eliminado.']);
    }
}
