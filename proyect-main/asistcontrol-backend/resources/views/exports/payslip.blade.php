<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; color: #4f46e5; }
        .muted { color: #6b7280; font-size: 10px; }
        .grid { width: 100%; margin-bottom: 14px; }
        .grid td { padding: 3px 0; vertical-align: top; }
        .label { color: #6b7280; font-size: 10px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: #eef2ff; text-align: left; padding: 6px 8px; border: 1px solid #d1d5db; font-size: 10px; }
        table.items td { padding: 5px 8px; border: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .total { font-weight: bold; font-size: 13px; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $company->name }}</div>
        <div class="muted">Recibo de nómina · {{ $item->period->name }} ({{ $item->period->start_date->format('d/m/Y') }} – {{ $item->period->end_date->format('d/m/Y') }})</div>
    </div>

    <table class="grid">
        <tr>
            <td width="50%">
                <div class="label">Empleado</div>
                <div>{{ $item->employee->full_name }} ({{ $item->employee->employee_code }})</div>
                <div class="label" style="margin-top:6px;">Puesto</div>
                <div>{{ $item->employee->position ?? '—' }}</div>
            </td>
            <td width="50%">
                <div class="label">Oficina / Área</div>
                <div>{{ $item->employee->office->name ?? '—' }} · {{ $item->employee->area->name ?? '—' }}</div>
                <div class="label" style="margin-top:6px;">Banco / Cuenta</div>
                <div>{{ $item->employee->bank_name ?? '—' }} {{ $item->employee->bank_account ? '· ' . $item->employee->bank_account : '' }}</div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td><div class="label">Días trabajados</div><div>{{ $item->worked_days }}</div></td>
            <td><div class="label">Horas trabajadas</div><div>{{ number_format($item->worked_minutes / 60, 1) }} h</div></td>
            <td><div class="label">Horas extra</div><div>{{ number_format($item->overtime_minutes / 60, 1) }} h</div></td>
            <td><div class="label">Retardos</div><div>{{ $item->late_count }}</div></td>
            <td><div class="label">Faltas</div><div>{{ $item->absence_count }}</div></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>Concepto</th><th>Tipo</th><th class="right">Importe</th></tr>
        </thead>
        <tbody>
            <tr><td>Sueldo base</td><td>Percepción</td><td class="right">{{ number_format($item->base_amount, 2) }}</td></tr>
            <tr><td>Horas extra</td><td>Percepción</td><td class="right">{{ number_format($item->overtime_amount, 2) }}</td></tr>
            @foreach (($item->breakdown ?? []) as $line)
                <tr>
                    <td>{{ $line['concept'] }}</td>
                    <td>{{ $line['type'] === 'bonus' ? 'Percepción' : 'Deducción' }}</td>
                    <td class="right">{{ number_format($line['amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="items" style="margin-top:12px;">
        <tr>
            <td>Total percepciones</td>
            <td class="right">{{ number_format($item->gross_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Total deducciones</td>
            <td class="right">{{ number_format($item->deductions_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="total">Neto a pagar</td>
            <td class="right total">{{ number_format($item->net_amount, 2) }}</td>
        </tr>
    </table>

    <p class="muted" style="margin-top:20px;">Generado el {{ $generatedAt }}. Documento informativo sin validez fiscal.</p>
</body>
</html>
