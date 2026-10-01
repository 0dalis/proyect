<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Registra en la bitácora las altas, cambios y bajas del modelo, con el
 * valor anterior y el nuevo de cada campo. Los campos ocultos (PIN, tokens)
 * se registran como "cambiado" sin mostrar su valor.
 *
 * Un modelo puede limitar los eventos con: protected array $auditEvents = ['created'];
 */
trait Auditable
{
    // Las fotos son rutas con UUID: se registran como una acción aparte, no
    // como el valor anterior y el nuevo del campo
    private static array $ignoredAuditFields = ['created_at', 'updated_at', 'deleted_at', 'last_used_at', 'last_seen_at', 'company_id', 'avatar_path', 'logo_path', 'photo_path'];

    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function (Model $model) use ($event) {
                if (! $model->audits($event)) {
                    return;
                }

                $changes = $event === 'updated' ? $model->auditChanges() : [];

                if ($event === 'updated' && $changes === []) {
                    return;
                }

                ActivityLogger::log($event, $model, changes: $changes);
            });
        }
    }

    public function audits(string $event): bool
    {
        $events = property_exists($this, 'auditEvents') ? $this->auditEvents : ['created', 'updated', 'deleted'];

        return in_array($event, $events, true);
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function auditChanges(): array
    {
        $changes = [];

        foreach ($this->getChanges() as $field => $new) {
            if (in_array($field, self::$ignoredAuditFields, true)) {
                continue;
            }

            $hidden = in_array($field, $this->getHidden(), true);
            $changes[$field] = [
                'old' => $hidden ? '••••' : $this->getOriginal($field),
                'new' => $hidden ? 'cambiado' : $this->getAttribute($field),
            ];
        }

        return $changes;
    }
}
