<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Support\ActivityLogger;
use App\Support\AttendanceSummary;
use App\Support\StoredImage;
use App\Support\VacationBalance;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    /**
     * Datos editables de "Mi perfil". Si la cuenta está ligada a un empleado,
     * el nombre y el teléfono son los de su ficha (se mantienen sincronizados).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->company->database ? $user->employee() : null;

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'first_name' => $employee?->first_name,
            'last_name' => $employee?->last_name,
            'phone' => $employee?->phone,
            'position' => $employee?->position,
            'linked_employee' => $employee !== null,
            'last_login_at' => $user->last_login_at,
            'member_since' => $user->created_at,
            'other_sessions' => $this->otherSessions($request)->count() + $this->otherTokens($request)->count(),
        ]);
    }

    /**
     * El usuario edita su nombre, su correo de acceso y (si es empleado) su
     * teléfono. Cambiar el correo exige la contraseña actual: es su usuario.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->company->database ? $user->employee() : null;
        $emailChanged = $request->filled('email') && strcasecmp((string) $request->input('email'), $user->email) !== 0;

        $data = $request->validate([
            'name' => [$employee ? 'prohibited' : 'required', 'string', 'max:120'],
            'first_name' => [$employee ? 'required' : 'prohibited', 'string', 'max:80'],
            'last_name' => [$employee ? 'required' : 'prohibited', 'string', 'max:80'],
            'phone' => [$employee ? 'nullable' : 'prohibited', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => [Rule::requiredIf($emailChanged), 'nullable', 'current_password:sanctum'],
        ], [
            'email.unique' => 'Ese correo ya tiene una cuenta en AsistControl.',
            'current_password.required' => 'Escribe tu contraseña para cambiar el correo.',
            'current_password.current_password' => 'La contraseña es incorrecta.',
            'phone.regex' => 'Escribe un teléfono válido (solo números, espacios, +, - y paréntesis).',
            'name.prohibited' => 'Tu nombre se toma de tu ficha de empleado.',
        ]);

        if ($employee) {
            $employee->fill([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'phone' => $data['phone'] ?? null,
                // El correo de la ficha sigue al de la cuenta
                'email' => $data['email'],
            ])->save();
            $data['name'] = $employee->fullName();
        }

        $user->forceFill(['name' => trim($data['name']), 'email' => $data['email']])->save();

        ActivityLogger::log('updated', $user, $emailChanged
            ? "Actualizó su perfil y cambió su correo a {$data['email']}"
            : 'Actualizó su perfil');

        return response()->json([
            'message' => $emailChanged ? 'Perfil actualizado. Desde ahora entras con tu nuevo correo.' : 'Perfil actualizado.',
            'user' => new CurrentUserResource($user->refresh()),
        ]);
    }

    /**
     * Cierra las sesiones de otros navegadores y los tokens de la app,
     * dejando abierta solo la actual.
     */
    public function destroyOtherSessions(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
        ], [
            'current_password.current_password' => 'La contraseña es incorrecta.',
        ]);

        $closed = $this->otherSessions($request)->delete() + $this->otherTokens($request)->delete();

        ActivityLogger::log('updated', $request->user(), 'Cerró sus otras sesiones');

        return response()->json([
            'message' => $closed ? "Cerramos {$closed} sesión(es) en otros dispositivos." : 'No había otras sesiones abiertas.',
        ]);
    }

    private function otherSessions(Request $request): Builder
    {
        return DB::connection('central')->table('sessions')
            ->where('user_id', $request->user()->id)
            ->when($request->hasSession(), fn (Builder $query) => $query->where('id', '!=', $request->session()->getId()));
    }

    private function otherTokens(Request $request): Relation
    {
        $current = $request->user()->currentAccessToken();

        return $request->user()->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($query) => $query->whereKeyNot($current->getKey()));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'La contraseña actual es incorrecta.',
        ]);

        $user = $request->user();
        $user->update(['password' => $request->input('password')]);

        // Cierra las demás sesiones: tokens de la app y otras sesiones del navegador
        $current = $user->currentAccessToken();
        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->delete();

        if ($request->hasSession()) {
            DB::connection('central')->table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        ActivityLogger::log('updated', description: 'Cambió su contraseña');

        return response()->json(['message' => 'Contraseña actualizada. Cerramos tus otras sesiones.']);
    }

    /**
     * Primer acceso con la contraseña temporal del correo: el empleado elige
     * su contraseña y su PIN de 6 dígitos (el mismo sirve en el kiosko).
     */
    public function firstAccess(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            throw ValidationException::withMessages(['password' => 'Tu cuenta ya está configurada.']);
        }

        $employee = $user->employee();

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(),
                // No puede quedarse con la temporal
                function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                    if (Hash::check((string) $value, $user->password)) {
                        $fail('Elige una contraseña distinta a la temporal.');
                    }
                }],
            'pin' => [$employee ? 'required' : 'nullable', 'digits:6', 'confirmed'],
        ], [
            'pin.digits' => 'El PIN debe tener 6 dígitos.',
        ]);

        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        if ($employee) {
            $employee->setPin($request->input('pin'));
            $employee->save();
        }

        ActivityLogger::log('updated', $employee, 'Configuró su contraseña y su PIN (primer acceso)');

        return response()->json([
            'message' => 'Listo. Tu contraseña y tu PIN quedaron guardados.',
            'user' => new CurrentUserResource($user->refresh()),
        ]);
    }

    /**
     * El empleado cambia su PIN para checar (app y kiosko).
     */
    public function updatePin(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'pin' => ['required', 'digits:6', 'confirmed'],
        ], [
            'current_password.current_password' => 'La contraseña es incorrecta.',
        ]);

        $employee = $request->user()->employee();

        if (! $employee) {
            throw ValidationException::withMessages(['pin' => 'Tu usuario no está ligado a un empleado.']);
        }

        $employee->setPin($request->input('pin'));
        $employee->save();
        ActivityLogger::log('updated', $employee, 'Cambió su PIN para checar');

        return response()->json(['message' => 'PIN actualizado.']);
    }

    /**
     * Foto de la cuenta que se ve en el navbar y en "Mi perfil".
     * En disco solo queda la ruta relativa (avatars/{empresa}/{uuid}.ext).
     */
    public function avatar(Request $request, StoredImage $storedImage): Response
    {
        $response = $storedImage->response($request->user()->avatar_path);

        abort_if($response === null, 404);

        return $response;
    }

    /**
     * Sube o reemplaza la foto de la cuenta. Se usa FormData (multipart).
     */
    public function updateAvatar(Request $request, StoredImage $storedImage): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'photo.required' => 'Selecciona una imagen.',
            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'Usa una imagen JPG, PNG o WebP.',
            'photo.max' => 'La foto no puede pesar más de 2 MB.',
        ]);

        // Borra la anterior y guarda solo la ruta relativa
        $user->forceFill([
            'avatar_path' => $storedImage->store($data['photo'], $user->company_id, 'avatars', $user->avatar_path),
        ])->save();

        ActivityLogger::log('updated', $user, 'Actualizó su foto de perfil');

        return response()->json([
            'message' => 'Foto actualizada.',
            'user' => new CurrentUserResource($user->refresh()),
        ]);
    }

    public function destroyAvatar(Request $request, StoredImage $storedImage): JsonResponse
    {
        $user = $request->user();

        $storedImage->delete($user->avatar_path);
        $user->forceFill(['avatar_path' => null])->save();

        ActivityLogger::log('updated', $user, 'Quitó su foto de perfil');

        return response()->json([
            'message' => 'Foto eliminada.',
            'user' => new CurrentUserResource($user->refresh()),
        ]);
    }

    /**
     * Resumen del mes del propio empleado.
     */
    public function summary(Request $request, AttendanceSummary $summary): JsonResponse
    {
        $employee = $request->user()->employee();

        if (! $employee) {
            return response()->json(['metrics' => null]);
        }

        $employee->load('shift', 'office:id,name,timezone', 'area:id,name');
        // El mes y "hoy" son los de su oficina
        $today = now($employee->office?->timezone ?? config('app.timezone'));
        $from = $today->copy()->startOfMonth();

        return response()->json([
            'period' => ['from' => $from->toDateString(), 'to' => $today->toDateString()],
            'metrics' => $summary->forEmployees(collect([$employee]), $from, $today)[$employee->id],
            'vacation' => VacationBalance::for($employee),
            'shift' => $employee->shift->only(['name', 'starts_at', 'ends_at', 'break_minutes', 'weekdays', 'day_schedules', 'tolerance_minutes']),
            'timezone' => $employee->office?->timezone,
            'office' => $employee->office?->name,
            'area' => $employee->area?->name,
        ]);
    }
}
