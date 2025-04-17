<?php

namespace Anas\EasyDev;

use Illuminate\Support\ServiceProvider;
use Anas\EasyDev\Commands\MakeCrud;
use Anas\EasyDev\Commands\MakeModelRelation;
use Anas\EasyDev\Commands\SyncModelRelations;

class EasyDevServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config - UPDATED FILE NAME
        $this->mergeConfigFrom(
            __DIR__.'/../config/easy-dev.php', 'easy-dev'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config - UPDATED FILE NAME
        $this->publishes([
            __DIR__.'/../config/easy-dev.php' => config_path('easy-dev.php'),
        ], 'easy-dev-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrud::class,
                MakeModelRelation::class,
                SyncModelRelations::class,
            ]);
        }
    }
}