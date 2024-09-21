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
        // Pubblicazione del file JS
        $this->publishes([
            __DIR__.'/../resources/js' => public_path('vendor/fabioserembe/js'),
        ], 'public');

        // Definisci la direttiva Blade per includere lo script
        Blade::directive('svgBboxScripts', function () {
            return '<script src="' . asset('vendor/fabioserembe/js/blade-svg-pro.js') . '"></script>';
        });
        // Registering package commands.
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
