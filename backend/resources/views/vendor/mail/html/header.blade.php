@props(['url'])
<tr>
<td class="header">
<a href="{{ rtrim(config('app.frontend_url'), '/') }}" style="display: inline-block; text-decoration: none;">
<span class="brand-mark">A</span>
<span class="brand-name">{{ config('legal.product') }}</span>
</a>
<div class="brand-tagline">Control de asistencia para tu empresa</div>
</td>
</tr>
