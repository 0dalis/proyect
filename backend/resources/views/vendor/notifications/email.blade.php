<x-mail::message>
{{-- Saludo --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# Algo salió mal
@else
# Hola
@endif
@endif

{{-- Líneas antes del botón --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Botón --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Líneas después del botón --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Despedida --}}
@if (! empty($salutation))
{{ $salutation }}
@else
Saludos,<br>
El equipo de {{ config('legal.product') }}
@endif

{{-- Enlace por si el botón no funciona --}}
@isset($actionText)
<x-slot:subcopy>
Si el botón "{{ $actionText }}" no funciona, copia y pega este enlace en tu navegador:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
