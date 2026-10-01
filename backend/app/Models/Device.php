<?php

namespace App\Models;

use App\Support\Auditable;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'device_identifier', 'name', 'platform', 'public_key', 'push_token'])]
#[Hidden(['public_key', 'push_token'])]
class Device extends Model
{
    use Auditable, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isUsable(): bool
    {
        return $this->approved_at !== null && $this->revoked_at === null;
    }

    /**
     * Verifica la firma hecha con la llave privada del celular, que solo se
     * desbloquea con huella, Face ID o el PIN del dispositivo.
     */
    public function verifySignature(string $payload, string $base64Signature): bool
    {
        $signature = base64_decode($base64Signature, true);

        return $signature !== false
            && openssl_verify($payload, $signature, $this->public_key, OPENSSL_ALGO_SHA256) === 1;
    }
}
