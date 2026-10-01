<?php

namespace App\Models;

use App\Support\EncryptedId;
use App\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id', 'user_name', 'action', 'subject_type', 'subject_id', 'subject_label',
    'employee_id', 'description', 'changes', 'user_agent',
])]
class ActivityLog extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    /** Token cifrado del empleado para enlazarlo sin exponer su id. */
    protected $appends = ['employee_public_id'];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected function employeePublicId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->employee_id ? EncryptedId::encode((string) $this->employee_id) : null,
        );
    }
}
