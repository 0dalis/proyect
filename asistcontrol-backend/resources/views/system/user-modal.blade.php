<!-- Modal Usuario -->
<div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="flex min-h-screen items-center justify-center p-4">
        <div id="modalBox"
             class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl p-6 scale-95 opacity-0 transition-all duration-300">
            <div class="flex items-center justify-between border-b pb-4">
                <h3 id="modalTitle"
                    class="text-lg font-semibold text-gray-800">
                    Crear Usuario
                </h3>
                <button type="button"
                        id="closeModal"
                        class="text-gray-400 hover:text-gray-700">

                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>
            <form id="userForm">
                @csrf
                <input type="hidden" id="userId" name="userId">
                <input type="hidden" id="actionUser" name="actionUser">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-5">
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Nombre
                        </label>
                        <input type="text"
                               id="first_name"
                               name="first_name"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Apellido
                        </label>

                        <input type="text"
                               id="last_name"
                               name="last_name"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Email
                        </label>

                        <input type="email"
                               id="email"
                               name="email"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Código Empleado
                        </label>

                        <input type="text"
                               id="employee_code"
                               name="employee_code"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Password
                        </label>

                        <input type="password"
                               id="password"
                               name="password"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- PIN -->
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            PIN
                        </label>

                        <input type="text"
                               id="pin"
                               name="pin"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Estado
                        </label>

                        <select id="is_active"
                                name="is_active"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">

                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>

                        </select>
                    </div>

                </div>
                <div class="mt-2">

                    <label class="block text-sm font-medium mb-2">
                        Roles
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-64 overflow-y-auto border border-neutral-200 rounded-lg bg-neutral-50 p-3">

                        @foreach($roles as $role)
                            <label class="relative flex items-center p-2 rounded-md cursor-pointer transition-all duration-200 hover:bg-neutral-100 has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-200 border border-transparent">
                                <input type="checkbox"
                                       name="roles[]"
                                       value="{{ $role->id }}"
                                       class="role-checkbox w-4 h-4 text-indigo-600 rounded border-neutral-300 focus:ring-indigo-500">
                                <span class="ml-3 text-sm text-neutral-800 select-none">
                                    {{ $role->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t pt-4 mt-6">
                    <button type="button"
                            id="closeModalBtn"
                            class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button type="submit"
                            id="saveUserBtn"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>

$(document).ready(function () {
    $('#openUserModal').on('click', function () {

        $('#userForm')[0].reset();

        $('#modalTitle').text('Crear Usuario');

        $('#actionUser').val('create');

        $('#userId').val('');

        $('#userModal').removeClass('hidden');

        setTimeout(() => {

            $('#modalBox')
                .removeClass('scale-95 opacity-0')
                .addClass('scale-100 opacity-100');

        }, 10);
    });
    $(document).on('click', '.editUser', function () {

        $('#modalTitle').text('Editar Usuario');

        $('#actionUser').val('update');

        $('#userId').val($(this).data('id'));

        $('#first_name').val($(this).data('first_name'));

        $('#last_name').val($(this).data('last_name'));

        $('#email').val($(this).data('email'));

        $('#employee_code').val($(this).data('employee_code'));

        $('#is_active').val($(this).data('is_active'));

        $('#userModal').removeClass('hidden');

        setTimeout(() => {

            $('#modalBox')
                .removeClass('scale-95 opacity-0')
                .addClass('scale-100 opacity-100');

        }, 10);
    });
    $('#closeModal, #closeModalBtn').on('click', function () {

        $('#modalBox')
            .removeClass('scale-100 opacity-100')
            .addClass('scale-95 opacity-0');

        setTimeout(() => {

            $('#userModal').addClass('hidden');

        }, 200);
    });

});
</script>
@endpush