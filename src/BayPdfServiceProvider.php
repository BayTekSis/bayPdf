<?php

namespace BayPdf;

use BayPdf\Contracts\ScopeResolver;
use BayPdf\Support\SharedScopeResolver;
use Illuminate\Support\ServiceProvider;

final class BayPdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/baypdf.php', 'baypdf');
        $this->app->singleton(DocumentTypes::class);
        $this->app->singleton(ScopeResolver::class, SharedScopeResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'baypdf');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'baypdf');
        if (config('baypdf.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/baypdf.php' => config_path('baypdf.php')], 'baypdf-config');
            $this->publishes([__DIR__.'/../public' => public_path('vendor/baypdf')], 'baypdf-assets');
        }
    }
}
