<?php

namespace App\Actions;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alta desde la landing. La empresa queda pendiente hasta que el correo
 * maestro se verifique; hasta entonces no se crea su base de datos.
 * El plan se elige después, en el modal de bienvenida (mientras, queda Free).
 */
class RegisterCompany
{
    /**
     * @param  array{company_name: string, name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        $plan = Plan::free();

        $owner = DB::connection('central')->transaction(function () use ($data, $plan) {
            $company = Company::query()->create([
                'name' => $data['company_name'],
                'slug' => $this->uniqueSlug($data['company_name']),
                'plan_id' => $plan->id,
                'status' => CompanyStatus::Pending,
            ]);

            return User::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_owner' => true,
            ]);
        });

        $owner->sendEmailVerificationNotification();

        return $owner;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'empresa';
        $slug = $base;

        for ($i = 2; Company::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
