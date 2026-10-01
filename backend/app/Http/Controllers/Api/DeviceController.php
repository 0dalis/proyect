<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $devices = Device::query()
            ->with('employee:id,first_name,last_name')
            ->when(
                ! $user->can(Permission::UsersManage->value),
                fn ($query) => $query->where('employee_id', $user->employee_id ?? 0),
                fn ($query) => $query->when($request->integer('employee_id'), fn ($q, $id) => $q->where('employee_id', $id)),
            )
            ->latest()
            ->get();

        return response()->json($devices);
    }

    /**
     * El celular registra su llave pública. El primer celular del empleado se
     * autoriza solo; un cambio de celular requiere que un admin lo apruebe.
     */
    public function store(Request $request): JsonResponse
    {
        $employee = $request->user()->employee();

        if (! $employee) {
            throw ValidationException::withMessages(['device' => 'Tu usuario no está ligado a un empleado.']);
        }

        $data = $request->validate([
            'device_identifier' => ['required', 'string', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'in:android,ios'],
            'public_key' => ['required', 'string', 'starts_with:-----BEGIN PUBLIC KEY-----'],
            'push_token' => ['nullable', 'string', 'max:500'],
        ]);

        if (openssl_pkey_get_public($data['public_key']) === false) {
            throw ValidationException::withMessages(['public_key' => 'La llave pública no es válida.']);
        }

        $hasApprovedDevice = $employee->devices()->whereNotNull('approved_at')->whereNull('revoked_at')->exists();

        $device = $employee->devices()->updateOrCreate(
            ['device_identifier' => $data['device_identifier']],
            $data,
        );

        if (! $device->approved_at && ! $hasApprovedDevice) {
            $device->forceFill(['approved_at' => now(), 'revoked_at' => null])->save();
        }

        return response()->json([
            'id' => $device->id,
            'approved' => $device->isUsable(),
            'message' => $device->isUsable()
                ? 'Celular registrado. Ya puedes checar.'
                : 'Celular registrado. Un administrador debe autorizarlo porque ya tienes otro celular.',
        ], 201);
    }

    public function approve(Device $device): JsonResponse
    {
        // Solo un celular activo por empleado
        Device::query()->where('employee_id', $device->employee_id)->whereKeyNot($device->id)
            ->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $device->forceFill(['approved_at' => now(), 'revoked_at' => null])->save();

        return response()->json($device);
    }

    public function revoke(Device $device): JsonResponse
    {
        $device->forceFill(['revoked_at' => now()])->save();

        return response()->json($device);
    }
}
