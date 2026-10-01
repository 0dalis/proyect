<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Models\BonusRule;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Support\ActivityLogger;
use App\Support\Csv;
use App\Support\PayrollCalculator;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pre-nómina. Se calcula al momento para cualquier rango; al cerrar un
 * periodo, el cálculo queda guardado y esas fechas ya no se pueden modificar
 * (checadas, justificaciones, solicitudes) hasta que el dueño lo reabra.
 */
class PayrollController extends Controller
{
    private const CSV_HEADERS = [
        'No.', 'Empleado', 'Área', 'Días trabajados', 'Faltas sin justificar', 'Retardos sin justificar',
        'Horas extra dobles', 'Horas extra triples', 'Festivos trabajados',
        'Sueldo del periodo', 'Descuentos', 'Tiempo extra', 'Festivos', 'Bonos', 'Total',
    ];

    public function __construct(private PayrollCalculator $calculator, private TenantManager $tenants) {}

    public function index(Request $request): JsonResponse|StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $rows = $this->rows($from, $to);
        $closed = PayrollPeriod::query()->overlapping($from, $to)->get(['id', 'name', 'starts_on', 'ends_on']);

        if ($request->query('format') === 'csv') {
            ActivityLogger::log('exported', description: "Exportó la pre-nómina del {$from->format('d/m/Y')} al {$to->format('d/m/Y')}");

            return $this->csv("prenomina-{$from->toDateString()}-{$to->toDateString()}.csv", $rows);
        }

        return response()->json([
            'rows' => $rows,
            'totals' => $this->totals($rows),
            'bonuses_enabled' => $this->tenants->currentOrFail()->bonuses_enabled,
            // Si el rango toca un periodo cerrado, el panel lo avisa
            'closed_periods' => $closed,
        ]);
    }

    public function periods(): JsonResponse
    {
        return response()->json(
            PayrollPeriod::query()->withCount('items')->orderByDesc('starts_on')->get()
        );
    }

    /**
     * Cierra el periodo: guarda el cálculo de cada empleado y bloquea esas fechas.
     */
    public function close(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);
        $data = $request->validate(['name' => ['nullable', 'string', 'max:120']]);
        $company = $this->tenants->currentOrFail();

        // Solo se cierra lo que ya pasó en la zona horaria de la empresa
        if ($to->toDateString() > now($company->timezone)->toDateString()) {
            throw ValidationException::withMessages(['to' => 'Solo puedes cerrar fechas que ya terminaron.']);
        }

        if ($overlap = PayrollPeriod::query()->overlapping($from, $to)->first()) {
            throw ValidationException::withMessages([
                'from' => "Esas fechas se cruzan con \"{$overlap->name}\", que ya está cerrado.",
            ]);
        }

        $rows = $this->rows($from, $to);
        $user = $request->user();

        $period = DB::connection('tenant')->transaction(function () use ($data, $from, $to, $rows, $user) {
            $period = PayrollPeriod::query()->create([
                'name' => $data['name'] ?? "Del {$from->format('d/m/Y')} al {$to->format('d/m/Y')}",
                'starts_on' => $from->toDateString(),
                'ends_on' => $to->toDateString(),
                'closed_by' => $user->id,
                'closed_by_name' => $user->name,
                'closed_at' => now(),
                'totals' => $this->totals($rows),
            ]);

            foreach ($rows as $row) {
                $period->items()->create([
                    'employee_id' => $row['employee']['id'],
                    'data' => $row,
                    'total' => $row['total'],
                ]);
            }

            return $period;
        });

        ActivityLogger::log('payroll_closed', $period, "Cerró la pre-nómina \"{$period->name}\"");

        return response()->json($period->loadCount('items'), 201);
    }

    /**
     * Pre-nómina cerrada tal como quedó (no se recalcula).
     */
    public function show(Request $request, PayrollPeriod $period): JsonResponse|StreamedResponse
    {
        $rows = $period->items()->get()->pluck('data')->sortBy('employee.name')->values()->all();

        if ($request->query('format') === 'csv') {
            ActivityLogger::log('exported', $period, "Exportó la pre-nómina cerrada \"{$period->name}\"");

            return $this->csv("prenomina-{$period->starts_on->toDateString()}-{$period->ends_on->toDateString()}-cerrada.csv", $rows);
        }

        return response()->json([...$period->toArray(), 'rows' => $rows]);
    }

    /**
     * Reabrir: se borra lo guardado y las fechas vuelven a poder modificarse.
     * Solo el dueño (routes/web/nomina.php).
     */
    public function reopen(PayrollPeriod $period): JsonResponse
    {
        $name = $period->name;
        ActivityLogger::log('payroll_reopened', $period, "Reabrió la pre-nómina \"{$name}\"");
        $period->delete();

        return response()->json(['message' => "Reabriste \"{$name}\"."]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return [$request->date('from')->startOfDay(), $request->date('to')->endOfDay()];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(Carbon $from, Carbon $to): array
    {
        $company = $this->tenants->currentOrFail();

        $employees = Employee::query()
            ->with('shift', 'office', 'area:id,name')
            ->where(fn ($query) => $query->where('status', EmployeeStatus::Active)->orWhereDate('terminated_on', '>=', $from))
            ->orderBy('first_name')
            ->get();

        $rules = $company->moduleAvailable('bonuses') ? BonusRule::query()->where('is_active', true)->get() : collect();

        return $this->calculator->calculate($employees, $from, $to, $rules);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function totals(array $rows): array
    {
        $sum = fn (callable $value) => round(array_sum(array_map($value, $rows)), 2);

        return [
            'employees' => count($rows),
            'base' => $sum(fn ($row) => $row['base']),
            'deductions' => $sum(fn ($row) => $row['deductions']),
            'overtime' => $sum(fn ($row) => $row['overtime']['amount'] ?? 0),
            'holidays' => $sum(fn ($row) => $row['holiday_pay'] ?? 0),
            'bonuses' => $sum(fn ($row) => $row['bonus_total']),
            'total' => $sum(fn ($row) => $row['total']),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function csv(string $filename, array $rows): StreamedResponse
    {
        return Csv::download($filename, self::CSV_HEADERS, array_map(fn ($row) => [
            $row['employee']['employee_code'], $row['employee']['name'], $row['employee']['area'],
            $row['metrics']['worked_days'], $row['metrics']['unjustified_absences'], $row['metrics']['unjustified_lates'],
            $row['overtime']['double_hours'] ?? 0, $row['overtime']['triple_hours'] ?? 0,
            $row['metrics']['holidays_worked'] ?? 0,
            $row['base'], $row['deductions'], $row['overtime']['amount'] ?? 0, $row['holiday_pay'] ?? 0,
            $row['bonus_total'], $row['total'],
        ], $rows));
    }
}
