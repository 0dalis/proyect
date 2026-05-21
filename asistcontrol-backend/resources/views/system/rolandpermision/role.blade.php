<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Roles del sistema') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permisos</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($roles as $role)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $role->name }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @foreach($role->permissions as $permission)
                                            <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-medium mr-1 mb-0.5 px-2 py-0.5 rounded">
                                                {{ $permission->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right space-x-1">
                                        <!-- Editar rol -->
                                        <a href="#" data-action="update"
                                                class="editRole inline-flex items-center px-2 py-1 text-sm text-indigo-600 hover:text-indigo-900"
                                                data-id="{{ $role->id }}"
                                                data-name="{{ $role->name }}"
                                                data-permissions="{{ $role->permissions->pluck('id')->join(',') }}"
                                                data-tippy-content="Editar Rol">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <!-- Eliminar rol -->
                                        <a href="#" data-action="delete"
                                                class="deleteRole inline-flex items-center px-2 py-1 text-sm text-red-600 hover:text-red-900"
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
@push('scripts')
<script>
    $(document).on('click', '.deleteRole', function(e){
        e.preventDefault();

        var roleId = $(this).data('id');
        var roleName = $(this).data('name');

        // Confirmación con SweetAlert
        Swal.fire({
            title: `¿Eliminar el rol "${roleName}"?`,
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if(result.isConfirmed) {

                // Mostrar "Procesando..."
                Swal.fire({
                    title: 'Procesando...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });

                // AJAX para eliminar
                $.ajax({
                    url: "{{ url('roles/delete') }}/" + roleId, // Ajusta tu ruta
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response){
                        Swal.close(); // cerrar el "Procesando..."

                        // Toastify
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: response.success ? "#4fbe87" : "#e74c3c",
                        }).showToast();

                        if(response.success){
                            // Remover la fila de la tabla
                            $(`.deleteRole[data-id='${roleId}']`).closest('tr').remove();
                        }
                    },
                    error: function(xhr){
                        Swal.close();
                        let message = "Error al eliminar el rol";
                        if(xhr.responseJSON && xhr.responseJSON.message){
                            message = xhr.responseJSON.message;
                        }
                        Toastify({
                            text: message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#e74c3c",
                        }).showToast();
                    }
                });
            }
        });
    });
</script>
@endpush
</x-app-layout>
