<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Notifications\TrialEnding;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('billing:trial-reminders')]
#[Description('Avisa al dueño 3 días antes del primer cobro y el día del cobro')]
class SendTrialReminders extends Command
{
    /** Días antes del cobro para el primer aviso. */
    private const DAYS_BEFORE = 3;

    public function handle(): int
    {
        $sent = 0;

        $companies = Company::query()
            ->where('status', CompanyStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->with('plan', 'owner')
            ->get();

        foreach ($companies as $company) {
            if (! $company->owner || $company->plan->isFree()) {
                continue;
            }

            // El cobro es un día antes del fin de la prueba; se compara en la zona de la empresa
            $chargeOn = $company->trial_ends_at->copy()->subDay()->timezone($company->timezone)->startOfDay();
            $today = now($company->timezone)->startOfDay();
            $daysLeft = (int) $today->diffInDays($chargeOn, false);

            if ($daysLeft <= 0 && ! $company->trial_charge_notice_sent_at) {
                $company->owner->notify(new TrialEnding($company, $chargeOn, 0));
                $company->forceFill(['trial_charge_notice_sent_at' => now()])->save();
                $sent++;
            } elseif ($daysLeft > 0 && $daysLeft <= self::DAYS_BEFORE && ! $company->trial_reminder_sent_at) {
                $company->owner->notify(new TrialEnding($company, $chargeOn, $daysLeft));
                $company->forceFill(['trial_reminder_sent_at' => now()])->save();
                $sent++;
            }
        }

        $this->info("Avisos enviados: {$sent}");

        return self::SUCCESS;
    }
}
