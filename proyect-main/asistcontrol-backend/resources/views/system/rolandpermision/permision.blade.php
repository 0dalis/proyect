<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Permisos del sistema') }}
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
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de creación</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($permissions as $permission)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $permission->name }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $permission->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <a href="#" data-action="update"
                                        class="editPermission inline-flex items-center px-2 py-1 text-sm text-indigo-600 hover:text-indigo-900"
                                        data-id="{{ $permission->id }}"
                                        data-name="{{ $permission->name }}"
                                        data-tippy-content="Editar Permiso">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="#" class="deletePermission inline-flex items-center px-2 py-1 text-sm text-red-600 hover:text-red-900"
                                            data-id="{{ $permission->id }}"
                                            data-name="{{ $permission->name }}">
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
    <!-- Botón flotante -->
<a href="#" id="openPermissionModal" data-action="create"
   class="fixed bottom-6 right-6 inline-flex items-center px-4 py-3 bg-blue-600 text-white text-sm font-medium rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none"
   data-tippy-content="Crear Permiso">
    <i class="bi bi-plus-lg mr-2 text-lg"></i>
    Crear Permiso
</a>

<!-- Incluir el modal -->
@include('system/rolandpermision/permission-modal')
@push('scripts')
<script>
$(document).ready(function() {
    var $modal = $('#permissionModal');
    var $modalBox = $('#modalBox');
    function openModal(action = 'create', id = '', name = '') {
        var title = action === 'update' ? 'Editar Permiso' : 'Agregar Permiso';
        $('#modalTitle').text(title);

        $('#permissionId').val(id);
        $('#name').val(name);
        $('#actionpermission').val(action);
        $modal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
        setTimeout(function() {
            $modalBox.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    }
    function closeModal() {
        $modalBox.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(function() {
            $modal.addClass('hidden');
            $('body').removeClass('overflow-hidden');
        }, 300);
    }
    $('#openPermissionModal').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('action'); // obtener 'create'
        openModal(action);
    });
    $('.editPermission').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var action = $(this).data('action') || 'update'; // Por si acaso
        openModal(action, id, name);
    });
    $('#closeModal, #closeModalBtn').on('click', closeModal);
    $('#permissionForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#savePermissionBtn');

        var action = $('#actionpermission').val(); // 'create' o 'update'
        var id = $('#permissionId').val();
        var name = $('#name').val();

        var url = action === 'create'
            ? "{{ route('permissions.store') }}"
            : "{{ route('permissions.update') }}";

        // BLOQUEAR botón y mostrar cargando
        $btn.prop('disabled', true);
        var originalText = $btn.html();
        $btn.html('<i class="bi bi-arrow-clockwise animate-spin mr-2"></i>Guardando...');
        if (window.AppLoader) AppLoader.show('Guardando permiso...');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                permissionId: id,
                name: name
            },
            success: function(response) {
                if (window.AppLoader) AppLoader.hide();
                closeModal();

                Toastify({
                    text: response.message,
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#4fbe87",
                }).showToast();

                location.reload();
            },
            error: function(xhr) {
                if (window.AppLoader) AppLoader.hide();
                var message = "Error al procesar la solicitud";
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Toastify({
                    text: message,
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#e74c3c",
                }).showToast();
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.html(originalText);
            }
        });
    });
});
$(document).on('click', '.deletePermission', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    var name = $(this).data('name');
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminará el permiso "${name}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            Swal.showLoading();
            return $.ajax({
                url: "{{ route('permissions.destroy') }}",
                method: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}",
                    permissionId: id,
                }
            }).then(response => {
                return response;
            }).catch(error => {
                Swal.hideLoading();
                throw error.responseJSON || error;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            var response = result.value;

            if (response.success) {
                Toastify({
                    text: response.message,
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#4fbe87",
                }).showToast();
                location.reload();
            } else {
                Toastify({
                    text: response.message || 'Error al eliminar',
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#e74c3c",
                }).showToast();
            }
        }
    });
});
</script>
@endpush
</x-app-layout>
