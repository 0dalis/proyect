<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use App\Models\Company;
use App\Models\Office;
use App\Models\Area;
use Illuminate\Routing\Controllers\HasMiddleware;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth'];
    }

    public function index()
    {
        $employees = Employee::with(['user', 'user.roles', 'company', 'office', 'area'])->get();
        $companies = Company::where('is_active', true)->get();
        $offices = Office::where('is_active', true)->get();
        $areas = Area::where('is_active', true)->get();
        $roles = Role::all();

        return view('system.employee', compact('employees', 'companies', 'offices', 'areas', 'roles'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'is_active'       => $request->has('is_active') ? 1 : 0,
            'has_system_access' => $request->has('has_system_access') ? 1 : 0,
        ]);

        $employeeRules = [
            'company_id'    => 'required|exists:companies,id',
            'office_id'     => 'nullable|exists:offices,id',
            'area_id'       => 'nullable|exists:areas,id',
            'employee_code' => 'required|string|max:50|unique:employees,employee_code',
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'pin'           => 'nullable|string|min:4',
            'is_active'     => 'required|boolean',
            'has_system_access' => 'required|boolean',
        ];

        if ($request->has_system_access) {
            $employeeRules['email'] = 'required|email|unique:users,email';
            $employeeRules['password'] = 'nullable|string|min:6';
            $employeeRules['roles'] = 'nullable|array';
        }

        $request->validate($employeeRules);

        $userId = null;

        if ($request->has_system_access) {
            $user = User::create([
                'company_id' => $request->company_id,
                'email'      => $request->email,
                'password'   => $request->password,
                'is_active'  => $request->is_active,
            ]);

            if ($request->filled('roles')) {
                $rolesIds = array_map('intval', $request->roles);
                $user->syncRoles($rolesIds);
            }

            $userId = $user->id;
        }

        Employee::create([
            'company_id'    => $request->company_id,
            'office_id'     => $request->office_id,
            'area_id'       => $request->area_id,
            'user_id'       => $userId,
            'employee_code' => $request->employee_code,
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'pin'           => $request->pin,
            'is_active'     => $request->is_active,
        ]);

        return response()->json(['success' => true, 'message' => 'Empleado creado correctamente']);
    }

    public function update(Request $request)
    {
        $employee = Employee::findOrFail($request->employeeid);

        $request->merge([
            'is_active'         => $request->has('is_active') ? 1 : 0,
            'has_system_access' => $request->has('has_system_access') ? 1 : 0,
        ]);

        $employeeRules = [
            'company_id'    => 'required|exists:companies,id',
            'office_id'     => 'nullable|exists:offices,id',
            'area_id'       => 'nullable|exists:areas,id',
            'employee_code' => 'required|string|max:50|unique:employees,employee_code,' . $employee->id,
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'pin'           => 'nullable|string|min:4',
            'is_active'     => 'required|boolean',
            'has_system_access' => 'required|boolean',
        ];

        if ($request->has_system_access) {
            $emailUnique = 'unique:users,email';
            if ($employee->user) {
                $emailUnique .= ',' . $employee->user->id;
            }
            $employeeRules['email'] = 'required|email|' . $emailUnique;
            $employeeRules['password'] = 'nullable|string|min:6';
            $employeeRules['roles'] = 'nullable|array';
        }

        $request->validate($employeeRules);

        if ($request->has_system_access) {
            if ($employee->user) {
                $updateUserData = [
                    'email'      => $request->email,
                    'is_active'  => $request->is_active,
                    'company_id' => $request->company_id,
                ];
                if ($request->filled('password')) {
                    $updateUserData['password'] = bcrypt($request->password);
                }
                $employee->user->update($updateUserData);

                if ($request->has('roles')) {
                    $rolesIds = is_array($request->roles) ? array_map('intval', $request->roles) : [];
                    $employee->user->syncRoles($rolesIds);
                }
            } else {
                $user = User::create([
                    'company_id' => $request->company_id,
                    'email'      => $request->email,
                    'password'   => $request->password,
                    'is_active'  => $request->is_active,
                ]);

                if ($request->filled('roles')) {
                    $rolesIds = array_map('intval', $request->roles);
                    $user->syncRoles($rolesIds);
                }

                $employee->user_id = $user->id;
            }
        } else {
            if ($employee->user) {
                $employee->user_id = null;
            }
        }

        $employee->update([
            'company_id'    => $request->company_id,
            'office_id'     => $request->office_id,
            'area_id'       => $request->area_id,
            'user_id'       => $employee->user_id,
            'employee_code' => $request->employee_code,
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'pin'           => $request->pin,
            'is_active'     => $request->is_active,
        ]);

        return response()->json(['success' => true, 'message' => 'Empleado actualizado correctamente']);
    }
}
