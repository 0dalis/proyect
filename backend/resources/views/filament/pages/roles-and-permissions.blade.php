<x-filament-panels::page>
    @php
        $roles = $this->roles();
        $usersByRole = $this->usersByRole();
        $customized = $this->customizedRoles();
    @endphp

    {{-- Uso de cada rol en la plataforma --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($roles as $role)
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ $role['label'] }}</div>
                <div class="text-2xl font-bold text-gray-950">{{ number_format($usersByRole[$role['value']]) }}</div>
                <div class="text-xs text-gray-500">
                    usuarios
                    @isset($customized[$role['value']])
                        · {{ $customized[$role['value']] }} {{ $customized[$role['value']] === 1 ? 'empresa lo personalizó' : 'empresas lo personalizaron' }}
                    @endisset
                </div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- Matriz de permisos iniciales --}}
    <x-filament::section>
        <x-slot name="heading">Permisos de cada rol</x-slot>
        <x-slot name="description">
            Lo que trae cada rol al crear una empresa. El dueño los ajusta para su empresa, excepto los bloqueados,
            que nunca se pueden dar a ese rol. El dueño siempre tiene todos.
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500">
                        <th class="py-2 pr-4 font-medium">Permiso</th>
                        @foreach ($roles as $role)
                            <th class="px-3 py-2 text-center font-medium">{{ $role['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->matrix() as $row)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 pr-4">
                                <div class="font-medium text-gray-950">{{ $row['label'] }}</div>
                                <code class="text-xs text-gray-500">{{ $row['key'] }}</code>
                            </td>
                            @foreach ($roles as $role)
                                @php($state = $row['roles'][$role['value']])
                                <td class="px-3 py-2 text-center">
                                    @if ($state === 'default')
                                        <x-filament::icon icon="heroicon-m-check-circle" class="mx-auto h-5 w-5 text-success-600" />
                                        <span class="sr-only">Incluido</span>
                                    @elseif ($state === 'locked')
                                        <x-filament::icon icon="heroicon-m-lock-closed" class="mx-auto h-4 w-4 text-gray-400" />
                                        <span class="sr-only">Bloqueado</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                        <span class="sr-only">No incluido (el dueño puede darlo)</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1"><x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 text-success-600" /> Incluido al crear la empresa</span>
            <span class="inline-flex items-center gap-1"><span class="text-gray-300">—</span> No incluido (el dueño puede darlo)</span>
            <span class="inline-flex items-center gap-1"><x-filament::icon icon="heroicon-m-lock-closed" class="h-4 w-4 text-gray-400" /> Bloqueado para ese rol</span>
        </div>
    </x-filament::section>

    {{-- Roles actuales de una empresa --}}
    <x-filament::section>
        <x-slot name="heading">Roles de una empresa</x-slot>
        <x-slot name="description">Los permisos que tiene hoy cada rol, con los cambios que hizo el dueño.</x-slot>

        <div class="max-w-md">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="companyId">
                    <option value="">Elige una empresa…</option>
                    @foreach ($this->companies() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        @if ($companyId)
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($roles as $role)
                    @php($permissions = $this->companyRoles()[$role['value']] ?? [])
                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="font-semibold text-gray-950">{{ $role['label'] }}</span>
                            <span class="text-xs text-gray-500">
                                {{ $role['value'] === 'owner' ? 'Todos' : count($permissions).' permisos' }}
                            </span>
                        </div>
                        @if ($role['value'] === 'owner')
                            <p class="text-sm text-gray-500">El dueño siempre tiene todos los permisos.</p>
                        @elseif (! $permissions)
                            <p class="text-sm text-gray-500">Sin permisos: solo checa y ve lo suyo.</p>
                        @else
                            <ul class="space-y-1 text-sm text-gray-700">
                                @foreach ($permissions as $permission)
                                    <li class="flex items-start gap-1.5">
                                        <x-filament::icon icon="heroicon-m-check" class="mt-0.5 h-4 w-4 shrink-0 text-success-600" />
                                        {{ $this->permissionLabel($permission) }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
