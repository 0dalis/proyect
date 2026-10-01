<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Employee extends Model{
    protected $fillable = [
        'company_id',
        'office_id',
        'area_id',
        'shift_id',
        'is_area_manager', // TRUE: gerente del área a la que pertenece (area_id)
        'user_id',       // NULLABLE: Solo si tiene cuenta en el panel web/app
        'employee_code', // Código para el kiosco o QR
        'first_name',
        'last_name',
        'pin',           // Hasheado o cifrado para marcaje en kiosco
        'is_active',
        'bank_name',
        'bank_account',
        'position',
        'hired_at',
    ];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_area_manager' => 'boolean',
        'hired_at' => 'date',
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

    public function compensation()
    {
        return $this->hasOne(EmployeeCompensation::class);
    }

    public function concepts()
    {
        return $this->hasMany(EmployeeConcept::class);
    }

    public function vacationBalances()
    {
        return $this->hasMany(VacationBalance::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function credential()
    {
        return $this->hasOne(EmployeeCredential::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class, 'user_id', 'user_id');
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

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /* --------------------------------------------------------------------------
    | Mutators
    | -------------------------------------------------------------------------- */
    public function setPinAttribute($value)
    {
        $this->attributes['pin'] = empty($value) ? '' : Hash::make($value);
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
