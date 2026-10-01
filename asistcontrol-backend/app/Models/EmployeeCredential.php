<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EmployeeCredential extends Model
{
    protected $table = 'employee_credentials';

    protected $fillable = [
        'company_id',
        'employee_id',
        'user_id',
        'orientation',
        'design',
        'qr_token',
        'qr_payload',
        'photo_path',
        'pdf_path',
        'download_enabled',
        'issued_at',
        'last_printed_at',
        'print_count',
    ];

    protected $casts = [
        'design' => 'array',
        'download_enabled' => 'boolean',
        'issued_at' => 'datetime',
        'last_printed_at' => 'datetime',
        'print_count' => 'integer',
    ];

    protected $appends = ['photo_url', 'pdf_url'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_path ? Storage::disk('public')->url($this->pdf_path) : null;
    }

    /**
     * Construye el payload firmado del QR: AC|company_code|employee_code|user_id|token|sig
     */
    public static function buildPayload(Company $company, Employee $employee, string $token): string
    {
        $base = implode('|', [
            'AC',
            $company->code,
            $employee->employee_code,
            (string) ($employee->user_id ?? 0),
            $token,
        ]);

        return $base . '|' . substr(hash_hmac('sha256', $base, (string) config('app.key')), 0, 12);
    }

    /**
     * Valida el payload del QR y devuelve sus partes o null si es inválido.
     *
     * @return array{company_code:string, employee_code:string, user_id:string, token:string}|null
     */
    public static function verifyPayload(string $payload): ?array
    {
        $parts = explode('|', $payload);

        if (count($parts) !== 6 || $parts[0] !== 'AC') {
            return null;
        }

        [$prefix, $companyCode, $employeeCode, $userId, $token, $sig] = $parts;
        $base = implode('|', [$prefix, $companyCode, $employeeCode, $userId, $token]);
        $expected = substr(hash_hmac('sha256', $base, (string) config('app.key')), 0, 12);

        if (! hash_equals($expected, $sig)) {
            return null;
        }

        return [
            'company_code' => $companyCode,
            'employee_code' => $employeeCode,
            'user_id' => $userId,
            'token' => $token,
        ];
    }
}
