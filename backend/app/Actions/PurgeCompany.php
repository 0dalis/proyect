<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\CompanyDeletion;
use App\Models\User;
use App\Notifications\CompanyDataDeleted;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Borrado definitivo, 30 días después de la solicitud. No es un borrado
 * lógico: las filas y archivos dejan de existir.
 *
 * Se conserva solo el registro de la baja (company_deletions), sin datos
 * personales, y la referencia al cliente de Stripe por las facturas.
 */
class PurgeCompany
{
    /**
     * Tablas intermedias sin company_id; se limpian por sus llaves antes de
     * borrar las tablas principales.
     */
    private const PIVOTS = [
        'area_manager' => ['employee_id', 'employees'],
        'announcement_employee' => ['announcement_id', 'announcements'],
    ];

    public function __construct(private TenantManager $tenants) {}

    public function handle(CompanyDeletion $deletion): void
    {
        $company = Company::query()->find($deletion->company_id);

        if ($company) {
            if ($company->database) {
                $this->purgeTenantData($company);
            }
            // Archivos de la empresa: checadas, avatares, logotipo y fotos
            foreach (['attendance', 'avatars', 'logos', 'photos'] as $folder) {
                Storage::disk('local')->deleteDirectory("{$folder}/{$company->id}");
            }
            $this->purgeCentralData($company);
        }

        if ($deletion->export_path) {
            Storage::disk('local')->delete($deletion->export_path);
        }

        $email = $deletion->owner_email;
        $name = $deletion->owner_name;

        $deletion->forceFill([
            'purged_at' => now(),
            'feedback_token' => Str::random(48),
            // Sin datos personales a partir de aquí
            'owner_name' => null,
            'owner_email' => null,
            'export_path' => null,
            'export_token_hash' => null,
            'export_expires_at' => null,
        ])->save();

        if ($email) {
            Notification::route('mail', [$email => $name])->notify(new CompanyDataDeleted($deletion));
        }
    }

    private function purgeTenantData(Company $company): void
    {
        $dedicated = $company->database === config('tenancy.database_prefix').'tenant_'.$company->id;

        if ($dedicated) {
            $this->tenants->forget();
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$company->database}`");

            return;
        }

        // Base compartida: solo las filas de esta empresa
        $this->tenants->run($company, function () use ($company) {
            $db = DB::connection('tenant');
            $schema = Schema::connection('tenant');
            $db->statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                foreach (self::PIVOTS as $pivot => [$column, $parent]) {
                    if ($schema->hasTable($pivot)) {
                        $db->table($pivot)
                            ->whereIn($column, $db->table($parent)->where('company_id', $company->id)->select('id'))
                            ->delete();
                    }
                }

                foreach ($schema->getTableListing(schemaQualified: false) as $table) {
                    if ($table !== 'migrations' && $schema->hasColumn($table, 'company_id')) {
                        $db->table($table)->where('company_id', $company->id)->delete();
                    }
                }
            } finally {
                $db->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });
    }

    private function purgeCentralData(Company $company): void
    {
        $central = DB::connection('central');
        $userIds = User::query()->where('company_id', $company->id)->pluck('id');

        $central->transaction(function () use ($central, $company, $userIds) {
            $central->table('personal_access_tokens')
                ->where('tokenable_type', (new User)->getMorphClass())
                ->whereIn('tokenable_id', $userIds)
                ->delete();
            $central->table('sessions')->whereIn('user_id', $userIds)->delete();
            $central->table('password_reset_tokens')
                ->whereIn('email', User::query()->whereIn('id', $userIds)->pluck('email'))
                ->delete();

            $central->table('model_has_permissions')->where('company_id', $company->id)->delete();
            $central->table('model_has_roles')->where('company_id', $company->id)->delete();
            $roleIds = $central->table('roles')->where('company_id', $company->id)->pluck('id');
            $central->table('role_has_permissions')->whereIn('role_id', $roleIds)->delete();
            $central->table('roles')->whereIn('id', $roleIds)->delete();

            $subscriptionIds = $central->table('subscriptions')->where('company_id', $company->id)->pluck('id');
            $central->table('subscription_items')->whereIn('subscription_id', $subscriptionIds)->delete();
            $central->table('subscriptions')->whereIn('id', $subscriptionIds)->delete();

            $central->table('users')->whereIn('id', $userIds)->delete();
            $central->table('companies')->where('id', $company->id)->delete();
        });
    }
}
