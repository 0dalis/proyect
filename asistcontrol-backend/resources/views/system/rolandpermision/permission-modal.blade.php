<div id="permissionModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div id="modalBackdrop" class="fixed inset-0 bg-neutral-900/60 transition-opacity duration-300 backdrop-blur-xs"></div>
    <div class="flex min-h-screen items-center justify-center p-4 text-center">
        <div id="modalBox" class="relative w-full max-w-md sm:max-w-lg transform rounded-xl bg-white p-5 text-left align-middle shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 border border-neutral-200">

            <!-- Cabecera -->
            <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-neutral-900">Agregar Permiso</h3>
                <button type="button" id="closeModal" class="text-neutral-400 bg-transparent hover:bg-neutral-100 hover:text-neutral-700 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center transition-colors">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Cerrar</span>
                </button>
            </div>

            <!-- Cuerpo del modal (formulario) -->
            <form id="permissionForm">
                @csrf
                <input type="hidden" id="permissionId" name="permissionId">
                <input type="hidden" id="actionpermission" name="actionpermission">
                <div class="space-y-4 py-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-neutral-700 mb-1">Nombre</label>
                        <input type="text" id="name" name="name" class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                    </div>
                </div>

                <!-- Footer con botones -->
                <div class="flex items-center justify-end border-t border-neutral-100 space-x-3 pt-4">
                    <button type="button" id="closeModalBtn" class="text-neutral-700 bg-white border border-neutral-300 hover:bg-neutral-50 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="savePermissionBtn" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
