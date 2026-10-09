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
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
