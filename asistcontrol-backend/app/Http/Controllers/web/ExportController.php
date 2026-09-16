<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Office;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Request as RequestModel;
use App\Models\Shift;
use App\Support\XlsxWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function export(Request $request, string $resource): Response
    {
        $company = $request->user()->company;
        $format = strtolower($request->input('format', 'csv'));

        [$title, $headers, $rows] = $this->build($request, $company, $resource);

        $filename = $resource . '_' . now()->format('Ymd_His');

        return match ($format) {
            'xlsx' => $this->xlsx($filename, $title, $headers, $rows),
            'pdf' => $this->pdf($filename, $title, $company, $headers, $rows),
            default => $this->csv($filename, $headers, $rows),
        };
    }

    private function csv(string $filename, array $headers, array $rows): Response
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $v ?? '', $row));
            }
            fclose($out);
        }, $filename . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function xlsx(string $filename, string $title, array $headers, array $rows): Response
    {
        $content = XlsxWriter::build($title, $headers, $rows);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
        ]);
    }

    private function pdf(string $filename, string $title, Company $company, array $headers, array $rows): Response
    {
        $pdf = Pdf::loadView('exports.table', [
            'title' => $title,
            'company' => $company->name,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('letter', 'landscape');

        return $pdf->download($filename . '.pdf');
    }

    /**
     * @return array{0:string,1:array,2:array}
     */
    private function build(Request $request, Company $company, string $resource): array
    {
        return match ($resource) {
            'attendance' => $this->attendance($request, $company),
            'employees' => $this->employees($company),
            'offices' => $this->offices($company),
            'areas' => $this->areas($company),
            'shifts' => $this->shifts($company),
            'requests' => $this->requests($company),
            'notifications' => $this->notifications($company),
            'payroll_items' => $this->payrollItems($request, $company),
            'payroll_periods' => $this->payrollPeriods($company),
            'bank_layout' => $this->bankLayout($request, $company),
            'report_attendance' => $this->reportAttendance($request, $company),
            default => abort(404, 'Recurso no soportado.'),
        };
    }

    private function attendance(Request $request, Company $company): array
    {
        $query = Attendance::where('company_id', $company->id)
            ->with(['employee:id,first_name,last_name,employee_code', 'office:id,name', 'records']);

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }
        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        $rows = $query->orderByDesc('date')->get()->map(function ($a) {
            $time = fn ($type) => optional($a->records->firstWhere('type', $type))->recorded_at;
            return [
                $a->employee?->full_name ?? '—',
                $a->employee?->employee_code ?? '—',
                optional($a->date)->format('d/m/Y'),
                $a->status,
                $time('check_in') ? Carbon::parse($time('check_in'))->format('H:i') : '',
                $time('check_out') ? Carbon::parse($time('check_out'))->format('H:i') : '',
                $a->worked_minutes,
                $a->late_minutes,
                $a->overtime_minutes,
            ];
        })->all();

        return ['Asistencia', ['Empleado', 'Código', 'Fecha', 'Estado', 'Entrada', 'Salida', 'Min. trabajados', 'Retardo', 'Extra'], $rows];
    }

    private function employees(Company $company): array
    {
        $rows = $company->employees()->with(['office:id,name', 'area:id,name', 'shift:id,name', 'compensation'])->get()->map(fn ($e) => [
            $e->full_name,
            $e->employee_code,
            $e->office?->name ?? '—',
            $e->area?->name ?? '—',
            $e->shift?->name ?? '—',
            $e->compensation?->base_salary ?? 0,
            $e->compensation?->pay_frequency ?? '—',
            $e->is_active ? 'Activo' : 'Inactivo',
        ])->all();

        return ['Empleados', ['Empleado', 'Código', 'Oficina', 'Área', 'Turno', 'Salario base', 'Frecuencia', 'Estado'], $rows];
    }

    private function offices(Company $company): array
    {
        $rows = $company->offices()->get()->map(fn ($o) => [
            $o->name, $o->code ?? '—', $o->latitude, $o->longitude, $o->radius_meters, $o->timezone, $o->country ?? '—',
            $o->is_active ? 'Activa' : 'Inactiva',
        ])->all();

        return ['Oficinas', ['Nombre', 'Código', 'Latitud', 'Longitud', 'Radio (m)', 'Zona horaria', 'País', 'Estado'], $rows];
    }

    private function areas(Company $company): array
    {
        $rows = $company->areas()->get()->map(fn ($a) => [
            $a->name, $a->is_active ? 'Activa' : 'Inactiva',
        ])->all();

        return ['Áreas', ['Nombre', 'Estado'], $rows];
    }

    private function shifts(Company $company): array
    {
        $rows = Shift::whereHas('office', fn ($q) => $q->where('company_id', $company->id))
            ->with('office:id,name')->get()->map(fn ($s) => [
                $s->name,
                $s->office?->name ?? '—',
                Carbon::parse($s->start_time)->format('H:i'),
                Carbon::parse($s->end_time)->format('H:i'),
                $s->lunch_start ? Carbon::parse($s->lunch_start)->format('H:i') : '—',
                $s->lunch_end ? Carbon::parse($s->lunch_end)->format('H:i') : '—',
                $s->is_active ? 'Activo' : 'Inactivo',
            ])->all();

        return ['Turnos', ['Turno', 'Oficina', 'Entrada', 'Salida', 'Descanso inicio', 'Descanso fin', 'Estado'], $rows];
    }

    private function requests(Company $company): array
    {
        $rows = RequestModel::where('company_id', $company->id)
            ->with('user:id,email')->get()->map(fn ($r) => [
                $r->user?->email ?? '—',
                $r->type,
                optional($r->start_date)->format('d/m/Y'),
                $r->end_date ? optional($r->end_date)->format('d/m/Y') : '',
                $r->status,
                $r->is_paid ? 'Con goce' : 'Sin goce',
                $r->reason ?? '',
            ])->all();

        return ['Solicitudes', ['Usuario', 'Tipo', 'Inicio', 'Fin', 'Estado', 'Goce de sueldo', 'Motivo'], $rows];
    }

    private function notifications(Company $company): array
    {
        $rows = $company->notifications()->get()->map(fn ($n) => [
            $n->title, $n->message, $n->target_type, $n->priority,
            $n->sent_at ? Carbon::parse($n->sent_at)->format('d/m/Y H:i') : 'Pendiente',
            $n->is_active ? 'Activa' : 'Inactiva',
        ])->all();

        return ['Notificaciones', ['Título', 'Mensaje', 'Destino', 'Prioridad', 'Enviada', 'Estado'], $rows];
    }

    private function payrollPeriods(Company $company): array
    {
        $rows = $company->payrollPeriods()->withCount('items')->get()->map(fn ($p) => [
            $p->name,
            $p->frequency,
            optional($p->start_date)->format('d/m/Y'),
            optional($p->end_date)->format('d/m/Y'),
            $p->status,
            $p->items_count,
        ])->all();

        return ['Periodos de nómina', ['Periodo', 'Frecuencia', 'Inicio', 'Fin', 'Estado', 'Empleados'], $rows];
    }

    private function payrollItems(Request $request, Company $company): array
    {
        $periodId = $request->input('period_id');
        $items = PayrollItem::whereHas('period', fn ($q) => $q->where('company_id', $company->id))
            ->when($periodId, fn ($q) => $q->where('payroll_period_id', $periodId))
            ->with(['employee:id,first_name,last_name,employee_code', 'period:id,name'])
            ->get();

        $rows = $items->map(fn ($i) => [
            $i->period?->name ?? '—',
            $i->employee?->full_name ?? '—',
            $i->employee?->employee_code ?? '—',
            $i->worked_days,
            $i->late_count,
            $i->absence_count,
            $i->overtime_minutes,
            number_format((float) $i->base_amount, 2),
            number_format((float) $i->overtime_amount, 2),
            number_format((float) $i->bonuses_amount, 2),
            number_format((float) $i->deductions_amount, 2),
            number_format((float) $i->net_amount, 2),
        ])->all();

        return ['Nómina por empleado', ['Periodo', 'Empleado', 'Código', 'Días', 'Retardos', 'Faltas', 'Extra (min)', 'Base', 'Horas extra', 'Bonos', 'Deducciones', 'Neto'], $rows];
    }

    private function bankLayout(Request $request, Company $company): array
    {
        $periodId = $request->input('period_id');

        $items = PayrollItem::whereHas('period', fn ($q) => $q->where('company_id', $company->id))
            ->when($periodId, fn ($q) => $q->where('payroll_period_id', $periodId))
            ->with(['employee:id,first_name,last_name,employee_code,bank_name,bank_account', 'period:id,name'])
            ->get();

        $rows = $items->map(fn ($i) => [
            $i->employee?->employee_code ?? '',
            $i->employee?->full_name ?? '',
            $i->employee?->bank_name ?? '',
            $i->employee?->bank_account ?? '',
            number_format((float) $i->net_amount, 2, '.', ''),
            $i->period?->name ?? '',
        ])->all();

        return ['Layout bancario', ['Código', 'Beneficiario', 'Banco', 'Cuenta/CLABE', 'Monto neto', 'Periodo'], $rows];
    }

    private function reportAttendance(Request $request, Company $company): array
    {
        $groupBy = $request->input('group_by', 'company');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $query = Attendance::where('attendances.company_id', $company->id)
            ->whereBetween('attendances.date', [$from, $to]);

        if ($request->filled('office_id')) {
            $query->where('attendances.office_id', $request->office_id);
        }
        if ($request->filled('area_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('area_id', $request->area_id));
        }

        $aggregate = [
            "count(*) as records",
            "sum(case when attendances.status = 'present' then 1 else 0 end) as present",
            "sum(case when attendances.status = 'late' then 1 else 0 end) as late",
            "sum(case when attendances.status = 'absent' then 1 else 0 end) as absent",
            "sum(case when attendances.status = 'justified' then 1 else 0 end) as justified",
            "coalesce(sum(attendances.worked_minutes), 0) as worked_minutes",
            "coalesce(sum(attendances.overtime_minutes), 0) as overtime_minutes",
        ];

        if ($groupBy === 'area') {
            $rows = $query->leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->leftJoin('areas', 'areas.id', '=', 'employees.area_id')
                ->selectRaw("coalesce(areas.name, 'Sin área') as label")
                ->selectRaw(implode(', ', $aggregate))
                ->groupBy('areas.name')->orderBy('label')->get();
        } elseif ($groupBy === 'office') {
            $rows = $query->leftJoin('offices', 'offices.id', '=', 'attendances.office_id')
                ->selectRaw("coalesce(offices.name, 'Sin oficina') as label")
                ->selectRaw(implode(', ', $aggregate))
                ->groupBy('offices.name')->orderBy('label')->get();
        } elseif ($groupBy === 'employee') {
            $rows = $query->leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->selectRaw("concat(employees.first_name, ' ', employees.last_name) as label")
                ->selectRaw(implode(', ', $aggregate))
                ->groupBy('employees.id', 'employees.first_name', 'employees.last_name')
                ->orderBy('label')->get();
        } else {
            $rows = collect([$query->selectRaw("'Toda la empresa' as label")->selectRaw(implode(', ', $aggregate))->first()]);
        }

        $data = $rows->map(fn ($r) => [
            $r->label ?? '—',
            $r->records,
            $r->present,
            $r->late,
            $r->absent,
            $r->justified,
            (int) $r->worked_minutes,
            (int) $r->overtime_minutes,
        ])->all();

        return ['Reporte de asistencia', ['Grupo', 'Registros', 'Presentes', 'Retardos', 'Faltas', 'Justificados', 'Min. trabajados', 'Extra (min)'], $data];
    }

    public function payslip(Request $request, $itemId): Response
    {
        $company = $request->user()->company;

        $item = PayrollItem::whereHas('period', fn ($q) => $q->where('company_id', $company->id))
            ->with(['employee.office', 'employee.area', 'employee.compensation', 'period'])
            ->findOrFail($itemId);

        $pdf = Pdf::loadView('exports.payslip', [
            'company' => $company,
            'item' => $item,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'portrait');

        return $pdf->download('recibo_' . ($item->employee?->employee_code ?? $item->id) . '.pdf');
    }
}
