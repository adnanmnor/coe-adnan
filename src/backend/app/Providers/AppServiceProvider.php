<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Prometheus\Facades\Prometheus;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Prometheus metrics
        Prometheus::addGauge('app_orders_total')
            ->helpText('Total number of orders')
            ->value(fn () => (int) Order::count());

        Prometheus::addGauge('app_orders_paid_total')
            ->helpText('Total number of paid orders')
            ->value(fn () => (int) Order::where('status', 'paid')->count());

        Prometheus::addGauge('app_products_total')
            ->helpText('Total number of products')
            ->value(fn () => (int) Product::count());

        Prometheus::addGauge('app_users_total')
            ->helpText('Total number of users')
            ->value(fn () => (int) User::count());

        // Rate limiters
        // Skip actual limiting in test environment (automated test suite runs too fast)
        $isTesting = app()->environment('testing');
        $authLimit = $isTesting ? 10000 : 5;
        $apiLimit  = $isTesting ? 10000 : 60;

        RateLimiter::for('auth', function (Request $request) use ($authLimit) {
            return Limit::perMinute($authLimit)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) use ($apiLimit) {
            return Limit::perMinute($apiLimit)->by($request->user()?->id ?: $request->ip());
        });
    }
}
