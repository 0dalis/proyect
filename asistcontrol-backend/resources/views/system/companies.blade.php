<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Empresas') }}
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
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicio Plan</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fin Plan</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($companies as $company)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $company->name }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $company->code }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">{{ $company->slug }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        {{ $company->plan?->nombre ?? 'Sin plan' }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        {{ $company->trial_ends_at ? \Carbon\Carbon::parse($company->trial_ends_at)->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        {{ $company->subscription_ends_at ? \Carbon\Carbon::parse($company->subscription_ends_at)->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        @if($company->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <a href="#"
                                           class="editCompany inline-flex items-center px-2 py-1 text-sm text-indigo-600 hover:text-indigo-900"
                                           data-id="{{ $company->id }}"
                                           data-name="{{ $company->name }}"
                                           data-code="{{ $company->code }}"
                                           data-plan="{{ $company->plan_id }}"
                                           data-trial_ends_at="{{ $company->trial_ends_at ? \Carbon\Carbon::parse($company->trial_ends_at)->format('Y-m-d\TH:i') : '' }}"
                                           data-subscription_ends_at="{{ $company->subscription_ends_at ? \Carbon\Carbon::parse($company->subscription_ends_at)->format('Y-m-d\TH:i') : '' }}"
                                           data-is_active="{{ $company->is_active ? 1 : 0 }}">
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
       id="openEmpresaModal"
       data-action="create"
       class="fixed bottom-6 right-6 inline-flex items-center px-4 py-3 bg-indigo-600 text-white text-sm font-medium rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none z-40 transition-colors">
        <i class="bi bi-plus-lg mr-2 text-lg"></i>
        Nueva Empresa
    </a>
    @include('system.companie-modal')
</x-app-layout>
