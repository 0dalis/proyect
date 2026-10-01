<?php

namespace App\Actions;

use App\Billing\BillingGateway;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyDeletion;
use App\Models\Kiosk;
use App\Models\User;
use App\Notifications\CompanyDeletionRequested;
use App\Support\ActivityLogger;
use App\Tenancy\TenantManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * El dueño pide eliminar su empresa de AsistControl:
 * 1. Queda registrada la baja (motivo, plan, tiempo en el sistema).
 * 2. Se cierra el acceso a todos: sesiones, tokens de la app y kioskos.
 * 3. Se cancela la suscripción en Stripe.
 * 4. Se genera la exportación y se envía el enlace (48 horas) al dueño.
 * 5. A los 30 días, companies:purge-deleted borra todo (PurgeCompany).
 */
class RequestCompanyDeletion
{
    public function __construct(
        private BillingGateway $billing,
        private ExportCompanyData $export,
        private TenantManager $tenants,
    ) {}

    public function handle(Company $company, User $owner, string $reason): CompanyDeletion
    {
        $this->tenants->run($company, fn () => ActivityLogger::log(
            'company_deletion_requested',
            description: 'Solicitó eliminar los datos de la empresa',
            actor: $owner,
        ));

        $deletion = DB::connection('central')->transaction(function () use ($company, $owner, $reason) {
            $requestedAt = now();
            $registeredAt = $owner->created_at ?? $company->created_at;

            $deletion = CompanyDeletion::query()->create([
                'company_id' => $company->id,
                'company_name' => $company->name,
                'last_plan' => $company->plan->name,
                'billing_interval' => $company->billing_interval,
                'stripe_customer_id' => $company->stripe_id,
                'registered_at' => $registeredAt,
                'requested_at' => $requestedAt,
                'days_in_system' => (int) $registeredAt->diffInDays($requestedAt),
                'reason' => $reason,
                'purge_after' => $requestedAt->copy()->addDays(CompanyDeletion::RETENTION_DAYS),
                'owner_name' => $owner->name,
                'owner_email' => $owner->email,
            ]);

            $company->forceFill([
                'status' => CompanyStatus::DeletionPending,
                'deletion_requested_at' => $requestedAt,
                'deletion_scheduled_for' => $deletion->purge_after,
            ])->save();

            return $deletion;
        });

        $this->closeAccess($company);
        $this->billing->cancelNow($company);

        $deletion->forceFill(['export_path' => $this->export->handle($company)])->save();
        $token = $deletion->issueExportToken();

        Notification::route('mail', [$owner->email => $owner->name])
            ->notify(new CompanyDeletionRequested($deletion, $token));

        return $deletion;
    }

    private function closeAccess(Company $company): void
    {
        $userIds = User::query()->where('company_id', $company->id)->pluck('id');

        PersonalAccessToken::query()
            ->where('tokenable_type', (new User)->getMorphClass())
            ->whereIn('tokenable_id', $userIds)
            ->delete();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection') ?? 'central')
                ->table(config('session.table', 'sessions'))
                ->whereIn('user_id', $userIds)
                ->delete();
        }

        // Los kioskos también validan el estado de la empresa; además se desactivan
        $this->tenants->run($company, fn () => Kiosk::query()->update(['is_active' => false]));
    }
}
