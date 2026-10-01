<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AreaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Area::query()->withCount('employees')->with('managers:id,first_name,last_name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(Area::query()->create($this->validated($request)), 201);
    }

    public function update(Request $request, Area $area): JsonResponse
    {
        $area->update($this->validated($request, $area));

        return response()->json($area);
    }

    public function destroy(Area $area): JsonResponse
    {
        if ($area->employees()->exists()) {
            throw ValidationException::withMessages(['area' => 'Reasigna a los empleados de esta área antes de eliminarla.']);
        }

        $area->delete();

        return response()->json(status: 204);
    }

    /**
     * Define qué empleados supervisan el área (gerentes).
     */
    public function syncManagers(Request $request, Area $area): JsonResponse
    {
        $data = $request->validate([
            'employee_ids' => ['present', 'array'],
            'employee_ids.*' => [TenantRule::exists('employees')->whereNull('deleted_at')],
        ]);

        $area->managers()->sync($data['employee_ids']);

        return response()->json($area->load('managers:id,first_name,last_name'));
    }

    private function validated(Request $request, ?Area $area = null): array
    {
        return $request->validate([
            'name' => [$area ? 'sometimes' : 'required', 'string', 'max:120'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
