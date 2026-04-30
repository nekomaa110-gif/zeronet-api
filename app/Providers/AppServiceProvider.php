<?php

namespace App\Providers;

use App\Services\MikrotikService;
use App\Services\RadiusService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services ke container
     */
    public function register(): void
    {
        // Bind MikrotikService sebagai singleton
        // (satu instance per request, hemat koneksi)
        $this->app->singleton(MikrotikService::class, function ($app) {
            return new MikrotikService;
        });

        // Bind RadiusService
        $this->app->singleton(RadiusService::class, function ($app) {
            return new RadiusService;
        });
    }

    public function boot(): void
    {
        //
    }
}
