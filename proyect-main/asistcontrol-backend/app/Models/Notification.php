<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'message',
        'target_type',
        'area_id',
        'office_id',
        'target_user_id',
        'target_user_ids',
        'scheduled_at',
        'sent_at',
        'expires_at',
        'is_active',
        'priority',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'target_user_ids' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    /**
     * Usuarios objetivo de la notificación (sin filtrar por dispositivo).
     */
    public function recipientUsers(Company $company)
    {
        $query = $company->users()->with('employee:id,user_id,first_name,last_name,area_id,office_id');

        switch ($this->target_type) {
            case 'area':
                $query->whereHas('employee', fn ($q) => $q->where('area_id', $this->area_id));
                break;
            case 'office':
                $query->whereHas('employee', fn ($q) => $q->where('office_id', $this->office_id));
                break;
            case 'user':
                $query->where('id', $this->target_user_id);
                break;
            case 'users':
                $query->whereIn('id', $this->target_user_ids ?? []);
                break;
            default:
                break;
        }

        return $query->get();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isExpired(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    public function isScheduled(): bool
    {
        return !is_null($this->scheduled_at) && now()->lt($this->scheduled_at);
    }
}