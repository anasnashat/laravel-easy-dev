<?php

namespace PackgeTest;

use Illuminate\Support\ServiceProvider;
use PackgeTest\Commands\MakeCrud;
use PackgeTest\Commands\MakeModelRelation;
use PackgeTest\Commands\SyncModelRelations;

class PackgeTestServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/packge-test.php', 'packge-test'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/packge-test.php' => config_path('packge-test.php'),
        ], 'packge-test-config');

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