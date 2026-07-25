@props(['type' => 'text', 'count' => 1, 'class' => ''])

@for ($i = 0; $i < $count; $i++)
    @if($type === 'card')
        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5 animate-pulse {{ $class }}">
            <div class="flex items-center justify-between">
                <div class="space-y-2 flex-1">
                    <div class="h-3 bg-neutral-200 rounded w-2/3"></div>
                    <div class="h-6 bg-neutral-200 rounded w-1/3"></div>
                </div>
                <div class="w-10 h-10 bg-neutral-200 rounded-lg"></div>
            </div>
            <div class="flex gap-3 mt-3">
                <div class="h-3 bg-neutral-200 rounded w-16"></div>
                <div class="h-3 bg-neutral-200 rounded w-12"></div>
            </div>
        </div>
    @elseif($type === 'card-small')
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-neutral-200 p-4 animate-pulse {{ $class }}">
            <div class="h-3 bg-neutral-200 rounded w-1/2 mb-2"></div>
            <div class="h-7 bg-neutral-200 rounded w-1/3 mb-1"></div>
            <div class="h-3 bg-neutral-200 rounded w-3/4"></div>
        </div>
    @elseif($type === 'chart')
        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden animate-pulse {{ $class }}">
            <div class="px-5 py-4 border-b border-neutral-100 flex justify-between">
                <div class="space-y-1">
                    <div class="h-4 bg-neutral-200 rounded w-40"></div>
                    <div class="h-3 bg-neutral-200 rounded w-24"></div>
                </div>
                <div class="h-6 bg-neutral-200 rounded w-20"></div>
            </div>
            <div class="p-5">
                <div class="h-60 bg-neutral-100 rounded-lg flex items-center justify-center">
                    <i class="bi bi-bar-chart text-neutral-300 text-4xl"></i>
                </div>
            </div>
        </div>
    @elseif($type === 'table-row')
        <tr class="animate-pulse {{ $class }}">
            @for ($j = 0; $j < 7; $j++)
                <td class="px-4 py-3 whitespace-nowrap"><div class="h-4 bg-neutral-200 rounded w-full"></div></td>
            @endfor
        </tr>
    @elseif($type === 'table')
        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden animate-pulse {{ $class }}">
            <div class="px-5 py-4 border-b border-neutral-100 flex justify-between">
                <div class="h-4 bg-neutral-200 rounded w-32"></div>
                <div class="h-3 bg-neutral-200 rounded w-16"></div>
            </div>
            <div class="p-4 space-y-3">
                @for ($k = 0; $k < 5; $k++)
                    <div class="flex gap-4">
                        <div class="h-4 bg-neutral-200 rounded flex-1"></div>
                        <div class="h-4 bg-neutral-200 rounded w-20"></div>
                        <div class="h-4 bg-neutral-200 rounded w-14"></div>
                        <div class="h-4 bg-neutral-200 rounded w-14"></div>
                        <div class="h-4 bg-neutral-200 rounded w-20"></div>
                        <div class="h-4 bg-neutral-200 rounded w-24"></div>
                    </div>
                @endfor
            </div>
        </div>
    @elseif($type === 'text')
        <div class="h-4 bg-neutral-200 rounded w-full animate-pulse {{ $class }}"></div>
    @elseif($type === 'circle')
        <div class="rounded-full bg-neutral-200 animate-pulse {{ $class }}"></div>
    @endif
@endfor
