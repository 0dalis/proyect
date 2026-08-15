<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingMail;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessSubscriptions extends Command
{
    protected $signature = 'subscriptions:process';

    protected $description = 'Procesa trial, periodos de gracia y degradaciones de planes automáticamente.';

    public function handle(): int
    {
        $this->sendTrialEndingReminders();

        $this->downgradeExpiredTrials();

        $this->downgradeExpiredSubscriptions();

        return self::SUCCESS;
    }

    /**
     * Avisa por correo 7 días antes de que termine el trial.
     */
    protected function sendTrialEndingReminders(): void
    {
        $warningDate = now()->addDays(7);

        $companies = Company::where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $warningDate->toDateString())
            ->whereNull('subscription_ends_at')
            ->get();

        foreach ($companies as $company) {
            $owner = $this->getOwner($company);

            if (! $owner) {
                continue;
            }

            try {
                Mail::to($owner->email)->send(new TrialEndingMail($company, $owner));
                $this->info("Aviso de trial enviado a {$owner->email} ({$company->name}).");
            } catch (\Throwable $e) {
                $this->error("No se pudo enviar el aviso a {$owner->email}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Degrada a Plan Gratis las empresas cuyo trial ya venció y no tienen suscripción.
     */
    protected function downgradeExpiredTrials(): void
    {
        $freePlan = Plan::where('tipo', 'Free')->first() ?? Plan::orderBy('precio')->first();

        $companies = Company::where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->where(function ($query) {
                $query->whereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '<', now());
            })
            ->get();

        foreach ($companies as $company) {
            $company->update([
                'plan_id' => $freePlan?->id,
                'trial_ends_at' => null,
            ]);

            $this->info("Empresa {$company->name} degradada a Plan Gratis por trial vencido.");
        }
    }

    /**
     * Degrada a Plan Gratis las suscripciones pagadas vencidas tras 3 días de gracia.
     */
    protected function downgradeExpiredSubscriptions(): void
    {
        $freePlan = Plan::where('tipo', 'Free')->first() ?? Plan::orderBy('precio')->first();

        $graceCutoff = now()->subDays(3);

        $companies = Company::where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', $graceCutoff)
            ->get();

        foreach ($companies as $company) {
            $company->update([
                'plan_id' => $freePlan?->id,
                'subscription_ends_at' => null,
            ]);

            $this->info("Empresa {$company->name} degradada a Plan Gratis por suscripción vencida.");
        }
    }

    /**
     * Obtiene el usuario owner (o el primer usuario) de la empresa.
     */
    protected function getOwner(Company $company): ?User
    {
        $owner = $company->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'owner');
            })
            ->first();

        return $owner ?? $company->users()->first();
    }
}
