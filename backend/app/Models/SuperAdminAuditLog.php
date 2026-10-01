<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Bitácora del Super Admin: qué hizo, cuándo y sobre qué empresa. Solo se
 * agregan filas; nadie las edita ni las borra desde el panel.
 */
class SuperAdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'central';

    protected $guarded = ['id'];

    /** Etiquetas de las acciones para la tabla y el filtro. */
    public const ACTIONS = [
        'login' => 'Inicio de sesión',
        'mfa.enabled' => '2FA activada',
        'mfa.disabled' => '2FA desactivada',
        'company.updated' => 'Empresa editada',
        'company.trial_extended' => 'Prueba extendida',
        'company.suspended' => 'Empresa suspendida',
        'company.reactivated' => 'Empresa reactivada',
        'deletion.export_resent' => 'Reenvió la exportación',
        'plan.created' => 'Plan creado',
        'plan.updated' => 'Plan editado',
        'plan.deleted' => 'Plan eliminado',
        'settings.updated' => 'Ajustes guardados',
        'super_admin.created' => 'Super Admin creado',
        'super_admin.updated' => 'Super Admin editado',
        'super_admin.deleted' => 'Super Admin eliminado',
        'rating.published' => 'Opinión publicada',
        'rating.hidden' => 'Opinión ocultada',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $action, string $description, ?Company $company = null, array $properties = [], ?SuperAdmin $admin = null): self
    {
        $admin ??= Auth::guard('super_admin')->user();

        return static::query()->create([
            'super_admin_id' => $admin?->getKey(),
            'super_admin_name' => $admin?->name,
            'action' => $action,
            'company_id' => $company?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
        ]);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
