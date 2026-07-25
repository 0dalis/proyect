<!-- Modal Empleado -->
<div id="employeeModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-neutral-900/60 transition-opacity duration-300 backdrop-blur-sm"></div>
    <div class="flex min-h-screen items-center justify-center p-4">
        <div id="employeeModalBox"
             class="relative w-full max-w-2xl transform rounded-xl bg-white p-5 text-left align-middle shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 border border-neutral-200">
            <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                <h3 id="employeeModalTitle" class="text-lg font-semibold text-neutral-900">Crear Empleado</h3>
                <button type="button" id="closeEmployeeModal"
                        class="text-neutral-400 bg-transparent hover:bg-neutral-100 hover:text-neutral-700 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Cerrar</span>
                </button>
            </div>

            <form id="employeeForm">
                @csrf
                <input type="hidden" id="employeeid" name="employeeid">
                <input type="hidden" id="actionEmployee" name="actionEmployee">

                <div class="space-y-4 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="emp_first_name" class="block text-sm font-medium text-neutral-700 mb-1">Nombre</label>
                            <input type="text" id="emp_first_name" name="first_name"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="emp_last_name" class="block text-sm font-medium text-neutral-700 mb-1">Apellido</label>
                            <input type="text" id="emp_last_name" name="last_name"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="emp_employee_code" class="block text-sm font-medium text-neutral-700 mb-1">Código Empleado</label>
                            <input type="text" id="emp_employee_code" name="employee_code"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="emp_pin" class="block text-sm font-medium text-neutral-700 mb-1">PIN</label>
                            <input type="text" id="emp_pin" name="pin"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="emp_company_id" class="block text-sm font-medium text-neutral-700 mb-1">Empresa</label>
                            <select id="emp_company_id" name="company_id"
                                    class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                                <option value="" disabled selected>Seleccione una empresa</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="emp_office_id" class="block text-sm font-medium text-neutral-700 mb-1">Sucursal</label>
                            <select id="emp_office_id" name="office_id"
                                    class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                                <option value="">Sin sucursal</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="emp_area_id" class="block text-sm font-medium text-neutral-700 mb-1">Área</label>
                            <select id="emp_area_id" name="area_id"
                                    class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                                <option value="">Sin área</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Switch Acceso al Sistema -->
                    <div class="flex items-center justify-between bg-neutral-50 rounded-lg p-4 border border-neutral-200">
                        <div>
                            <span class="text-sm font-medium text-neutral-800">Acceso al sistema</span>
                            <p class="text-xs text-neutral-500 mt-0.5">Requiere email, contraseña y roles</p>
                        </div>
                        <label for="emp_has_system_access" class="relative cursor-pointer flex items-center">
                            <input type="checkbox" id="emp_has_system_access" name="has_system_access" class="sr-only peer">
                            <div class="w-12 h-6 bg-neutral-200 rounded-full peer-checked:bg-emerald-600 transition-colors duration-300 relative shadow-inner">
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-300 peer-checked:translate-x-6"></div>
                            </div>
                        </label>
                    </div>

                    <!-- Campos de Usuario (ocultos por defecto) -->
                    <div id="systemAccessFields" class="hidden border border-emerald-200 bg-emerald-50/30 rounded-lg p-4 space-y-4">
                        <p class="text-sm font-medium text-emerald-800">Datos de acceso al sistema</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="emp_email" class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                                <input type="email" id="emp_email" name="email"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                            </div>
                            <div>
                                <label for="emp_password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                                <input type="password" id="emp_password" name="password"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-emerald-600 focus:ring-emerald-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-2">Roles</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-neutral-200 rounded-lg bg-white p-3">
                                @foreach($roles as $role)
                                    <label class="relative flex items-center p-2 rounded-md cursor-pointer transition-all duration-200 hover:bg-neutral-100 has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-200 border border-transparent">
                                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                               class="emp-role-checkbox w-4 h-4 text-emerald-600 rounded border-neutral-300 focus:ring-emerald-500 focus:ring-2 transition">
                                        <span class="ml-3 text-sm text-neutral-800 select-none">{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="flex justify-end items-center space-x-3 text-sm pt-2">
                        <label class="text-sm font-medium text-neutral-700 cursor-pointer">Estado</label>
                        <div class="relative cursor-pointer flex items-center space-x-1">
                            <span id="emp-label-off" class="text-[10px] text-neutral-500 select-none">inactivo</span>
                            <input type="checkbox" id="emp_is_active" name="is_active" class="sr-only peer" checked>
                            <div class="w-12 h-6 bg-neutral-200 rounded-full peer-checked:bg-emerald-600 transition-colors duration-300 relative shadow-inner">
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-300 emp-toggle-circle"></div>
                            </div>
                            <span id="emp-label-on" class="text-[10px] text-neutral-500 select-none">activo</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-neutral-100 space-x-3 pt-4">
                    <button type="button" id="closeEmployeeModalBtn"
                            class="text-neutral-700 bg-white border border-neutral-300 hover:bg-neutral-50 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="saveEmployeeBtn"
                            class="text-white bg-emerald-600 hover:bg-emerald-700 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="saveEmployeeBtnText">Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
$(document).ready(function () {

    const $empToggle = $('#emp_is_active');
    const $empLabelOn = $('#emp-label-on');
    const $empLabelOff = $('#emp-label-off');
    const $empCircle = $('.emp-toggle-circle');
    const $sysAccessToggle = $('#emp_has_system_access');
    const $sysAccessFields = $('#systemAccessFields');

    function syncEmpToggleVisual() {
        if ($empToggle.prop('checked')) {
            $empCircle.addClass('translate-x-6');
            $empLabelOn.addClass('font-semibold text-emerald-600');
            $empLabelOff.removeClass('font-semibold text-red-500');
        } else {
            $empCircle.removeClass('translate-x-6');
            $empLabelOff.addClass('font-semibold text-red-500');
            $empLabelOn.removeClass('font-semibold text-emerald-600');
        }
    }

    function toggleSystemAccessFields() {
        if ($sysAccessToggle.is(':checked')) {
            $sysAccessFields.removeClass('hidden');
        } else {
            $sysAccessFields.addClass('hidden');
        }
    }

    syncEmpToggleVisual();
    $empToggle.on('change', syncEmpToggleVisual);
    $sysAccessToggle.on('change', toggleSystemAccessFields);

    // ========== ABRIR MODAL CREAR ==========
    $('#openEmployeeModal').on('click', function (e) {
        e.preventDefault();
        $('#employeeForm')[0].reset();
        $('#employeeModalTitle').text('Crear Empleado');
        $('#actionEmployee').val('create');
        $('#employeeid').val('');

        $('#emp_company_id').val('');
        $('#emp_office_id').val('');
        $('#emp_area_id').val('');

        $empToggle.prop('checked', true).trigger('change');
        $sysAccessToggle.prop('checked', false).trigger('change');

        $('#employeeModal').removeClass('hidden');
        setTimeout(() => {
            $('#employeeModalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    });

    // ========== ABRIR MODAL EDITAR ==========
    $(document).on('click', '.editEmployee', function (e) {
        e.preventDefault();
        const $btn = $(this);

        $('#employeeForm')[0].reset();
        $('#employeeModalTitle').text('Editar Empleado');
        $('#actionEmployee').val('update');
        $('#employeeid').val($btn.data('id'));

        $('#emp_first_name').val($btn.data('first_name'));
        $('#emp_last_name').val($btn.data('last_name'));
        $('#emp_employee_code').val($btn.data('employee_code'));
        $('#emp_company_id').val($btn.data('company_id'));
        $('#emp_office_id').val($btn.data('office_id'));
        $('#emp_area_id').val($btn.data('area_id'));

        const isActive = parseInt($btn.data('is_active')) === 1;
        $empToggle.prop('checked', isActive).trigger('change');

        const hasAccess = parseInt($btn.data('has_system_access')) === 1;
        $sysAccessToggle.prop('checked', hasAccess).trigger('change');

        $('#emp_email').val($btn.data('user_email'));

        $('.emp-role-checkbox').prop('checked', false);
        const roles = $btn.data('user_roles');
        if (roles && roles.length) {
            roles.forEach(id => $('.emp-role-checkbox[value="'+id+'"]').prop('checked', true));
        }

        $('#employeeModal').removeClass('hidden');
        setTimeout(() => {
            $('#employeeModalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    });

    // ========== CERRAR MODAL ==========
    $('#closeEmployeeModal, #closeEmployeeModalBtn').on('click', function () {
        $('#employeeModalBox').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#employeeModal').addClass('hidden');
        }, 200);
    });

    // ========== ENVÍO AJAX ==========
    $('#employeeForm').on('submit', function(e) {
        e.preventDefault();

        const action = $('#actionEmployee').val();
        const url = action === 'create' ? "{{ route('employees.store') }}" : "{{ route('employees.update') }}";
        const method = 'POST';

        const data = $(this).serialize();
        const $btn = $('#saveEmployeeBtn');
        const $btnText = $('#saveEmployeeBtnText');

        $btn.prop('disabled', true);
        $btnText.html('<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Guardando...');
        if (window.AppLoader) AppLoader.show('Guardando empleado...');

        $.ajax({
            url: url,
            type: method,
            data: data,
            dataType: 'json',
            success: function(response) {
                if (window.AppLoader) AppLoader.hide();
                Toastify({
                    text: response.message || 'Empleado guardado correctamente',
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#10B981"
                }).showToast();

                setTimeout(() => location.reload(), 1000);
            },
            error: function(xhr) {
                if (window.AppLoader) AppLoader.hide();
                $btn.prop('disabled', false);
                $btnText.html('Guardar');

                let errorMessage = 'Error al guardar el empleado';
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
                    backgroundColor: "#EF4444"
                }).showToast();
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
