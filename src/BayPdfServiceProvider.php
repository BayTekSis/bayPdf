<?php

namespace BayPdf;

use Illuminate\Support\ServiceProvider;

final class BayPdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/baypdf.php', 'baypdf');
        $this->app->singleton(DocumentTypes::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/baypdf.php' => config_path('baypdf.php')], 'baypdf-config');
        }
    }
}
