<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\Company;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Company::class);
        Cashier::calculateTaxes(false);
        Cashier::$registersRoutes = false;

        RateLimiter::for('login-web', function (Request $request) {
            $key = 'login-web:' . $request->ip() . '|' . ($request->input('email') ?? '');
            return Limit::perMinutes(30, 5)->by($key);
        });

        RateLimiter::for('login-mobile', function (Request $request) {
            $key = 'login-mobile:' . $request->ip() . '|' . ($request->input('correo') ?? '');
            return Limit::perMinutes(30, 5)->by($key);
        });

        RateLimiter::for('registro', function (Request $request) {
            return Limit::perMinutes(30, 5)->by($request->ip());
        });

        RateLimiter::for('contacto', function (Request $request) {
            return Limit::perMinutes(30, 5)->by($request->ip());
        });
    }
}
