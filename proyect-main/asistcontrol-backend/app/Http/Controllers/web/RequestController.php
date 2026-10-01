<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Models\Request as RequestModel;
use App\Models\Shift;
use App\Models\VacationBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $query = RequestModel::where('company_id', $company->id)
            ->with(['user:id,email', 'user.employee:id,user_id,first_name,last_name,employee_code,office_id,area_id', 'approver:id,email'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json(['requests' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'type' => 'required|in:permission,justification,vacation',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:1000',
            'is_paid' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $company->employees()->findOrFail($request->employee_id);

        if (! $employee->user_id) {
            return response()->json(['errors' => ['employee_id' => ['El empleado no tiene cuenta de usuario asociada.']]], 422);
        }

        $model = RequestModel::create([
            'company_id' => $company->id,
            'user_id' => $employee->user_id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reason' => $request->reason,
            'status' => 'pending',
            'is_paid' => $request->boolean('is_paid'),
        ]);

        return response()->json(['message' => 'Solicitud creada.', 'request' => $model], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $company = $this->getCompany($request);
        $model = RequestModel::where('company_id', $company->id)->findOrFail($id);

        if ($model->isApproved()) {
            return response()->json(['message' => 'No se puede editar una solicitud aprobada.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:permission,justification,vacation',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
            'is_paid' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $model->update($request->only(['type', 'start_date', 'end_date', 'start_time', 'end_time', 'reason']) + [
            'is_paid' => $request->boolean('is_paid'),
        ]);

        return response()->json(['message' => 'Solicitud actualizada.', 'request' => $model]);
    }

    public function approve(Request $request, $id): JsonResponse
    {
        return $this->resolve($request, $id, 'approved');
    }

    public function reject(Request $request, $id): JsonResponse
    {
        return $this->resolve($request, $id, 'rejected');
    }

    private function resolve(Request $request, $id, string $status): JsonResponse
    {
        $company = $this->getCompany($request);
        $model = RequestModel::where('company_id', $company->id)->findOrFail($id);

        if (! $model->isPending()) {
            return response()->json(['message' => 'La solicitud ya fue resuelta.'], 422);
        }

        if ($status === 'approved' && $model->type === 'vacation') {
            return $this->approveVacation($request, $company, $model);
        }

        $model->update([
            'status' => $status,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Solicitud ' . ($status === 'approved' ? 'aprobada' : 'rechazada') . '.', 'request' => $model]);
    }

    private function approveVacation(Request $request, Company $company, RequestModel $model): JsonResponse
    {
        $employee = $company->employees()->where('user_id', $model->user_id)->first();
        $start = Carbon::parse($model->start_date);
        $end = Carbon::parse($model->end_date ?? $model->start_date);

        $workDays = $employee?->shift?->getWorkDays() ?? Shift::DEFAULT_WORK_DAYS;
        $holidays = CompanyHoliday::where('company_id', $company->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $dates = $this->workingDates($start, $end, $workDays, $holidays);
        $days = count($dates);

        if ($days === 0) {
            return response()->json(['message' => 'El rango no contiene días laborables.'], 422);
        }

        $year = $start->year;
        $defaultDays = $company->setting?->default_vacation_days ?? 12;

        $balance = VacationBalance::firstOrCreate(
            ['employee_id' => $employee?->id ?? 0, 'year' => $year],
            ['days_entitled' => $defaultDays, 'days_used' => 0]
        );

        $available = $balance->days_entitled - $balance->days_used;

        if ($days > $available) {
            return response()->json([
                'message' => "Días insuficientes: solicita {$days} día(s) y solo tiene {$available} disponible(s).",
                'days_requested' => $days,
                'days_available' => $available,
            ], 422);
        }

        DB::transaction(function () use ($company, $employee, $model, $dates, $balance, $request) {
            $balance->increment('days_used', count($dates));

            $model->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'is_paid' => true,
            ]);

            if ($employee) {
                foreach ($dates as $date) {
                    Attendance::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'date' => $date,
                        ],
                        [
                            'user_id' => $employee->user_id,
                            'office_id' => $employee->office_id,
                            'shift_id' => $employee->shift_id,
                            'status' => 'justified',
                            'leave_type' => 'vacation',
                            'request_id' => $model->id,
                            'worked_minutes' => 0,
                        ]
                    );
                }
            }
        });

        return response()->json([
            'message' => 'Vacaciones aprobadas.',
            'days' => $days,
            'dates' => $dates,
            'request' => $model->fresh(),
        ]);
    }

    /**
     * @param  array<int,int>  $workDays
     * @param  array<int,string>  $holidays
     * @return array<int,string>
     */
    private function workingDates(Carbon $start, Carbon $end, array $workDays, array $holidays): array
    {
        $dates = [];
        $cursor = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if (in_array($cursor->dayOfWeekIso, $workDays, true) && ! in_array($cursor->toDateString(), $holidays, true)) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $dates;
    }

    public function balances(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $year = (int) $request->input('year', now()->year);
        $defaultDays = $company->setting?->default_vacation_days ?? 12;

        $balances = $company->employees()
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) use ($year, $defaultDays) {
                $balance = VacationBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'year' => $year],
                    ['days_entitled' => $defaultDays, 'days_used' => 0]
                );

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'employee_code' => $employee->employee_code,
                    'year' => $year,
                    'days_entitled' => (float) $balance->days_entitled,
                    'days_used' => (float) $balance->days_used,
                    'days_available' => (float) ($balance->days_entitled - $balance->days_used),
                ];
            });

        return response()->json(['balances' => $balances]);
    }

    public function updateBalance(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer',
            'days_entitled' => 'required|numeric|min:0',
            'days_used' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $balance = VacationBalance::updateOrCreate(
            ['employee_id' => $employee->id, 'year' => $request->year],
            ['days_entitled' => $request->days_entitled, 'days_used' => $request->input('days_used', 0)]
        );

        return response()->json(['message' => 'Saldo actualizado.', 'balance' => $balance]);
    }

    /**
     * Calendario de un empleado (o de toda la empresa) con tipos por día.
     */
    public function calendar(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $from = Carbon::parse($request->from)->startOfDay();
        $to = Carbon::parse($request->to)->endOfDay();

        $attendances = Attendance::where('company_id', $company->id)
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $holidays = $company->holidays()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn ($h) => ['date' => Carbon::parse($h->date)->toDateString(), 'name' => $h->name]);

        $days = $attendances->map(fn ($a) => [
            'date' => Carbon::parse($a->date)->toDateString(),
            'employee_id' => $a->employee_id,
            'status' => $a->status,
            'leave_type' => $a->leave_type,
            'worked_minutes' => $a->worked_minutes,
        ])->values();

        return response()->json([
            'days' => $days,
            'holidays' => $holidays,
        ]);
    }
}
