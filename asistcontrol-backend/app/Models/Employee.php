<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Employee extends Model{
    protected $fillable = [
        'company_id',
        'office_id',
        'area_id',
        'user_id',       // NULLABLE: Solo si tiene cuenta en el panel web/app
        'employee_code', // Código para el kiosco o QR
        'first_name',
        'last_name',
        'pin',           // Hasheado o cifrado para marcaje en kiosco
        'is_active',
    ];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* --------------------------------------------------------------------------
    | Relaciones
    | -------------------------------------------------------------------------- */

    // Si el empleado tiene acceso a la web, esta relación devuelve su User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ¡Las asistencias pertenecen al EMPLEADO, no al usuario!
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /* --------------------------------------------------------------------------
    | Mutators
    | -------------------------------------------------------------------------- */
    public function setPinAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['pin'] = Hash::make($value);
        }
    }

    /* --------------------------------------------------------------------------
    | Helpers
    | -------------------------------------------------------------------------- */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Saber rápidamente si puede entrar al panel web
    public function hasSystemAccess(): bool
    {
        return !is_null($this->user_id);
    }
}
