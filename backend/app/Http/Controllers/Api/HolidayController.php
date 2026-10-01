<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Support\ActivityLogger;
use App\Support\MexicanHolidays;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Días festivos: no cuentan como falta y, si se trabajan, se pagan doble adicional.
 */
class HolidayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = $request->integer('year', (int) now()->year);

        return response()->json(
            Holiday::query()->whereYear('date', $year)->orderBy('date')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', TenantRule::unique('holidays', 'date')],
            'name' => ['required', 'string', 'max:120'],
        ], [
            'date.unique' => 'Ya hay un festivo registrado ese día.',
        ]);

        $holiday = Holiday::query()->create([...$data, 'is_official' => false]);

        return response()->json($holiday, 201);
    }

    /**
     * Agrega los días de descanso obligatorio de la LFT del año (los que falten).
     */
    public function loadOfficial(Request $request): JsonResponse
    {
        $year = (int) $request->validate(['year' => ['required', 'integer', 'between:2020,2100']])['year'];
        $existing = Holiday::query()->whereYear('date', $year)->pluck('date')->map->toDateString()->all();
        $added = 0;

        foreach (MexicanHolidays::forYear($year) as $date => $name) {
            if (! in_array($date, $existing, true)) {
                Holiday::query()->create(['date' => $date, 'name' => $name, 'is_official' => true]);
                $added++;
            }
        }

        if ($added) {
            ActivityLogger::log('created', description: "Cargó {$added} días festivos oficiales de {$year}");
        }

        return response()->json([
            'added' => $added,
            'holidays' => Holiday::query()->whereYear('date', $year)->orderBy('date')->get(),
        ]);
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $holiday->delete();

        return response()->json(null, 204);
    }
}
