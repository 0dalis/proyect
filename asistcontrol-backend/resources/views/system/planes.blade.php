<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('planes') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">tipo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">precio</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IVA</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min usuarios</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max usuarios</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Público</th>   {{-- NUEVA COLUMNA --}}
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($planes as $plan)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $plan->nombre }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $plan->tipo }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">$ {{ $plan->precio }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $plan->iva }} %</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @if($plan->min_users) {{ $plan->min_users }} @else <i class="bi bi-infinity text-lg"></i> @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @if($plan->max_users) {{ $plan->max_users }} @else <i class="bi bi-infinity text-lg"></i> @endif
                                    </td>

                                    {{-- CELDA DEL TOGGLE --}}
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                class="sr-only peer togglepublic"
                                                data-id="{{ $plan->id }}"
                                                {{ $plan->public ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                            <span class="ml-2 text-xs text-gray-600">{{ $plan->public ? 'Sí' : 'No' }}</span>
                                        </label>
                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <a href="#"
                                        class="editPlan inline-flex items-center px-2 py-1 text-sm text-indigo-600 hover:text-indigo-900"
                                        data-id="{{ $plan->id }}"
                                        data-nombre="{{ $plan->nombre }}"
                                        data-tipo="{{ $plan->tipo }}"
                                        data-precio="{{ $plan->precio }}"
                                        data-iva="{{ $plan->iva }}"
                                        data-min_users="{{ $plan->min_users }}"
                                        data-max_users="{{ $plan->max_users }}"
                                        data-caracteristicas="{{ is_array($plan->caracteristicas) ? implode("\n", $plan->caracteristicas) : '' }}"
                                        data-stripe_price_id="{{ $plan->stripe_price_id }}">
                                            <i class="bi bi-pencil-fill"></i>
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
    <a href="#"
       id="openPlanesModal"
       data-action="create"
       class="fixed bottom-6 right-6 inline-flex items-center px-4 py-3 bg-indigo-600 text-white text-sm font-medium rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none z-40 transition-colors">
        <i class="bi bi-plus-lg mr-2 text-lg"></i>
        Nuevo Plan
    </a>
    <style>
        /* Para el toggle cuando está en estado "processing" (deshabilitado) */
        .togglepublic.processing {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Si usas un slider personalizado (ejemplo con pseudo-elementos), ajusta */
        .togglepublic.processing + .slider {
            background-color: #9ca3af !important;
            cursor: not-allowed;
        }
    </style>
    @include('system.planes-modal')
</x-app-layout>
