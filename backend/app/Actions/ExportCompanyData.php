<?php

namespace App\Actions;

use App\Models\Area;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\Office;
use App\Models\Shift;
use App\Tenancy\TenantManager;
use BackedEnum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Exportación que recibe el dueño al pedir la baja: sus listas y el histórico
 * de asistencia en CSV (se abren en Excel). Sin gráficas ni cálculos.
 * Queda en storage/app/private/exports hasta el borrado definitivo.
 */
class ExportCompanyData
{
    public function __construct(private TenantManager $tenants) {}

    /**
     * @return string Ruta en el disco local
     */
    public function handle(Company $company): string
    {
        $path = 'exports/'.$company->id.'-'.Str::random(24).'.zip';
        $disk = Storage::disk('local');
        $disk->makeDirectory('exports');

        $zip = new ZipArchive;
        if ($zip->open($disk->path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear la exportación.');
        }

        $this->tenants->run($company, function () use ($zip) {
            $this->employees($zip);
            $this->attendance($zip);
            $this->requests($zip);
            $this->catalogs($zip);
        });

        $zip->addFromString('LEEME.txt', implode("\r\n", [
            "Exportación de datos de {$company->name} en AsistControl",
            'Generada el '.now()->timezone($company->timezone)->format('d/m/Y H:i'),
            '',
            'empleados.csv    Lista de empleados (incluye bajas).',
            'asistencia.csv   Histórico de entradas y salidas con su estado (a tiempo, retardo...).',
            'solicitudes.csv  Justificaciones, permisos y vacaciones.',
            'areas.csv, oficinas.csv, turnos.csv',
            '',
            'Los archivos usan UTF-8 y se abren con Excel.',
        ]));

        $zip->close();

        return $path;
    }

    private function employees(ZipArchive $zip): void
    {
        $rows = Employee::query()->withTrashed()->with('office:id,name', 'shift:id,name', 'area:id,name')->orderBy('id')->get()
            ->map(fn (Employee $e) => [
                $e->employee_code, $e->employee_number, $e->first_name, $e->last_name, $e->email, $e->phone, $e->position,
                $e->area?->name, $e->office?->name, $e->shift?->name,
                $this->label($e->status), $this->label($e->employment_type), $this->label($e->work_mode),
                $e->hired_on?->format('Y-m-d'), $e->terminated_on?->format('Y-m-d'),
            ]);

        $this->csv($zip, 'empleados.csv', [
            'Código', 'Número', 'Nombre', 'Apellidos', 'Correo', 'Teléfono', 'Puesto', 'Área', 'Oficina', 'Turno',
            'Estado', 'Tipo de contrato', 'Modalidad', 'Fecha de ingreso', 'Fecha de baja',
        ], $rows);
    }

    private function attendance(ZipArchive $zip): void
    {
        $rows = [];

        AttendanceRecord::query()
            ->with('employee:id,employee_code,employee_number,first_name,last_name', 'office:id,name,timezone')
            ->orderBy('work_date')->orderBy('recorded_at')
            ->chunk(1000, function ($records) use (&$rows) {
                foreach ($records as $r) {
                    $at = $r->recorded_at->copy()->timezone($r->office?->timezone ?? 'America/Mexico_City');
                    $rows[] = [
                        $r->employee?->employee_code, $r->employee?->employee_number, $r->employee?->fullName(), $r->work_date->format('Y-m-d'),
                        $this->label($r->type), $at->format('H:i:s'), $this->label($r->status),
                        $r->minutes_late, $r->minutes_early, $r->is_justified ? 'Sí' : 'No',
                        $this->label($r->channel), $r->office?->name,
                    ];
                }
            });

        $this->csv($zip, 'asistencia.csv', [
            'Código', 'Número', 'Empleado', 'Fecha', 'Tipo', 'Hora', 'Estado', 'Minutos de retardo',
            'Minutos de salida anticipada', 'Justificada', 'Canal', 'Oficina',
        ], $rows);
    }

    private function requests(ZipArchive $zip): void
    {
        $rows = EmployeeRequest::query()->with('employee:id,employee_code,employee_number,first_name,last_name')->orderBy('id')->get()
            ->map(fn (EmployeeRequest $r) => [
                $r->employee?->employee_code, $r->employee?->employee_number, $r->employee?->fullName(), $this->label($r->type),
                $r->starts_on?->format('Y-m-d'), $r->ends_on?->format('Y-m-d'), $r->reason,
                $this->label($r->status), $r->created_at?->format('Y-m-d H:i'),
            ]);

        $this->csv($zip, 'solicitudes.csv', [
            'Código', 'Número', 'Empleado', 'Tipo', 'Desde', 'Hasta', 'Motivo', 'Estado', 'Solicitada',
        ], $rows);
    }

    private function catalogs(ZipArchive $zip): void
    {
        $this->csv($zip, 'areas.csv', ['Área'], Area::query()->orderBy('name')->pluck('name')->map(fn ($name) => [$name]));

        $this->csv($zip, 'oficinas.csv', ['Oficina', 'Dirección', 'Latitud', 'Longitud', 'Radio (m)', 'Zona horaria'],
            Office::query()->orderBy('name')->get()->map(fn (Office $o) => [
                $o->name, $o->address, $o->latitude, $o->longitude, $o->geofence_radius, $o->timezone,
            ]));

        $this->csv($zip, 'turnos.csv', ['Turno', 'Entrada', 'Salida', 'Días', 'Tolerancia (min)'],
            Shift::query()->orderBy('name')->get()->map(fn (Shift $s) => [
                $s->name, $s->starts_at, $s->ends_at, implode(',', (array) $s->weekdays), $s->tolerance_minutes,
            ]));
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    private function csv(ZipArchive $zip, string $name, array $headers, iterable $rows): void
    {
        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        rewind($out);
        $zip->addFromString($name, stream_get_contents($out));
        fclose($out);
    }

    private function label(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return method_exists($value, 'label') ? $value->label() : (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}
