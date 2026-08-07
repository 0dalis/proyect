    <!-- Modal Plan -->
    <div id="planModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-neutral-900/60 transition-opacity duration-300 backdrop-blur-sm"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div id="planModalBox"
                 class="relative w-full max-w-lg transform rounded-xl bg-white p-5 text-left align-middle shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 border border-neutral-200">

                <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                    <h3 id="planModalTitle" class="text-lg font-semibold text-neutral-900">Nuevo Plan</h3>
                    <button type="button" id="closePlanModal"
                            class="text-neutral-400 bg-transparent hover:bg-neutral-100 hover:text-neutral-700 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>

                <form id="planForm">
                    @csrf
                    <input type="hidden" id="planId" name="plan_id">
                    <input type="hidden" id="actionPlan" name="action">

                    <div class="space-y-4 py-5">
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-neutral-700 mb-1">Nombre</label>
                            <input type="text" id="nombre" name="nombre"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="tipo" class="block text-sm font-medium text-neutral-700 mb-1">Tipo</label>
                            <input type="text" id="tipo" name="tipo"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm"
                                   placeholder="Ej. Básico, Premium...">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="precio" class="block text-sm font-medium text-neutral-700 mb-1">Precio ($)</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                            </div>
                            <div>
                                <label for="iva" class="block text-sm font-medium text-neutral-700 mb-1">IVA (%)</label>
                                <input type="number" id="iva" name="iva" step="0.01" min="0" max="100"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="min_users" class="block text-sm font-medium text-neutral-700 mb-1">Mín. usuarios</label>
                                <input type="number" id="min_users" name="min_users" min="1"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                            </div>
                            <div>
                                <label for="max_users" class="block text-sm font-medium text-neutral-700 mb-1">Máx. usuarios</label>
                                <input type="number" id="max_users" name="max_users" min="1"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm"
                                       placeholder="Vacío = ilimitado">
                            </div>
                        </div>
                        <div>
                            <label for="caracteristicas_text" class="block text-sm font-medium text-neutral-700 mb-1">Características (una por línea)</label>
                            <textarea id="caracteristicas_text" name="caracteristicas_text" rows="5"
                                      class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm"
                                      placeholder="Ej:&#10;Hasta 35 usuarios&#10;3 oficinas con GPS&#10;Foto en check-in/out&#10;Reportes PDF + Excel&#10;Soporte 24h"></textarea>
                        </div>
                        <div>
                            <label for="stripe_price_id" class="block text-sm font-medium text-neutral-700 mb-1">Stripe Price ID</label>
                            <input type="text" id="stripe_price_id" name="stripe_price_id"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm"
                                   placeholder="price_xxxxxxxxxxxxx">
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t border-neutral-100 space-x-3 pt-4">
                        <button type="button" id="closePlanModalBtn"
                                class="text-neutral-700 bg-white border border-neutral-300 hover:bg-neutral-50 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" id="savePlanBtn"
                                class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="savePlanBtnText">Guardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function () {

        // Abrir modal crear
        $('#openPlanesModal').on('click', function () {
            $('#planForm')[0].reset();
            $('#planModalTitle').text('Nuevo Plan');
            $('#actionPlan').val('create');
            $('#planId').val('');

            $('#planModal').removeClass('hidden');
            setTimeout(() => {
                $('#planModalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 10);
        });

        // Abrir modal editar
        $(document).on('click', '.editPlan', function (e) {
            e.preventDefault();
            const $btn = $(this);
            $('#planForm')[0].reset();
            $('#planModalTitle').text('Editar Plan');
            $('#actionPlan').val('update');
            $('#planId').val($btn.data('id'));
            $('#nombre').val($btn.data('nombre'));
            $('#tipo').val($btn.data('tipo'));
            $('#precio').val($btn.data('precio'));
            $('#iva').val($btn.data('iva'));
            $('#min_users').val($btn.data('min_users'));
            $('#max_users').val($btn.data('max_users'));
            $('#caracteristicas_text').val($btn.data('caracteristicas'));
            $('#stripe_price_id').val($btn.data('stripe_price_id'));

            $('#planModal').removeClass('hidden');
            setTimeout(() => {
                $('#planModalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 10);
        });

        // Cerrar modal
        function closePlanModal() {
            $('#planModalBox').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
            setTimeout(() => {
                $('#planModal').addClass('hidden');
            }, 200);
        }

        $('#closePlanModal, #closePlanModalBtn').on('click', closePlanModal);

        // Envío AJAX
        $('#planForm').on('submit', function (e) {
            e.preventDefault();
            const action = $('#actionPlan').val();
            const url = action === 'create' ? "{{ route('planes.store') }}" : "{{ route('planes.update') }}";
            const method = action === 'create' ? 'POST' : 'PUT';
            const data = $(this).serialize();
            const $btn = $('#savePlanBtn');
            const $btnText = $('#savePlanBtnText');

            $btn.prop('disabled', true);
            $btnText.html('<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Guardando...');
            if (window.AppLoader) AppLoader.show('Guardando plan...');

            $.ajax({
                url: url,
                type: method,
                data: data,
                success: function (response) {
                    if (window.AppLoader) AppLoader.hide();
                    Toastify({
                        text: response.message || 'Plan guardado correctamente',
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#10B981",
                    }).showToast();
                    setTimeout(() => location.reload(), 1000);
                },
                error: function (xhr) {
                    if (window.AppLoader) AppLoader.hide();
                    let errorMessage = 'Error al guardar el plan';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
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
    $(document).on('change', '.togglepublic', function () {
        let toggle = $(this);
        let planId = toggle.data('id');
        let newState = toggle.is(':checked') ? 1 : 0;
        let originalState = !newState;
        let statusSpan = toggle.closest('label').find('span');

        // Deshabilitar el toggle y agregar clase visual "gris"
        toggle.prop('disabled', true);
        toggle.addClass('processing'); // Añadido

        $.ajax({
            url: '{{ route('planes.toggle') }}',
            method: 'PATCH',
            data: {
                id: planId,
                public: newState
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    statusSpan.text(newState ? 'Sí' : 'No');
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: newState ? "#10B981" : "#F59E0B",
                    }).showToast();
                } else {
                    toggle.prop('checked', originalState);
                    Toastify({
                        text: response.message || 'Error al actualizar',
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#EF4444",
                    }).showToast();
                }
            },
            error: function (xhr) {
                console.log(xhr);
                toggle.prop('checked', originalState);
                Toastify({
                    text: 'Error de conexión',
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#EF4444",
                }).showToast();
            },
            complete: function () {
                // Esperar 2 segundos antes de volver a habilitar y quitar estilo gris
                setTimeout(function() {
                    toggle.prop('disabled', false);
                    toggle.removeClass('processing'); // Removemos la clase
                }, 2000);
            }
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
