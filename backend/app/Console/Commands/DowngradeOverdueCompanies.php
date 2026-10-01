<?php

namespace App\Console\Commands;

use App\Billing\BillingGateway;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Notifications\PaymentFailed;
use App\Notifications\PlanDowngraded;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('billing:downgrade-overdue')]
#[Description('Pasa a Free las empresas con el pago vencido desde hace 3 días o más')]
class DowngradeOverdueCompanies extends Command
{
    public function handle(BillingGateway $billing): int
    {
        $free = Plan::free();

        $companies = Company::query()
            ->where('status', CompanyStatus::PastDue)
            ->where('past_due_since', '<=', now()->subDays(PaymentFailed::GRACE_DAYS))
            ->with('plan', 'owner')
            ->get();

        foreach ($companies as $company) {
            $previous = $company->plan->name;

            $billing->cancelNow($company);

            $company->forceFill([
                'plan_id' => $free->id,
                'billing_interval' => null,
                'status' => CompanyStatus::Active,
                'past_due_since' => null,
                'trial_ends_at' => null,
                'payroll_enabled' => false,
                'bonuses_enabled' => false,
            ])->save();
            $company->setRelation('plan', $free);

            $company->owner?->notify(new PlanDowngraded($company, $previous));
            $this->line("{$company->name}: {$previous} → Free");
        }

        $this->info("Empresas degradadas: {$companies->count()}");

        return self::SUCCESS;
    }
}
