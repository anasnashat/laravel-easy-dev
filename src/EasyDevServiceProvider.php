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
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/packge-test.php', 'easy-dev'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/packge-test.php' => config_path('easy-dev.php'),
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