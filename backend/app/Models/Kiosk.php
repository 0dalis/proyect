<?php

namespace App\Models;

use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['office_id', 'name', 'is_active'])]
#[Hidden(['token_hash'])]
class Kiosk extends Model
{
    use Auditable, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Genera el token del dispositivo con formato {empresa}|{kiosko}|{secreto}.
     * Solo se muestra una vez; se guarda su hash.
     */
    public function issueToken(): string
    {
        $secret = Str::random(40);
        $this->token_hash = hash('sha256', $secret);
        $this->save();

        return "{$this->company_id}|{$this->id}|{$secret}";
    }
}
