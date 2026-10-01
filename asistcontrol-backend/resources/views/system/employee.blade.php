<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Empleados') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 text-gray-900">

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sucursal</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Área</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acceso</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($employees as $employee)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $employee->full_name }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $employee->employee_code }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $employee->user ? $employee->user->email : '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $employee->company ? $employee->company->name : '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $employee->office ? $employee->office->name : '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $employee->area ? $employee->area->name : '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @if($employee->hasSystemAccess())
                                            <span class="inline-block px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">Sistema</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">Kiosco</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @if($employee->is_active)
                                            <span class="inline-block px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded-full">Activo</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 text-xs bg-red-100 text-red-800 rounded-full">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <a href="#"
                                        data-action="update"
                                        class="editEmployee inline-flex items-center px-2 py-1 text-sm text-indigo-600 hover:text-indigo-900"
                                        data-id="{{ $employee->id }}"
                                        data-first_name="{{ $employee->first_name }}"
                                        data-last_name="{{ $employee->last_name }}"
                                        data-employee_code="{{ $employee->employee_code }}"
                                        data-company_id="{{ $employee->company_id }}"
                                        data-office_id="{{ $employee->office_id }}"
                                        data-area_id="{{ $employee->area_id }}"
                                        data-is_active="{{ $employee->is_active }}"
                                        data-has_system_access="{{ $employee->hasSystemAccess() ? 1 : 0 }}"
                                        data-user_email="{{ $employee->user ? $employee->user->email : '' }}"
                                        data-user_roles='@json($employee->user ? $employee->user->roles->pluck("id") : [])'>
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <a href="#"
        id="openEmployeeModal"
        data-action="create"
        class="fixed bottom-6 right-6 inline-flex items-center px-4 py-3 bg-emerald-600 text-white text-sm font-medium rounded-full shadow-lg hover:bg-emerald-700 focus:outline-none z-40">

        <i class="bi bi-plus-lg mr-2 text-lg"></i>
        Crear Empleado
    </a>
    @include('system.employee-modal')
</x-app-layout>
