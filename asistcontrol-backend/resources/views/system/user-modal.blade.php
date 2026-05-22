<!-- Modal Usuario -->
<div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-neutral-900/60 transition-opacity duration-300 backdrop-blur-sm"></div>
    <div class="flex min-h-screen items-center justify-center p-4">
        <div id="modalBox"
             class="relative w-full max-w-3xl transform rounded-xl bg-white p-5 text-left align-middle shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 border border-neutral-200">
            <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-neutral-900">Crear Usuario</h3>
                <button type="button" id="closeModal"
                        class="text-neutral-400 bg-transparent hover:bg-neutral-100 hover:text-neutral-700 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Cerrar</span>
                </button>
            </div>

            <!-- Formulario -->
            <form id="userForm">
                @csrf
                <input type="hidden" id="userid" name="userid">
                <input type="hidden" id="actionUser" name="actionUser">

                <div class="space-y-4 py-5">
                    <!-- Grid de campos -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nombre -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-neutral-700 mb-1">Nombre</label>
                            <input type="text" id="first_name" name="first_name"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <!-- Apellido -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-neutral-700 mb-1">Apellido</label>
                            <input type="text" id="last_name" name="last_name"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                            <input type="email" id="email" name="email"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <!-- Código empleado -->
                        <div>
                            <label for="employee_code" class="block text-sm font-medium text-neutral-700 mb-1">Código Empleado</label>
                            <input type="text" id="employee_code" name="employee_code"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                            <input type="password" id="password" name="password"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <!-- PIN -->
                        <div>
                            <label for="pin" class="block text-sm font-medium text-neutral-700 mb-1">PIN</label>
                            <input type="text" id="pin" name="pin"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <!-- Empresa (ocupa todo el ancho) -->
                        <div class="col-span-1 sm:col-span-2">
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Empresa</label>

                            <!-- Contenedor visible solo en edición (nombre + checkbox) -->
                            <div id="currentCompanyContainer" class="hidden items-center gap-3 mb-2">
                                <span id="currentCompanyName" class="text-sm font-medium text-neutral-700"></span>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" id="changeCompany"
                                           class="h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-neutral-600">Cambiar empresa</span>
                                </label>
                            </div>

                            <!-- Input de código de empresa -->
                            <input type="text" id="company_code" name="company_code"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm"
                                   placeholder="Código de empresa">
                        </div>
                    </div>

                    <!-- Roles -->
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-2">Roles</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-64 overflow-y-auto border border-neutral-200 rounded-lg bg-neutral-50 p-3">
                            @foreach($roles as $role)
                                <label class="relative flex items-center p-2 rounded-md cursor-pointer transition-all duration-200 hover:bg-neutral-100 has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-200 border border-transparent">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                           class="role-checkbox w-4 h-4 text-indigo-600 rounded border-neutral-300 focus:ring-indigo-500 focus:ring-2 transition">
                                    <span class="ml-3 text-sm text-neutral-800 select-none">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Estado (Toggle) -->
                    <div class="flex justify-end items-center space-x-3 text-sm pt-2">
                        <label class="text-sm font-medium text-neutral-700 cursor-pointer">Estado</label>
                        <div class="relative cursor-pointer flex items-center space-x-1">
                            <span id="label-off" class="text-[10px] text-neutral-500 select-none">inactivo</span>
                            <input type="checkbox" id="is_active" name="is_active" class="sr-only peer" checked>
                            <div class="w-12 h-6 bg-neutral-200 rounded-full peer-checked:bg-indigo-600 transition-colors duration-300 relative shadow-inner">
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-300 toggle-circle"></div>
                            </div>
                            <span id="label-on" class="text-[10px] text-neutral-500 select-none">activo</span>
                        </div>
                    </div>
                </div>

                <!-- Footer con botones -->
                <div class="flex items-center justify-end border-t border-neutral-100 space-x-3 pt-4">
                    <button type="button" id="closeModalBtn"
                            class="text-neutral-700 bg-white border border-neutral-300 hover:bg-neutral-50 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="saveUserBtn"
                            class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="saveBtnText">Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
$(document).ready(function () {

    const $toggle = $('#is_active');
    const $labelOn = $('#label-on');
    const $labelOff = $('#label-off');
    const $circle = $('.toggle-circle');
    function syncToggleVisual() {
        if ($toggle.prop('checked')) {
            $circle.addClass('translate-x-6');
            $labelOn.addClass('font-semibold text-indigo-600');
            $labelOff.removeClass('font-semibold text-red-500');
        } else {
            $circle.removeClass('translate-x-6');
            $labelOff.addClass('font-semibold text-red-500');
            $labelOn.removeClass('font-semibold text-indigo-600');
        }
    }
    syncToggleVisual();
    $toggle.on('change', syncToggleVisual);
    $toggle.parent().on('click', function(e) {
        if (!$(e.target).is('#is_active')) {
            $toggle.prop('checked', !$toggle.prop('checked')).trigger('change');
        }
    });

    // ========== ABRIR MODAL CREAR ==========
    $('#openUserModal').on('click', function () {
        $('#userForm')[0].reset();
        $('#modalTitle').text('Crear Usuario');
        $('#actionUser').val('create');
        $('#userid').val('');

        // Empresa: ocultar contenedor nombre/checkbox, mostrar input limpio
        $('#currentCompanyContainer').addClass('hidden');
        $('#company_code').show().val('');
        $('#changeCompany').prop('checked', false);

        // Toggle activo por defecto
        $toggle.prop('checked', true);
        syncToggleVisual();

        $('#userModal').removeClass('hidden');
        setTimeout(() => {
            $('#modalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    });

    // ========== ABRIR MODAL EDITAR ==========
    $(document).on('click', '.editUser', function () {
        $('#userForm')[0].reset();
        $('#modalTitle').text('Editar Usuario');
        $('#actionUser').val('update');
        $('#userid').val($(this).data('id'));
        $('#first_name').val($(this).data('first_name'));
        $('#last_name').val($(this).data('last_name'));
        $('#email').val($(this).data('email'));
        $('#employee_code').val($(this).data('employee_code'));

        const isActive = $(this).data('is_active');
        $toggle.prop('checked', isActive);
        syncToggleVisual();

        // Roles
        $('.role-checkbox').prop('checked', false);
        const roles = $(this).data('roles');
        if (roles) {
            roles.forEach(id => $('.role-checkbox[value="'+id+'"]').prop('checked', true));
        }

        // Empresa: mostrar nombre actual + checkbox "Cambiar empresa"
        const companyName = $(this).data('company_name') || 'Sin empresa';
        $('#currentCompanyName').text(companyName).show();   // asegurar visible
        $('#currentCompanyContainer').removeClass('hidden'); // mostrar contenedor
        $('#company_code').hide().val('');                  // ocultar input
        $('#changeCompany').prop('checked', false);

        $('#userModal').removeClass('hidden');
        setTimeout(() => {
            $('#modalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    });

    // ========== CHECKBOX "CAMBIAR EMPRESA" ==========
    $('#changeCompany').on('change', function () {
        if ($(this).is(':checked')) {
            // Ocultar solo el nombre, mostrar input al lado del checkbox
            $('#currentCompanyName').hide();
            $('#company_code').show().focus();
            // El contenedor sigue visible (checkbox permanece)
        } else {
            // Volver a mostrar nombre y ocultar input
            $('#currentCompanyName').show();
            $('#company_code').hide().val('');
        }
    });

    // ========== CERRAR MODAL ==========
    $('#closeModal, #closeModalBtn').on('click', function () {
        $('#modalBox').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#userModal').addClass('hidden');
            $toggle.prop('checked', true);
            syncToggleVisual();
        }, 200);
    });

    // ========== ENVÍO AJAX ==========
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        const action = $('#actionUser').val();
        const url = action === 'create' ? "{{ route('users.store') }}" : "{{ route('users.update') }}";
        const method = action === 'create' ? 'POST' : 'PUT';
        const data = $(this).serialize();
        const $btn = $('#saveUserBtn');
        const $btnText = $('#saveBtnText');

        $btn.prop('disabled', true);
        $btnText.html('<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Guardando...');

        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(response) {
                Toastify({
                    text: response.message || 'Usuario guardado correctamente',
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#10B981",
                }).showToast();
                setTimeout(() => location.reload(), 1000);
            },
            error: function(xhr) {
                let errorMessage = 'Error al guardar el usuario';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                }
                Toastify({
                    text: errorMessage,
                    duration: 5000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#EF4444",
                }).showToast();
                $btn.prop('disabled', false);
                $btnText.html('Guardar');
            }
        });
    });

});
</script>
<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
@endpush
