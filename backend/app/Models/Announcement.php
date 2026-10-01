<?php

namespace App\Models;

use App\Enums\AudienceType;
use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['author_user_id', 'title', 'body', 'link_url', 'image_path', 'audience_type', 'audience_ids', 'show_on_kiosk', 'publish_at'])]
class Announcement extends Model
{
    use Auditable, BelongsToCompany;

    protected array $auditEvents = ['created'];

    protected function casts(): array
    {
        return [
            'audience_type' => AudienceType::class,
            'audience_ids' => 'array',
            'show_on_kiosk' => 'boolean',
            'publish_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)->withPivot('read_at');
    }
}
