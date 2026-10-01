<?php

namespace App\Models;

use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Notificación de la campana del panel para un usuario.
 */
class PanelNotification extends Model
{
    use BelongsToCompany;

    public const REQUEST_SUBMITTED = 'request_submitted';

    public const REQUEST_APPROVED = 'request_approved';

    public const REQUEST_REJECTED = 'request_rejected';

    public const ANNOUNCEMENT = 'announcement';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'link', 'subject_type', 'subject_id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function scopeFor(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }
}
