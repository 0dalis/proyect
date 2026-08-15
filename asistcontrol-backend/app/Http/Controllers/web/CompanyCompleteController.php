<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use App\Services\PlanLimitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyCompleteController extends Controller
{
    public function __construct(
        private PlanLimitsService $limits
    ) {}

    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function status(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        return response()->json([
            'setup_step' => $company->setup_step,
            'is_active' => $company->is_active,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'slug' => $company->slug,
                'logo_path' => $company->logo_path,
            ],
            'offices_count' => $company->offices()->count(),
            'areas_count' => $company->areas()->count(),
            'employees_count' => $company->employees()->count(),
        ]);
    }

    public function limits(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        return response()->json($this->limits->summary($company));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($data);

        return response()->json([
            'message' => 'Perfil de empresa actualizado correctamente.',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'slug' => $company->slug,
                'logo_path' => $company->logo_path,
            ],
        ]);
    }

    public function offices(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $offices = $company->offices()->orderBy('name')->get();

        return response()->json(['offices' => $offices]);
    }

    public function storeOffice(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $this->limits->canCreateOffice($company)) {
            $limit = $this->limits->officeLimit($company);

            return response()->json([
                'message' => "Alcanzaste el límite de oficinas de tu plan ($limit).",
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'timezone' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $office = $company->offices()->create([
            'name' => $request->name,
            'code' => $request->code,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters,
            'timezone' => $request->timezone ?? 'America/Mexico_City',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Oficina creada correctamente.',
            'office' => $office,
        ], 201);
    }

    public function updateOffice(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $office = $company->offices()->findOrFail($id);

        if (! $this->limits->canEditOffice($company, $office)) {
            return response()->json([
                'message' => 'Las oficinas que exceden tu plan solo pueden eliminarse.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'timezone' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $office->update($request->only([
            'name', 'code', 'latitude', 'longitude', 'radius_meters', 'timezone', 'is_active',
        ]));

        return response()->json([
            'message' => 'Oficina actualizada correctamente.',
            'office' => $office,
        ]);
    }

    public function deleteOffice(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $office = $company->offices()->findOrFail($id);

        if ($office->employees()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la oficina porque tiene empleados asignados.',
            ], 422);
        }

        $office->delete();

        return response()->json(['message' => 'Oficina eliminada correctamente.']);
    }

    public function areas(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $areas = $company->areas()->orderBy('name')->get();

        return response()->json(['areas' => $areas]);
    }

    public function storeArea(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $area = $company->areas()->create([
            'name' => $request->name,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Área creada correctamente.',
            'area' => $area,
        ], 201);
    }

    public function updateArea(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $area = $company->areas()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $area->update($request->only(['name', 'is_active']));

        return response()->json([
            'message' => 'Área actualizada correctamente.',
            'area' => $area,
        ]);
    }

    public function deleteArea(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $area = $company->areas()->findOrFail($id);

        if ($area->employees()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el área porque tiene empleados asignados.',
            ], 422);
        }

        $area->delete();

        return response()->json(['message' => 'Área eliminada correctamente.']);
    }

    public function employees(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $employees = $company->employees()
            ->with(['office', 'area', 'user'])
            ->orderBy('first_name')
            ->get();

        return response()->json(['employees' => $employees]);
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $this->limits->canCreateEmployee($company)) {
            $limit = $this->limits->employeeLimit($company);

            return response()->json([
                'message' => "Alcanzaste el límite de empleados de tu plan ($limit).",
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:50|
                unique:employees,employee_code,NULL,id,company_id,' . $company->id,
            'office_id' => 'required|exists:offices,id',
            'area_id' => 'required|exists:areas,id',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:8',
            'has_app_access' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hasAppAccess = $request->boolean('has_app_access', false);

        $employee = $company->employees()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'employee_code' => $request->employee_code,
            'office_id' => $request->office_id,
            'area_id' => $request->area_id,
            'pin' => '',
            'is_active' => true,
        ]);

        if ($hasAppAccess && $request->email && $request->password) {
            $user = User::create([
                'company_id' => $company->id,
                'email' => $request->email,
                'password' => $request->password,
                'is_active' => true,
            ]);

            $user->assignRole('employee');
            $employee->update(['user_id' => $user->id]);
        }

        $employee->load(['office', 'area', 'user']);

        return response()->json([
            'message' => 'Empleado creado correctamente.',
            'employee' => $employee,
        ], 201);
    }

    public function updateEmployee(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        if (! $this->limits->canEditEmployee($company, $employee)) {
            return response()->json([
                'message' => 'Los empleados que exceden tu plan solo pueden eliminarse.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:50|
                unique:employees,employee_code,' . $employee->id . ',id,company_id,' . $company->id,
            'office_id' => 'required|exists:offices,id',
            'area_id' => 'required|exists:areas,id',
            'is_active' => 'boolean',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:8',
            'has_app_access' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'employee_code' => $request->employee_code,
            'office_id' => $request->office_id,
            'area_id' => $request->area_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $hasAppAccess = $request->boolean('has_app_access', false);

        if ($hasAppAccess && $request->email && !$employee->user_id && $request->password) {
            $user = User::create([
                'company_id' => $company->id,
                'email' => $request->email,
                'password' => $request->password,
                'is_active' => true,
            ]);
            $user->assignRole('employee');
            $employee->update(['user_id' => $user->id]);
        } elseif ($hasAppAccess && $employee->user_id && $request->email) {
            $user = $employee->user;
            $user->update(['email' => $request->email]);
            if ($request->password) {
                $user->update(['password' => $request->password]);
            }
        } elseif (!$hasAppAccess && $employee->user_id) {
            $employee->user->delete();
            $employee->update(['user_id' => null]);
        }

        $employee->load(['office', 'area', 'user']);

        return response()->json([
            'message' => 'Empleado actualizado correctamente.',
            'employee' => $employee,
        ]);
    }

    public function deleteEmployee(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($id);

        if ($employee->user_id) {
            $employee->user->delete();
        }

        $employee->delete();

        return response()->json(['message' => 'Empleado eliminado correctamente.']);
    }

    public function nextStep(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $currentStep = (int) $request->input('step', $company->setup_step);

        $nextStep = match ($currentStep) {
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            default => $currentStep,
        };

        $company->update(['setup_step' => $nextStep]);

        return response()->json([
            'message' => 'Paso avanzado correctamente.',
            'setup_step' => $nextStep,
        ]);
    }

    public function previousStep(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $currentStep = (int) $request->input('step', $company->setup_step);

        $prevStep = match ($currentStep) {
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            default => 1,
        };

        $company->update(['setup_step' => $prevStep]);

        return response()->json([
            'message' => 'Paso retrocedido correctamente.',
            'setup_step' => $prevStep,
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $officesCount = $company->offices()->count();
        $areasCount = $company->areas()->count();
        $employeesCount = $company->employees()->count();

        if ($officesCount === 0 || $areasCount === 0 || $employeesCount === 0) {
            return response()->json([
                'message' => 'Debes completar todos los pasos antes de finalizar.',
                'missing' => array_filter([
                    $officesCount === 0 ? 'Debes crear al menos una oficina.' : null,
                    $areasCount === 0 ? 'Debes crear al menos un área.' : null,
                    $employeesCount === 0 ? 'Debes crear al menos un empleado.' : null,
                ]),
            ], 422);
        }

        $company->update([
            'is_active' => true,
            'setup_step' => 5,
        ]);

        return response()->json([
            'message' => '¡Configuración completada! Tu empresa ya está activa.',
            'is_active' => true,
        ]);
    }
}
