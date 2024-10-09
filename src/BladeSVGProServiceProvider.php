<?php

namespace FabioSerembe\BladeSVGPro;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeSVGProServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->commands([
            BladeSVGPro::class
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'blade-svg-pro');

        // Register the main class to use with the facade
        $this->app->singleton('blade-svg-pro', function () {
            return new BladeSVGPro;
        });
    }
}
