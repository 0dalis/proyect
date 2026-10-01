<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegisterAttendance;
use App\Enums\AttendanceChannel;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Kiosk;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API que usa la pantalla del kiosko (autenticada con X-Kiosk-Token).
 */
class KioskTerminalController extends Controller
{
    public function show(Request $request, TenantManager $tenants): JsonResponse
    {
        /** @var Kiosk $kiosk */
        $kiosk = $request->attributes->get('kiosk');

        return response()->json([
            'kiosk' => $kiosk->only(['id', 'name']),
            'office' => $kiosk->office->only(['id', 'name', 'timezone']),
            'company' => $tenants->currentOrFail()->only(['id', 'name']),
        ]);
    }

    /**
     * Checada con el QR de la credencial o con número de empleado + PIN.
     * La foto (opcional) es evidencia contra checar por otra persona.
     */
    public function punch(Request $request, RegisterAttendance $registerAttendance): JsonResponse
    {
        /** @var Kiosk $kiosk */
        $kiosk = $request->attributes->get('kiosk');

        $data = $request->validate([
            'method' => ['required', 'in:qr,pin'],
            'qr' => ['required_if:method,qr', 'nullable', 'string', 'max:100'],
            'employee_number' => ['required_if:method,pin', 'nullable', 'string', 'max:30'],
            'pin' => ['required_if:method,pin', 'nullable', 'digits:6'],
            'photo' => ['nullable', 'string'],
        ]);

        $employee = $data['method'] === 'qr'
            ? $this->employeeFromQr($data['qr'])
            : $this->employeeFromPin($kiosk, $data['employee_number'], $data['pin']);

        $record = $registerAttendance->handle(
            $employee,
            $data['method'] === 'qr' ? AttendanceChannel::KioskQr : AttendanceChannel::KioskPin,
            [
                'kiosk_id' => $kiosk->id,
                'photo_path' => $this->storePhoto($kiosk, $data['photo'] ?? null),
            ],
        );

        return response()->json([
            ...AttendanceController::present($record),
            'employee' => ['name' => $employee->fullName(), 'employee_code' => $employee->employee_code],
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * "Consultar mi asistencia": para quien no tiene la app (o se quedó sin
     * batería). Con su credencial o número + PIN ve sus últimos 14 días.
     */
    public function myAttendance(Request $request): JsonResponse
    {
        /** @var Kiosk $kiosk */
        $kiosk = $request->attributes->get('kiosk');

        $data = $request->validate([
            'method' => ['required', 'in:qr,pin'],
            'qr' => ['required_if:method,qr', 'nullable', 'string', 'max:100'],
            'employee_number' => ['required_if:method,pin', 'nullable', 'string', 'max:30'],
            'pin' => ['required_if:method,pin', 'nullable', 'digits:6'],
        ]);

        $employee = $data['method'] === 'qr'
            ? $this->employeeFromQr($data['qr'])
            : $this->employeeFromPin($kiosk, $data['employee_number'], $data['pin']);

        $timezone = $kiosk->office->timezone;
        $from = now($timezone)->subDays(13)->toDateString();

        $days = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', $from)
            ->orderByDesc('work_date')
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(fn (AttendanceRecord $record) => $record->work_date->toDateString())
            ->map(function ($records, string $date) use ($timezone) {
                $in = $records->firstWhere('type.value', 'check_in');
                $out = $records->firstWhere('type.value', 'check_out');

                return [
                    'date' => $date,
                    'check_in' => $in?->recorded_at->copy()->timezone($timezone)->format('H:i'),
                    'check_out' => $out?->recorded_at->copy()->timezone($timezone)->format('H:i'),
                    'status' => $in?->status->value,
                    'status_label' => $in?->status->label(),
                    'minutes_late' => $in?->minutes_late ?? 0,
                    'is_justified' => (bool) $in?->is_justified,
                ];
            })
            ->values();

        return response()->json([
            'employee' => ['name' => $employee->fullName(), 'employee_code' => $employee->employee_code],
            'from' => $from,
            'days' => $days,
            'late_count' => $days->where('status', 'late')->where('is_justified', false)->count(),
        ]);
    }

    public function news(): JsonResponse
    {
        return response()->json(AnnouncementController::newsQuery()->limit(10)->get());
    }

    private function employeeFromQr(string $token): Employee
    {
        $employee = Employee::query()->where('badge_token', $token)->first();

        if (! $employee || ! $employee->badgeIsValid()) {
            throw ValidationException::withMessages(['qr' => 'Credencial no válida o vencida.']);
        }

        return $employee;
    }

    /**
     * El teclado del kiosko es numérico: acepta la parte numérica del código
     * impreso (ALDE-0006 -> 6 o 0006) o el número interno (00006) de las
     * credenciales antiguas, que siguen funcionando.
     */
    private function employeeFromPin(Kiosk $kiosk, string $employeeNumber, string $pin): Employee
    {
        $key = "kiosk-pin:{$kiosk->company_id}:{$kiosk->id}:{$employeeNumber}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['pin' => 'Demasiados intentos. Espera un minuto.']);
        }

        $employee = $this->employeeByIdentifier($employeeNumber);

        if (! $employee || ! $employee->checkPin($pin)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['pin' => 'Número de empleado o PIN incorrecto.']);
        }

        RateLimiter::clear($key);

        return $employee;
    }

    private function employeeByIdentifier(string $value): ?Employee
    {
        if (! preg_match('/^\d+$/', $value)) {
            return Employee::query()->where('employee_code', strtoupper($value))->first();
        }

        return Employee::query()->where(fn ($query) => $query
            ->where('employee_number', $value)
            ->orWhere('employee_code', 'like', '%-'.str_pad($value, 4, '0', STR_PAD_LEFT)))
            ->first();
    }

    private function storePhoto(Kiosk $kiosk, ?string $dataUri): ?string
    {
        if (! $dataUri || ! preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/', $dataUri, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            return null;
        }

        $path = sprintf('attendance/%d/%s/%s.%s', $kiosk->company_id, now()->format('Y/m/d'), Str::uuid(), $matches[1]);
        Storage::disk('local')->put($path, $binary);

        return $path;
    }
}
