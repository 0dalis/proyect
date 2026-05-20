<!-- Modal Usuario -->
<div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto">

    <!-- Fondo -->
    <div id="modalBackdrop"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm">
    </div>

    <div class="flex min-h-screen items-center justify-center p-4">

        <div id="modalBox"
             class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl p-6 scale-95 opacity-0 transition-all duration-300">

            <!-- Header -->
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

            <!-- Form -->
            <form id="userForm">

                @csrf

                <input type="hidden" id="userId" name="userId">
                <input type="hidden" id="actionUser" name="actionUser">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-5">

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Nombre
                        </label>

                        <input type="text"
                               id="first_name"
                               name="first_name"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Apellido -->
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Apellido
                        </label>

                        <input type="text"
                               id="last_name"
                               name="last_name"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Email
                        </label>

                        <input type="email"
                               id="email"
                               name="email"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Código -->
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Código Empleado
                        </label>

                        <input type="text"
                               id="employee_code"
                               name="employee_code"
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

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Password
                        </label>

                        <input type="password"
                               id="password"
                               name="password"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Activo -->
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

                <!-- Footer -->
                <div class="flex justify-end space-x-3 border-t pt-4">

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
const userModal = document.getElementById('userModal');
const modalBox = document.getElementById('modalBox');

const openUserModal = document.getElementById('openUserModal');

const closeModal = document.getElementById('closeModal');
const closeModalBtn = document.getElementById('closeModalBtn');

const modalTitle = document.getElementById('modalTitle');

const userForm = document.getElementById('userForm');

function showModal()
{
    userModal.classList.remove('hidden');

    setTimeout(() => {
        modalBox.classList.remove('scale-95', 'opacity-0');
        modalBox.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function hideModal()
{
    modalBox.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        userModal.classList.add('hidden');
    }, 200);
}

openUserModal.addEventListener('click', function () {

    userForm.reset();

    modalTitle.innerText = 'Crear Usuario';

    document.getElementById('actionUser').value = 'create';

    document.getElementById('userId').value = '';

    showModal();
});

document.querySelectorAll('.editUser').forEach(button => {

    button.addEventListener('click', function () {

        modalTitle.innerText = 'Editar Usuario';

        document.getElementById('actionUser').value = 'update';

        document.getElementById('userId').value = this.dataset.id;

        document.getElementById('first_name').value = this.dataset.first_name;

        document.getElementById('last_name').value = this.dataset.last_name;

        document.getElementById('email').value = this.dataset.email;

        document.getElementById('employee_code').value = this.dataset.employee_code;

        document.getElementById('pin').value = this.dataset.pin;

        document.getElementById('is_active').value = this.dataset.is_active;

        showModal();
    });
});

closeModal.addEventListener('click', hideModal);

closeModalBtn.addEventListener('click', hideModal);

</script>
@endpush