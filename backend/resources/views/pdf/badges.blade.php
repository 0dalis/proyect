<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; color: #1f2937; background: #ffffff; }

        .badge { position: relative; overflow: hidden; page-break-after: always; background: #ffffff; }
        .badge.last { page-break-after: auto; }
        .badge.horizontal { width: 241pt; height: 152pt; }
        .badge.vertical { width: 152pt; height: 241pt; }

        /* ---------- Frente ---------- */
        .band { position: absolute; top: 0; left: 0; height: 28pt; background: #1d4ed8; color: #ffffff; }
        .horizontal .band { width: 241pt; }
        .vertical .band { width: 152pt; height: 26pt; }
        .band .logo { position: absolute; top: 6pt; left: 7pt; width: 22pt; height: 16pt; overflow: hidden; }
        .band .logo img { max-width: 22pt; max-height: 16pt; }
        .band .brand { position: absolute; top: 0; left: 7pt; height: 28pt; line-height: 28pt; font-size: 8.5pt; font-weight: bold; }
        .vertical .band .brand { height: 26pt; line-height: 26pt; font-size: 8pt; }
        .band.has-logo .brand { left: 33pt; }

        .photo { position: absolute; overflow: hidden; background: #dbeafe; color: #1d4ed8; font-weight: bold; text-align: center; }
        .horizontal .photo { left: 8pt; top: 40pt; width: 54pt; height: 54pt; line-height: 54pt; font-size: 15pt; }
        .vertical .photo { left: 48pt; top: 44pt; width: 56pt; height: 56pt; line-height: 56pt; font-size: 16pt; }
        .photo img { width: 100%; height: 100%; }

        .name { position: absolute; font-weight: bold; color: #111827; overflow: hidden; max-height: 24pt; }
        .horizontal .name { left: 70pt; top: 40pt; width: 86pt; font-size: 11pt; line-height: 12pt; }
        .vertical .name { left: 4pt; top: 104pt; width: 144pt; font-size: 11pt; line-height: 12pt; text-align: center; }
        .role { position: absolute; color: #334155; overflow: hidden; white-space: nowrap; }
        .horizontal .role { left: 70pt; top: 66pt; width: 86pt; font-size: 7.5pt; line-height: 9pt; }
        .vertical .role { left: 4pt; top: 130pt; width: 144pt; font-size: 7.5pt; line-height: 9pt; text-align: center; }
        .where { position: absolute; color: #64748b; overflow: hidden; max-height: 16pt; }
        .horizontal .where { left: 70pt; top: 77pt; width: 86pt; font-size: 7pt; line-height: 8pt; }
        .vertical .where { left: 4pt; top: 141pt; width: 144pt; font-size: 7pt; line-height: 8pt; text-align: center; }

        .temp { position: absolute; background: #fef3c7; color: #92400e; font-size: 6.5pt; font-weight: bold; padding: 1.5pt 4pt; }
        .horizontal .temp { left: 70pt; top: 96pt; }
        .vertical .temp { left: 36pt; top: 30pt; }

        .code-label { position: absolute; font-size: 5.5pt; letter-spacing: 0.6pt; color: #64748b; text-transform: uppercase; }
        .horizontal .code-label { left: 8pt; top: 100pt; }
        .vertical .code-label { left: 4pt; top: 158pt; width: 144pt; text-align: center; }
        .code { position: absolute; font-family: 'DejaVu Sans Mono', monospace; font-weight: bold; color: #0f172a; }
        .horizontal .code { left: 8pt; top: 108pt; font-size: 12.5pt; }
        .vertical .code { left: 4pt; top: 165pt; width: 144pt; font-size: 12.5pt; text-align: center; }

        .qr { position: absolute; }
        .horizontal .qr { left: 159pt; top: 40pt; width: 74pt; height: 74pt; }
        .vertical .qr { left: 49pt; top: 184pt; width: 54pt; height: 54pt; }

        .dates { position: absolute; color: #475569; font-size: 6pt; }
        .horizontal .dates { left: 8pt; top: 135pt; width: 150pt; }
        .dates.hidden { display: none; }

        /* ---------- Reverso ---------- */
        .back-band { position: absolute; top: 0; left: 0; height: 20pt; background: #1f2937; color: #ffffff; }
        .horizontal .back-band { width: 241pt; }
        .vertical .back-band { width: 152pt; }
        .back-band .back-brand { position: absolute; top: 0; left: 8pt; height: 20pt; line-height: 20pt; font-size: 8pt; font-weight: bold; }
        .back-band .back-title { position: absolute; top: 0; right: 8pt; height: 20pt; line-height: 20pt; font-size: 6.5pt; color: #cbd5e1; }
        .vertical .back-title { display: none; }

        .back-h { position: absolute; left: 8pt; top: 28pt; font-size: 8pt; font-weight: bold; color: #0f172a; }
        .back-steps { position: absolute; left: 8pt; top: 40pt; font-size: 6.5pt; line-height: 9pt; color: #334155; }
        .horizontal .back-steps { width: 150pt; }
        .vertical .back-steps { width: 136pt; }
        .back-terms-h { position: absolute; left: 8pt; font-size: 6.5pt; font-weight: bold; color: #0f172a; }
        .horizontal .back-terms-h { top: 84pt; }
        .vertical .back-terms-h { top: 100pt; }
        .back-terms { position: absolute; left: 8pt; font-size: 6pt; line-height: 7.5pt; color: #64748b; }
        .horizontal .back-terms { top: 93pt; width: 225pt; }
        .vertical .back-terms { top: 109pt; width: 136pt; }
        .back-dates { position: absolute; left: 8pt; font-size: 6pt; color: #334155; }
        .horizontal .back-dates { top: 120pt; }
        .vertical .back-dates { top: 154pt; }
        .back-stripe { position: absolute; left: 8pt; height: 10pt; background: #0f172a; }
        .horizontal .back-stripe { top: 130pt; width: 225pt; }
        .vertical .back-stripe { top: 214pt; width: 136pt; }
        .back-foot { position: absolute; left: 8pt; font-size: 5.5pt; color: #94a3b8; text-align: right; }
        .horizontal .back-foot { top: 143pt; width: 225pt; }
        .vertical .back-foot { top: 228pt; width: 136pt; }
    </style>
</head>
<body>
@php
    $last = array_key_last($badges);
@endphp
@foreach ($badges as $index => $badge)
    @php
        $employee = $badge['employee'];
        $face = $orientation.($index === $last ? ' last' : '');
        $initials = mb_substr($employee->first_name, 0, 1).mb_substr($employee->last_name, 0, 1);
        $temporary = $employee->employment_type->value === 'temporary';
        $issued = $employee->badge_issued_at?->format('d/m/Y');
        $expires = $employee->badge_expires_on?->format('d/m/Y');
    @endphp

    {{-- Frente --}}
    <div class="badge {{ $face }}">
        <div class="band{{ $badge['logo'] ? ' has-logo' : '' }}">
            @if ($badge['logo'])
                <div class="logo"><img src="{{ $badge['logo'] }}" alt=""></div>
            @endif
            <div class="brand">{{ $company->name }}</div>
        </div>

        <div class="photo">
            @if ($badge['photo'])
                <img src="{{ $badge['photo'] }}" alt="">
            @else
                {{ $initials }}
            @endif
        </div>

        <div class="name">{{ $employee->fullName() }}</div>
        <div class="role">{{ $employee->position ?: 'Colaborador' }}</div>
        @php
            $where = collect([$employee->area?->name, $employee->office?->name])->filter()->implode(' · ');
        @endphp
        @if ($where)
            <div class="where">{{ $where }}</div>
        @endif

        @if ($temporary)
            <div class="temp">TEMPORAL @if ($expires) · {{ $expires }} @endif</div>
        @endif

        <div class="code-label">Código</div>
        <div class="code">{{ $employee->employee_code }}</div>

        <img class="qr" src="{{ $badge['qr'] }}" alt="QR">

        @if ($issued || $expires)
            {{-- En vertical no queda espacio: las fechas van solo en el reverso. --}}
            <div class="dates{{ $orientation === 'horizontal' ? '' : ' hidden' }}">
                @if ($issued) Emitida {{ $issued }} @endif
                @if ($issued && $expires) · @endif
                @if ($expires) Vigencia {{ $expires }} @endif
            </div>
        @endif
    </div>

    {{-- Reverso --}}
    <div class="badge {{ $face }}">
        <div class="back-band">
            <div class="back-brand">{{ $company->name }}</div>
            <div class="back-title">Credencial de colaborador</div>
        </div>

        <div class="back-h">Uso en el kiosko</div>
        <div class="back-steps">
            1. Acerca el código QR al lector del kiosko o teclea las cifras de tu código.<br>
            2. Escribe tu PIN de 6 dígitos.<br>
            3. Confirma tu entrada o salida.
        </div>

        <div class="back-terms-h">Términos de uso</div>
        <div class="back-terms">
            Esta credencial es personal e intransferible. Su uso indebido podrá ser motivo de sanción.
            Si la pierdes, repórtala de inmediato a Recursos Humanos.
        </div>

        <div class="back-dates">
            @if ($issued) Emitida {{ $issued }} @endif
            @if ($issued && $expires) · @endif
            @if ($expires) Vigencia {{ $expires }} @endif
        </div>

        <div class="back-stripe"></div>
        <div class="back-foot">{{ $employee->employee_code }} · {{ $company->name }}</div>
    </div>
@endforeach
</body>
</html>
