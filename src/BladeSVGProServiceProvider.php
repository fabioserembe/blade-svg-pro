<?php

namespace FabioSerembe\BladeSVGPro;

use Illuminate\Support\ServiceProvider;

class BladeSVGProServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([BladeSVGPro::class]);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('blade-svg-pro.php'),
            ], 'blade-svg-pro-config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'blade-svg-pro');

        $this->app->singleton('blade-svg-pro', function ($app) {
            return new BladeSVGPro;
        });
    }
}
