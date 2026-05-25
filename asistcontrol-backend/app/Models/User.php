<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // Para manejo de roles y permisos
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable{
    // Traits agregan funcionalidades al modelo
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'company_id',       // ID de la empresa a la que pertenece
        'office_id',        // ID de la oficina
        'area_id',          // ID del área
        'first_name',       // Nombre
        'last_name',        // Apellido
        'email',            // Correo electrónico
        'password',         // Contraseña (será hasheada automáticamente)
        'pin',              // PIN (también se hashea)
        'employee_code',    // Código de empleado
        'device_token',     // Token del dispositivo (para notificaciones push)
        'is_active',        // Usuario activo o inactivo
    ];
    /*
    |--------------------------------------------------------------------------
    | Atributos ocultos
    |--------------------------------------------------------------------------
    | Estos campos no se mostrarán al convertir el modelo a JSON
    */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    | Convierte automáticamente los atributos a tipos de datos específicos
    */
    protected $casts = [
        'is_active' => 'boolean',        // true/false
        'email_verified_at' => 'datetime', // fecha de verificación de email
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    | Define cómo se conecta este modelo con otros modelos
    */

    // Usuario pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Usuario pertenece a una oficina
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    // Usuario pertenece a un área
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // Usuario tiene muchas asistencias
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Usuario puede recibir muchas notificaciones (relación muchos a muchos)
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_reads')
                    ->withPivot('read_at') // campo extra en la tabla pivote
                    ->withTimestamps();    // actualiza automáticamente created_at y updated_at
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    | Consultas reutilizables para filtrar usuarios
    */

    // Filtra solo usuarios activos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Filtra usuarios por empresa
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors (Getters)
    |--------------------------------------------------------------------------
    | Permiten obtener atributos calculados o combinados
    */

    // Devuelve el nombre completo del usuario
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /*
    |--------------------------------------------------------------------------
    | Mutators (Setters)
    |--------------------------------------------------------------------------
    | Permiten modificar un atributo antes de guardarlo en la base de datos
    */

    // Hash automático de la contraseña
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Métodos útiles
    |--------------------------------------------------------------------------
    | Funciones que facilitan comprobaciones frecuentes
    */

    // Verifica si el usuario está activo
    public function isActive(): bool
    {
        return $this->is_active;
    }

    // Verifica si el usuario tiene rol de administrador
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    // Verifica si el usuario tiene rol de empleado
    public function isEmployee(): bool
    {
        return $this->hasRole('employee');
    }
}
