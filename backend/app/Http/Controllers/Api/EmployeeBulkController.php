<?php

namespace App\Http\Controllers\Api;

use App\Actions\ManageAppAccess;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Shift;
use App\Support\ActivityLogger;
use App\Support\TenantRule;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Carga masiva de empleados en dos pasos:
 * 1. import: nombre, apellidos y sueldo (opcional). Quedan "sin organizar":
 *    sin oficina, turno, área ni tipo, y no pueden checar todavía.
 * 2. organize: en una tabla se eligen varios y se les asigna oficina, turno,
 *    área, tipo y, si se quiere, acceso a la app con su correo.
 */
class EmployeeBulkController extends Controller
{
    /** Filas por carga (un archivo más grande se sube en partes). */
    private const MAX_ROWS = 500;

    public function import(Request $request, TenantManager $tenants): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'rows.*.first_name' => ['required', 'string', 'max:80'],
            'rows.*.last_name' => ['required', 'string', 'max:80'],
            'rows.*.salary' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            // M = mensual, D = diario
            'rows.*.salary_period' => ['nullable', 'required_with:rows.*.salary', 'in:daily,monthly'],
        ], [
            'rows.*.first_name.required' => 'Fila :position: falta el nombre.',
            'rows.*.last_name.required' => 'Fila :position: faltan los apellidos.',
            'rows.*.salary.numeric' => 'Fila :position: el sueldo debe ser un número.',
        ]);

        $company = $tenants->currentOrFail();
        $active = Employee::query()->where('status', EmployeeStatus::Active)->count();
        $room = max(0, $company->employeeLimit() - $active);

        if (count($data['rows']) > $room) {
            throw ValidationException::withMessages([
                'rows' => "Tu plan permite {$company->employeeLimit()} empleados activos y ya tienes {$active}: "
                    ."puedes importar {$room}. Mejora tu plan o agrega bloques de empleados.",
            ]);
        }

        $created = DB::connection('tenant')->transaction(function () use ($data) {
            $ids = [];

            foreach ($data['rows'] as $row) {
                $employee = new Employee([
                    'first_name' => trim($row['first_name']),
                    'last_name' => trim($row['last_name']),
                    'salary' => $row['salary'] ?? null,
                    'salary_period' => isset($row['salary']) ? $row['salary_period'] : null,
                    'status' => EmployeeStatus::Active,
                ]);
                // Sin organizar: se asignan en el paso 2
                $employee->forceFill(['office_id' => null, 'shift_id' => null, 'area_id' => null, 'employment_type' => null]);
                $employee->save();
                $employee->issueBadge();
                $ids[] = $employee->id;
            }

            return $ids;
        });

        ActivityLogger::log('created', description: 'Importó '.count($created).' empleados (carga masiva)');

        return response()->json([
            'created' => count($created),
            'pending' => Employee::query()->needsSetup()->count(),
        ], 201);
    }

    /**
     * Empleados que falta organizar (sin oficina, turno, área o tipo).
     */
    public function pending(): JsonResponse
    {
        $employees = Employee::query()
            ->needsSetup()
            ->where('status', EmployeeStatus::Active)
            ->orderBy('first_name')
            ->get(['id', 'employee_code', 'first_name', 'last_name', 'email', 'office_id', 'shift_id', 'area_id', 'employment_type', 'contract_ends_on']);

        return response()->json(['count' => $employees->count(), 'employees' => $employees]);
    }

    public function organize(Request $request, ManageAppAccess $appAccess): JsonResponse
    {
        $data = $request->validate([
            'employees' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'employees.*.id' => ['required', 'integer', 'distinct', TenantRule::exists('employees')],
            'employees.*.office_id' => ['required', TenantRule::exists('offices')],
            'employees.*.shift_id' => ['required', TenantRule::exists('shifts')],
            'employees.*.area_id' => ['required', TenantRule::exists('areas')],
            'employees.*.employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'employees.*.contract_ends_on' => ['nullable', 'date', 'required_if:employees.*.employment_type,temporary'],
            'employees.*.app_access' => ['boolean'],
            'employees.*.email' => ['nullable', 'email', 'max:190', 'required_if:employees.*.app_access,true', 'distinct'],
        ], [
            'employees.*.office_id.required' => 'Elige la oficina de todos los seleccionados.',
            'employees.*.shift_id.required' => 'Elige el turno de todos los seleccionados.',
            'employees.*.area_id.required' => 'Elige el área de todos los seleccionados.',
            'employees.*.employment_type.required' => 'Elige si son de planta o temporales.',
            'employees.*.contract_ends_on.required_if' => 'Los temporales necesitan fecha de fin de contrato.',
            'employees.*.email.required_if' => 'Escribe el correo de quienes tendrán la app.',
            'employees.*.email.distinct' => 'Hay correos repetidos.',
        ]);

        // El turno debe ser de la oficina elegida
        $shiftOffices = Shift::query()->whereIn('id', array_column($data['employees'], 'shift_id'))->pluck('office_id', 'id');
        foreach ($data['employees'] as $index => $row) {
            if ((int) ($shiftOffices[$row['shift_id']] ?? 0) !== (int) $row['office_id']) {
                throw ValidationException::withMessages(["employees.{$index}.shift_id" => 'El turno debe ser de la oficina elegida.']);
            }
        }

        DB::connection('tenant')->transaction(function () use ($data) {
            foreach ($data['employees'] as $row) {
                $employee = Employee::query()->findOrFail($row['id']);
                $employee->fill([
                    'office_id' => $row['office_id'],
                    'shift_id' => $row['shift_id'],
                    'area_id' => $row['area_id'],
                    'employment_type' => $row['employment_type'],
                    'contract_ends_on' => $row['employment_type'] === EmploymentType::Temporary->value ? $row['contract_ends_on'] : null,
                ])->save();
            }
        });

        // El acceso a la app manda correo: va después de guardar
        $withApp = 0;
        foreach ($data['employees'] as $row) {
            if (! empty($row['app_access']) && ! empty($row['email'])) {
                $employee = Employee::query()->findOrFail($row['id']);
                $appAccess->enable($employee, $row['email']);
                $employee->forceFill(['email' => $row['email']])->save();
                $withApp++;
            }
        }

        ActivityLogger::log('updated', description: 'Organizó '.count($data['employees']).' empleados (carga masiva)');

        return response()->json([
            'organized' => count($data['employees']),
            'with_app' => $withApp,
            'pending' => Employee::query()->needsSetup()->where('status', EmployeeStatus::Active)->count(),
        ]);
    }
}
