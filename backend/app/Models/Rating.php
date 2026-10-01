<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Opinión de un dueño o administrador sobre AsistControl (1 a 5 estrellas,
 * con medias). El Super Admin elige cuáles se muestran en la página.
 */
class Rating extends Model
{
    public const PENDING = 'pending';

    public const PUBLISHED = 'published';

    public const HIDDEN = 'hidden';

    public const STATUSES = [
        self::PENDING => 'Por revisar',
        self::PUBLISHED => 'En la página',
        self::HIDDEN => 'Oculta',
    ];

    protected $connection = 'central';

    protected $fillable = ['company_id', 'user_id', 'score', 'comment', 'allow_publish'];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'allow_publish' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Solo se publica si el usuario lo permitió y dejó un comentario.
     */
    public function canBePublished(): bool
    {
        return $this->allow_publish && filled($this->comment);
    }

    public function publish(): void
    {
        $this->forceFill(['status' => self::PUBLISHED, 'published_at' => now()])->save();
    }

    public function hide(): void
    {
        $this->forceFill(['status' => self::HIDDEN, 'published_at' => null])->save();
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED)->where('allow_publish', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
