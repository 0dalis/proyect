<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Shift;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Shift::query()
                ->when($request->integer('office_id'), fn ($query, $officeId) => $query->where('office_id', $officeId))
                ->with('office:id,name')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(Shift::query()->create($this->validated($request)), 201);
    }

    public function update(Request $request, Shift $shift): JsonResponse
    {
        $shift->update($this->validated($request, $shift));

        return response()->json($shift);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        if (Shift::query()->where('office_id', $shift->office_id)->count() === 1) {
            throw ValidationException::withMessages(['shift' => 'Cada oficina debe tener al menos un turno.']);
        }

        if (Employee::query()->where('shift_id', $shift->id)->exists()) {
            throw ValidationException::withMessages(['shift' => 'Reasigna a los empleados de este turno antes de eliminarlo.']);
        }

        $shift->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, ?Shift $shift = null): array
    {
        $required = $shift ? 'sometimes' : 'required';

        $data = $request->validate([
            'office_id' => [$required, TenantRule::exists('offices')],
            'name' => [$required, 'string', 'max:120'],
            'starts_at' => [$required, 'date_format:H:i'],
            'ends_at' => [$required, 'date_format:H:i', 'different:starts_at'],
            // Comida o descanso sin goce; se descuenta de las horas trabajadas
            'break_minutes' => ['sometimes', 'integer', 'min:0', 'max:240'],
            'weekdays' => [$required, 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            // Horario especial de algunos días: {"5": {"starts_at": "09:00", "ends_at": "17:00", "break_minutes": 60}}
            'day_schedules' => ['sometimes', 'nullable', 'array'],
            'day_schedules.*.starts_at' => ['required', 'date_format:H:i'],
            'day_schedules.*.ends_at' => ['required', 'date_format:H:i', 'different:day_schedules.*.starts_at'],
            'day_schedules.*.break_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'tolerance_minutes' => [$required, 'integer', 'min:0', 'max:240'],
            'absence_after_minutes' => [$required, 'integer', 'min:0', 'max:720', 'gte:tolerance_minutes'],
        ], [
            'absence_after_minutes.gte' => 'El límite para falta debe ser mayor o igual a la tolerancia.',
        ]);

        if (isset($data['weekdays'])) {
            $data['weekdays'] = collect($data['weekdays'])->map(fn ($day) => (int) $day)->sort()->values()->all();
        }

        if (array_key_exists('day_schedules', $data)) {
            $data['day_schedules'] = $this->daySchedules($data['day_schedules'] ?? [], $data['weekdays'] ?? $shift?->weekdays ?? []);
        }

        return $data;
    }

    /**
     * Solo días activos del turno; sin horarios especiales se guarda null.
     *
     * @param  array<int|string, array{starts_at: string, ends_at: string, break_minutes?: int|null}>  $schedules
     * @param  list<int>  $weekdays
     * @return array<string, array{starts_at: string, ends_at: string, break_minutes: int|null}>|null
     */
    private function daySchedules(array $schedules, array $weekdays): ?array
    {
        $result = [];

        foreach ($schedules as $day => $schedule) {
            if (! in_array((int) $day, array_map('intval', $weekdays), true)) {
                throw ValidationException::withMessages([
                    'day_schedules' => 'El horario especial solo puede ser de un día activo del turno.',
                ]);
            }

            $result[(string) (int) $day] = [
                'starts_at' => $schedule['starts_at'],
                'ends_at' => $schedule['ends_at'],
                'break_minutes' => isset($schedule['break_minutes']) ? (int) $schedule['break_minutes'] : null,
            ];
        }

        ksort($result);

        return $result ?: null;
    }
}
