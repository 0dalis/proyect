<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Roles del sistema') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permisos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($roles as $role)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $role->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $role->permissions->pluck('name')->join(', ') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                        <!-- Editar rol -->
                                        <a href="#" data-action="update"
                                                class="editRole inline-flex items-center px-2 py-1 text-indigo-600 hover:text-indigo-900"
                                                data-id="{{ $role->id }}"
                                                data-name="{{ $role->name }}"
                                                data-permissions="{{ $role->permissions->pluck('id')->join(',') }}"
                                                data-tippy-content="Editar Rol">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <!-- Eliminar rol -->
                                        <a href="#" data-action="delete"
                                                class="deleteRole inline-flex items-center px-2 py-1 text-red-600 hover:text-red-900"
                                                data-id="{{ $role->id }}"
                                                data-name="{{ $role->name }}"
                                                data-tippy-content="Eliminar Rol">
                                            <i class="bi bi-trash-fill"></i>
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
    <!-- Botón flotante para agregar un nuevo rol -->
    <a href="#" id="openRoleModal" data-action="create"
    class="fixed bottom-6 right-6 inline-flex items-center px-4 py-3 bg-blue-600 text-white text-sm font-medium rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none"
    data-tippy-content="Crear Rol">
        <i class="bi bi-plus-lg mr-2 text-lg"></i>
        Crear Rol
    </a>
    @include('system/rolandpermision/role-modal')
</x-app-layout>
