<x-mail::layout>
{{-- Encabezado de marca --}}
<x-slot:header>
<x-mail::header :url="config('app.frontend_url')">
{{ config('legal.product') }}
</x-mail::header>
</x-slot:header>

{{-- Cuerpo --}}
{!! $slot !!}

{{-- Nota al pie del cuerpo --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Pie: producto, proveedor y soporte --}}
<x-slot:footer>
<x-mail::footer>
**{{ config('legal.product') }}** · diseñado y soportado por {{ config('legal.provider') }}

¿Dudas? Escríbenos a [{{ config('legal.support_email') }}](mailto:{{ config('legal.support_email') }})

[Aviso de privacidad]({{ rtrim(config('app.frontend_url'), '/') }}/legal/privacidad) · [Términos]({{ rtrim(config('app.frontend_url'), '/') }}/legal/terminos)

© {{ date('Y') }} {{ config('legal.provider') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
