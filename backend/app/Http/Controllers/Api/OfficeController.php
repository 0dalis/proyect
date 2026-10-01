<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Office::query()->withCount('employees')->with('shifts')->orderByDesc('is_default')->get());
    }

    public function store(Request $request, TenantManager $tenants): JsonResponse
    {
        $company = $tenants->currentOrFail();

        if (Office::query()->count() >= $company->officeLimit()) {
            throw ValidationException::withMessages([
                'office' => 'Llegaste al límite de oficinas de tu plan. Agrega oficinas extra desde Suscripción.',
            ]);
        }

        $data = $this->validated($request);
        // Sin zona indicada, la de la empresa (Cancún, Tijuana... se eligen por oficina)
        $data['timezone'] ??= $company->timezone;

        $office = Office::query()->create($data);

        return response()->json($office, 201);
    }

    public function show(Office $office): JsonResponse
    {
        return response()->json($office->load('shifts'));
    }

    public function update(Request $request, Office $office): JsonResponse
    {
        $office->update($this->validated($request, $office));

        return response()->json($office);
    }

    public function destroy(Office $office): JsonResponse
    {
        if ($office->is_default || Office::query()->count() === 1) {
            throw ValidationException::withMessages(['office' => 'No puedes eliminar la oficina principal.']);
        }

        if ($office->employees()->exists()) {
            throw ValidationException::withMessages(['office' => 'Reasigna a los empleados de esta oficina antes de eliminarla.']);
        }

        $office->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, ?Office $office = null): array
    {
        $geofence = config('tenancy.geofence');
        $required = $office ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'geofence_radius' => [$required, 'integer', "min:{$geofence['min_radius']}", "max:{$geofence['max_radius']}"],
            'timezone' => ['sometimes', 'timezone:all'],
        ]);
    }
}
