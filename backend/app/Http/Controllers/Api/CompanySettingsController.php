<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Plan;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\PlanSeats;
use App\Support\StoredImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CompanySettingsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_owner, 403, 'Solo el dueño puede cambiar la configuración de la empresa.');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'employees_can_use_web' => ['sometimes', 'boolean'],
            'payroll_enabled' => ['sometimes', 'boolean'],
            'bonuses_enabled' => ['sometimes', 'boolean'],
            'timezone' => ['sometimes', 'timezone:all'],
        ]);

        $company = $request->user()->company;

        if (! $company->plan->includes_payroll && (($data['payroll_enabled'] ?? false) || ($data['bonuses_enabled'] ?? false))) {
            throw ValidationException::withMessages([
                'payroll_enabled' => "Tu plan {$company->plan->name} no incluye pre-nómina ni bonos.",
            ]);
        }

        $before = $company->only(array_keys($data));
        $company->update($data);

        $changes = collect($data)
            ->filter(fn ($value, $field) => $before[$field] != $value)
            ->map(fn ($value, $field) => ['old' => $before[$field], 'new' => $value])
            ->all();

        if ($changes) {
            ActivityLogger::log('settings_changed', description: 'Cambió la configuración de la empresa', changes: $changes);
        }

        return response()->json($company->only(array_keys($data)));
    }

    /**
     * Logotipo que se ve en la marca del sidebar. Cualquier usuario lo ve,
     * pero solo el dueño lo cambia.
     */
    public function logo(Request $request, StoredImage $storedImage): Response
    {
        $response = $storedImage->response($request->user()->company->logo_path);

        abort_if($response === null, 404);

        return $response;
    }

    public function updateLogo(Request $request, StoredImage $storedImage): JsonResponse
    {
        abort_unless($request->user()->is_owner, 403, 'Solo el dueño puede cambiar el logotipo.');

        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'photo.required' => 'Selecciona una imagen.',
            'photo.image' => 'El logotipo debe ser una imagen.',
            'photo.mimes' => 'Usa una imagen JPG, PNG o WebP.',
            'photo.max' => 'El logotipo no puede pesar más de 2 MB.',
        ]);

        $company = $request->user()->company;

        $company->forceFill([
            'logo_path' => $storedImage->store($data['photo'], $company->id, 'logos', $company->logo_path),
        ])->save();

        ActivityLogger::log('settings_changed', description: 'Cambió el logotipo de la empresa');

        return response()->json([
            'message' => 'Logotipo actualizado.',
            'logo_url' => StoredImage::url('web.empresa.logo.ver', $company->logo_path),
        ]);
    }

    public function destroyLogo(Request $request, StoredImage $storedImage): JsonResponse
    {
        abort_unless($request->user()->is_owner, 403, 'Solo el dueño puede quitar el logotipo.');

        $company = $request->user()->company;

        $storedImage->delete($company->logo_path);
        $company->forceFill(['logo_path' => null])->save();

        ActivityLogger::log('settings_changed', description: 'Quitó el logotipo de la empresa');

        return response()->json([
            'message' => 'Logotipo eliminado.',
            'logo_url' => null,
        ]);
    }

    /**
     * Plan contratado, consumo contra los límites y mensualidad estimada.
     */
    public function subscription(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_owner || $request->user()->hasRole('admin'), 403);

        $company = $request->user()->company->load('plan');

        return response()->json([
            'plan' => [
                ...$company->plan->only(['name', 'slug', 'monthly_price', 'employee_block_size', 'employee_block_price', 'extra_office_price', 'includes_payroll']),
                'yearly_price' => $company->plan->yearlyPrice(),
                'is_free' => $company->plan->isFree(),
            ],
            'billing_interval' => $company->billing_interval,
            'past_due_since' => $company->past_due_since,
            'database_tier' => $company->plan->database_tier->label(),
            'status' => $company->status->value,
            'status_label' => $company->status->label(),
            'trial_ends_at' => $company->trial_ends_at,
            'extras' => $company->only(['extra_employee_blocks', 'extra_offices']),
            'usage' => [
                'employees' => ['used' => Employee::query()->where('status', EmployeeStatus::Active)->count(), 'limit' => $company->employeeLimit(),
                    'locked' => count(PlanSeats::lockedEmployeeIds($company))],
                'offices' => ['used' => Office::query()->count(), 'limit' => $company->officeLimit()],
            ],
            // Informativo: los usuarios de la app no tienen límite propio
            'app_users' => User::query()->where('company_id', $company->id)->whereNotNull('employee_id')
                ->where('app_access', true)->whereNull('blocked_at')->count(),
            'monthly_price' => $company->estimatedMonthlyPrice(),
            'payment_method' => $company->pm_last_four ? "{$company->pm_type} •••• {$company->pm_last_four}" : null,
            'available_plans' => Plan::query()->where('is_active', true)->where('is_public', true)->orderBy('sort_order')
                ->get(['name', 'slug', 'monthly_price', 'included_employees', 'included_offices']),
        ]);
    }
}
