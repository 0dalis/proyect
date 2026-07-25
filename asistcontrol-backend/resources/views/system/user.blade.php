<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 text-gray-900">

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $user->email }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $user->company ? $user->company->name : '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @foreach($user->roles as $role)
                                            <span class="inline-block px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @if($user->is_active)
                                            <span class="inline-block px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded-full">Activo</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 text-xs bg-red-100 text-red-800 rounded-full">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <a href="#"
                                        data-action="update"
                                        class="editUser inline-flex items-center px-2 py-1 text-sm text-indigo-600 hover:text-indigo-900"
                                        data-id="{{ $user->id }}"
                                        data-email="{{ $user->email }}"
                                        data-is_active="{{ $user->is_active }}"
                                        data-company_name="{{ $user->company ? $user->company->name : 'Sin empresa' }}"
                                        data-roles='@json($user->roles->pluck("id"))'>
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
        id="openUserModal"
        data-action="create"
        class="fixed bottom-6 right-6 inline-flex items-center px-4 py-3 bg-blue-600 text-white text-sm font-medium rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none z-40">

        <i class="bi bi-plus-lg mr-2 text-lg"></i>
        Crear Usuario
    </a>
    @include('system.user-modal')
</x-app-layout>
