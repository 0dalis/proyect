    <!-- Modal Empresa (igual estilo que el de roles) -->
    <div id="companyModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-neutral-900/60 transition-opacity duration-300 backdrop-blur-sm"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div id="companyModalBox"
                 class="relative w-full max-w-lg transform rounded-xl bg-white p-5 text-left align-middle shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 border border-neutral-200">

                <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                    <h3 id="companyModalTitle" class="text-lg font-semibold text-neutral-900">Nueva Empresa</h3>
                    <button type="button" id="closeCompanyModal"
                            class="text-neutral-400 bg-transparent hover:bg-neutral-100 hover:text-neutral-700 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>

                <form id="companyForm">
                    @csrf
                    <input type="hidden" id="companyId" name="company_id">
                    <input type="hidden" id="actionCompany" name="action">

                    <div class="space-y-4 py-5">
                        <div>
                            <label for="companyName" class="block text-sm font-medium text-neutral-700 mb-1">Nombre</label>
                            <input type="text" id="companyName" name="name"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="companyCode" class="block text-sm font-medium text-neutral-700 mb-1">Código</label>
                            <input type="text" id="companyCode" name="code"
                                   class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="companyPlanId" class="block text-sm font-medium text-neutral-700 mb-1">Plan</label>
                            <select id="companyPlanId" name="plan_id"
                                    class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                                <option value="" disabled selected>Seleccione un plan</option>
                                @foreach($planes as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="trialEndsAt" class="block text-sm font-medium text-neutral-700 mb-1">Inicio Plan</label>
                                <input type="datetime-local" id="trialEndsAt" name="trial_ends_at"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                            </div>
                            <div>
                                <label for="subscriptionEndsAt" class="block text-sm font-medium text-neutral-700 mb-1">Fin Plan</label>
                                <input type="datetime-local" id="subscriptionEndsAt" name="subscription_ends_at"
                                       class="block w-full rounded-lg border border-neutral-300 bg-white text-neutral-900 focus:border-indigo-600 focus:ring-indigo-600 focus:ring-1 px-3 py-2 shadow-sm text-sm">
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 pt-2">
                            <input type="checkbox" id="companyIsActive" name="is_active" value="1"
                                   class="h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="companyIsActive" class="text-sm font-medium text-neutral-700">Activo</label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t border-neutral-100 space-x-3 pt-4">
                        <button type="button" id="closeCompanyModalBtn"
                                class="text-neutral-700 bg-white border border-neutral-300 hover:bg-neutral-50 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" id="saveCompanyBtn"
                                class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="saveCompanyBtnText">Guardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function () {

        $('#openEmpresaModal').on('click', function () {
            $('#companyForm')[0].reset();
            $('#companyModalTitle').text('Nueva Empresa');
            $('#actionCompany').val('create');
            $('#companyId').val('');
            $('#companyIsActive').prop('checked', true);
            // Limpiar fechas
            $('#trialEndsAt').val('');
            $('#subscriptionEndsAt').val('');

            $('#companyModal').removeClass('hidden');
            setTimeout(() => {
                $('#companyModalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 10);
        });

        $(document).on('click', '.editCompany', function (e) {
            e.preventDefault();
            const $btn = $(this);
            $('#companyForm')[0].reset();
            $('#companyModalTitle').text('Editar Empresa');
            $('#actionCompany').val('update');
            $('#companyId').val($btn.data('id'));
            $('#companyName').val($btn.data('name'));
            $('#companyCode').val($btn.data('code'));
            $('#companyPlan').val($btn.data('plan'));
            $('#companyIsActive').prop('checked', $btn.data('is_active') == 1);

            // Cargar fechas
            $('#trialEndsAt').val($btn.data('trial_ends_at'));
            $('#subscriptionEndsAt').val($btn.data('subscription_ends_at'));

            $('#companyModal').removeClass('hidden');
            setTimeout(() => {
                $('#companyModalBox').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 10);
        });

        function closeCompanyModal() {
            $('#companyModalBox').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
            setTimeout(() => {
                $('#companyModal').addClass('hidden');
            }, 200);
        }

        $('#closeCompanyModal, #closeCompanyModalBtn').on('click', closeCompanyModal);

        // Envío AJAX
        $('#companyForm').on('submit', function (e) {
            e.preventDefault();
            const action = $('#actionCompany').val();
            const url = action === 'create' ? "{{ route('companies.store') }}" : "{{ route('companies.update') }}";
            const method = action === 'create' ? 'POST' : 'PUT';
            const data = $(this).serialize();
            const $btn = $('#saveCompanyBtn');
            const $btnText = $('#saveCompanyBtnText');

            $btn.prop('disabled', true);
            $btnText.html('<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Guardando...');

            $.ajax({
                url: url,
                type: method,
                data: data,
                success: function (response) {
                    Toastify({
                        text: response.message || 'Empresa guardada correctamente',
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#10B981",
                    }).showToast();
                    setTimeout(() => location.reload(), 1000);
                },
                error: function (xhr) {
                    let errorMessage = 'Error al guardar la empresa';
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
