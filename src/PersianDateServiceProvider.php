<?php

namespace Penobit\PersianDate;

use Illuminate\Support\ServiceProvider;

class PersianDateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // $this->publishes([
        //     __DIR__.'/../config/persiandate.php' => config_path('persiandate.php'),
        // ], 'config');

        // $this->publishes([
        //     __DIR__.'/../resources/lang' => resource_path('lang/vendor/persiandate'),
        // ], 'lang');

        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'persiandate');
    }

    /**
     * Register services.
     */
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__.'/../config/persiandate.php', 'persiandate');
    }
}