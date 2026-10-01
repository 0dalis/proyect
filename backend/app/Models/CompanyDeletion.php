<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Solicitud de baja de una empresa. En proceso durante 30 días; después queda
 * como registro histórico (sin datos personales).
 */
#[Fillable([
    'company_id', 'company_name', 'last_plan', 'billing_interval', 'stripe_customer_id',
    'registered_at', 'requested_at', 'days_in_system', 'reason', 'purge_after',
    'owner_name', 'owner_email',
])]
#[Hidden(['export_token_hash', 'feedback_token', 'owner_email'])]
class CompanyDeletion extends Model
{
    /** Horas que dura cada enlace de exportación. */
    public const EXPORT_LINK_HOURS = 48;

    /** Días entre la solicitud y el borrado. */
    public const RETENTION_DAYS = 30;

    protected $connection = 'central';

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'requested_at' => 'datetime',
            'purge_after' => 'datetime',
            'purged_at' => 'datetime',
            'export_expires_at' => 'datetime',
            'export_downloaded_at' => 'datetime',
            'feedback_submitted_at' => 'datetime',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeInProgress(Builder $query): void
    {
        $query->whereNull('purged_at');
    }

    /** @param Builder<self> $query */
    public function scopePurged(Builder $query): void
    {
        $query->whereNotNull('purged_at');
    }

    public function isInProgress(): bool
    {
        return $this->purged_at === null;
    }

    /**
     * Días que faltan para el borrado (0 = hoy).
     */
    public function daysLeft(): int
    {
        return max(0, (int) ceil(now()->diffInHours($this->purge_after, false) / 24));
    }

    public function exportLinkIsValid(): bool
    {
        return $this->isInProgress() && $this->export_expires_at?->isFuture();
    }

    /**
     * Genera un enlace nuevo (el anterior deja de servir) y devuelve el token.
     */
    public function issueExportToken(): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'export_token_hash' => hash('sha256', $token),
            'export_expires_at' => now()->addHours(self::EXPORT_LINK_HOURS),
            'export_links_sent' => $this->export_links_sent + 1,
        ])->save();

        return $token;
    }

    public static function findByExportToken(string $token): ?self
    {
        return self::query()->where('export_token_hash', hash('sha256', $token))->first();
    }
}
