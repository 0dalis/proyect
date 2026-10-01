<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;

#[Signature('plans:sync-stripe {--force : Crea precios nuevos aunque el plan ya tenga}')]
#[Description('Crea en Stripe el producto y los precios mensual y anual (11 meses) de cada plan de pago')]
class SyncPlansWithStripe extends Command
{
    public function handle(): int
    {
        if (blank(config('cashier.secret'))) {
            $this->error('Configura STRIPE_KEY y STRIPE_SECRET en .env primero.');

            return self::FAILURE;
        }

        $stripe = Cashier::stripe();
        $currency = config('cashier.currency', 'mxn');

        foreach (Plan::query()->where('is_active', true)->get() as $plan) {
            if ($plan->isFree()) {
                continue;
            }

            if (! $plan->stripe_product_id) {
                $plan->stripe_product_id = $stripe->products->create([
                    'name' => "AsistControl {$plan->name}",
                    'metadata' => ['plan' => $plan->slug],
                ])->id;
            }

            $force = (bool) $this->option('force');

            if (! $plan->stripe_price_id || $force) {
                $plan->stripe_price_id = $stripe->prices->create([
                    'product' => $plan->stripe_product_id,
                    'currency' => $currency,
                    'unit_amount' => (int) round((float) $plan->monthly_price * 100),
                    'recurring' => ['interval' => 'month'],
                    'nickname' => "{$plan->name} mensual",
                ])->id;
            }

            if (! $plan->stripe_yearly_price_id || $force) {
                $plan->stripe_yearly_price_id = $stripe->prices->create([
                    'product' => $plan->stripe_product_id,
                    'currency' => $currency,
                    'unit_amount' => (int) round($plan->yearlyPrice() * 100),
                    'recurring' => ['interval' => 'year'],
                    'nickname' => "{$plan->name} anual (11 meses)",
                ])->id;
            }

            $plan->save();
            $this->line("{$plan->name}: {$plan->stripe_price_id} / {$plan->stripe_yearly_price_id}");
        }

        $this->info('Planes sincronizados con Stripe.');

        return self::SUCCESS;
    }
}
