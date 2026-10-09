<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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
        Prometheus::addGauge('app_orders_total')
            ->helpText('Total number of orders')
            ->value(fn () => Order::count());

        Prometheus::addGauge('app_orders_paid_total')
            ->helpText('Total number of paid orders')
            ->value(fn () => Order::where('status', 'paid')->count());

        Prometheus::addGauge('app_products_total')
            ->helpText('Total number of products')
            ->value(fn () => Product::count());

        Prometheus::addGauge('app_users_total')
            ->helpText('Total number of users')
            ->value(fn () => User::count());
    }
}
