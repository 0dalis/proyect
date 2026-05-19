<div id="roleModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-neutral-900/60 transition-opacity duration-300 backdrop-blur-xs"></div>
    <div class="flex min-h-screen items-center justify-center p-4 text-center">
        <div id="roleModalBox" class="relative w-full max-w-md sm:max-w-lg transform rounded-xl bg-white p-5 text-left align-middle shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 border border-neutral-200">

            <!-- Cabecera -->
            <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                <h3 id="roleModalTitle" class="text-lg font-semibold text-neutral-900">Agregar Rol</h3>
                <button type="button" id="closeRoleModal" class="text-neutral-400 bg-transparent hover:bg-neutral-100 hover:text-neutral-700 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Cerrar</span>
                </button>
            </div>
            <!-- Cuerpo del modal (formulario) -->
            <form id="roleForm">
                @csrf
                <input type="hidden" id="roleId" name="roleId">
                <input type="hidden" id="actionRole" name="actionRole">
                <div class="space-y-4 py-5">
                    <div>
                        <label for="roleName" class="block text-sm font-medium text-neutral-700 mb-1">Nombre del Rol</label>
                        <input type="text" id="roleName" name="roleName" class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-2">Permisos:</label>
                        <p class="mt-1 text-xs text-neutral-500">Selecciona los permisos que deseas asignar</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-64 overflow-y-auto border border-neutral-200 rounded-lg bg-neutral-50 p-3">
                            @foreach($permissions as $permission)
                            <label class="relative flex items-center p-2 rounded-md cursor-pointer transition-all duration-200 hover:bg-neutral-100 has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-200 border border-transparent">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="w-4 h-4 text-indigo-600 rounded border-neutral-300 focus:ring-indigo-500 focus:ring-2 transition">
                                <span class="ml-3 text-sm text-neutral-800 select-none">{{ $permission->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Footer con botones -->
                <div class="flex items-center justify-end border-t border-neutral-100 space-x-3 pt-4">
                    <button type="button" id="closeRoleModalBtn" class="text-neutral-700 bg-white border border-neutral-300 hover:bg-neutral-50 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="saveRoleBtn" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    function openRoleModal(action = 'create', id = '', name = '', selectedPermissions = []) {
        $('#roleModalTitle').text(action === 'update' ? 'Editar Rol' : 'Agregar Rol');
        $('#roleId').val(id);
        $('#roleName').val(name);
        $('#actionRole').val(action);

        // Desmarcar todos
        $('#roleForm input[name="permissions[]"]').prop('checked', false);

        // Marcar los seleccionados si es update
        if(selectedPermissions.length > 0) {
            selectedPermissions.forEach(function(pid) {
                $('#roleForm input[name="permissions[]"][value="' + pid + '"]').prop('checked', true);
            });
        }

        $('#roleModal').removeClass('hidden');
        $('body').addClass('overflow-hidden');
        setTimeout(function() {
            $('#roleModalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    }

    function closeRoleModal() {
        $('#roleModalBox').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(function() {
            $('#roleModal').addClass('hidden');
            $('body').removeClass('overflow-hidden');
        }, 300);
    }

    // Abrir modal para crear rol
    $('#openRoleModal').on('click', function(e){
        e.preventDefault();
        openRoleModal('create');
    });

    // Abrir modal para editar rol
    $(document).on('click', '.editRole', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var permissions = $(this).data('permissions').toString().split(','); // convertir a array
        openRoleModal('update', id, name, permissions);
    });

    $('#closeRoleModal, #closeRoleModalBtn').on('click', closeRoleModal);

</script>
@endpush
