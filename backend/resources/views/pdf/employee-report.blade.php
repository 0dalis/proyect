<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 12px; margin: 18px 0 6px; color: #1e293b; }
        .muted { color: #64748b; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 8px; margin-bottom: 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 4px 6px; }
        .kpis td { width: 20%; border: 1px solid #e2e8f0; text-align: center; padding: 8px 4px; }
        .kpis strong { display: block; font-size: 16px; }
        table.days { width: 100%; border-collapse: collapse; }
        table.days th { background: #f1f5f9; text-align: left; padding: 5px 6px; font-size: 9px; text-transform: uppercase; color: #64748b; }
        table.days td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; }
        .pill { padding: 1px 6px; border-radius: 8px; font-size: 9px; font-weight: bold; }
        .on_time { background: #ecfdf5; color: #047857; }
        .late { background: #fffbeb; color: #b45309; }
        .absent { background: #fef2f2; color: #b91c1c; }
        .excused { background: #ecfeff; color: #0e7490; }
        .rest, .pending { background: #f1f5f9; color: #64748b; }
        .calendar { margin-top: 14px; page-break-inside: avoid; }
        .calendar img { display: block; width: 480px; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; }
        .footer { margin-top: 16px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
@php
    $labels = ['on_time' => 'A tiempo', 'late' => 'Retardo', 'absent' => 'Falta', 'excused' => 'Vacaciones / permiso', 'rest' => 'Descanso', 'pending' => 'Pendiente'];
@endphp

<div class="header">
    <table class="grid">
        <tr>
            <td>
                <h1>{{ $employee->fullName() }}</h1>
                <div class="muted">{{ $employee->employee_code }} · {{ $employee->position ?: 'Colaborador' }} · {{ $employee->area?->name }}</div>
            </td>
            <td style="text-align: right">
                <strong>{{ $company->name }}</strong><br>
                <span class="muted">Reporte de asistencia<br>{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span>
            </td>
        </tr>
    </table>
</div>

<table class="grid">
    <tr>
        <td><span class="muted">Oficina:</span> {{ $employee->office?->name }}</td>
        <td><span class="muted">Turno:</span> {{ $employee->shift?->name }} ({{ substr($employee->shift?->starts_at, 0, 5) }}–{{ substr($employee->shift?->ends_at, 0, 5) }})</td>
        <td><span class="muted">Tolerancia:</span> {{ $employee->shift?->tolerance_minutes }} min</td>
    </tr>
</table>

<h2>Resumen</h2>
<table class="grid kpis">
    <tr>
        <td><strong>{{ $metrics['worked_days'] }}/{{ $metrics['scheduled_days'] }}</strong>Días trabajados</td>
        <td><strong>{{ $metrics['on_time'] }}</strong>A tiempo</td>
        <td><strong>{{ $metrics['lates'] }}</strong>Retardos ({{ $metrics['unjustified_lates'] }} sin justificar)</td>
        <td><strong>{{ $metrics['absences'] }}</strong>Faltas ({{ $metrics['unjustified_absences'] }} sin justificar)</td>
        <td><strong>{{ $metrics['minutes_late'] }}</strong>Minutos de retardo</td>
    </tr>
</table>

<h2>Detalle por día</h2>
<table class="days">
    <thead>
        <tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Estado</th><th>Notas</th></tr>
    </thead>
    <tbody>
        @foreach ($days as $day)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($day['date'])->locale('es')->translatedFormat('D d M') }}</td>
                <td>{{ $day['check_in'] ?? '—' }}</td>
                <td>{{ $day['check_out'] ?? '—' }}</td>
                <td><span class="pill {{ $day['status'] }}">{{ $labels[$day['status']] ?? $day['status'] }}</span></td>
                <td class="muted">
                    @if ($day['minutes_late']) {{ $day['minutes_late'] }} min tarde @endif
                    @if ($day['justified']) · Justificado @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if (! empty($calendarCapture))
<div class="calendar">
    <h2>Calendario</h2>
    <img src="{{ $calendarCapture }}" alt="Calendario de asistencia del periodo">
</div>
@endif

<div class="footer">Generado por {{ $generatedBy }} el {{ now()->setTimezone($company->timezone)->format('d/m/Y H:i') }} · AsistControl</div>
</body>
</html>
