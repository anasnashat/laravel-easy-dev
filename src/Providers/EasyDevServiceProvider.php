<?php

namespace AnasNashat\EasyDev\Providers;

use Illuminate\Support\ServiceProvider;
use AnasNashat\EasyDev\Commands\MakeCrudCommand;
use AnasNashat\EasyDev\Commands\MakeModelRelationCommand;
use AnasNashat\EasyDev\Commands\ModelSyncRelationsCommand;
use AnasNashat\EasyDev\Commands\MakeRepositoryCommand;
use AnasNashat\EasyDev\Commands\MakeApiResourceCommand;
use AnasNashat\EasyDev\Commands\MakePolicyCommand;
use AnasNashat\EasyDev\Commands\MakeDtoCommand;
use AnasNashat\EasyDev\Commands\MakeObserverCommand;
use AnasNashat\EasyDev\Commands\MakeFilterCommand;
use AnasNashat\EasyDev\Commands\MakeEnumCommand;
use AnasNashat\EasyDev\Commands\EasyDevHelpCommand;
use AnasNashat\EasyDev\Commands\EnhancedCrudCommand;
use AnasNashat\EasyDev\Commands\BeautifulHelpCommand;
use AnasNashat\EasyDev\Commands\DemoUICommand;
use AnasNashat\EasyDev\Services\GenerationContext;
use AnasNashat\EasyDev\Services\MigrationParser;
use AnasNashat\EasyDev\Services\ModelEnhancer;
use AnasNashat\EasyDev\Services\ServiceProviderManager;
use AnasNashat\EasyDev\Contracts\SchemaParser;
use AnasNashat\EasyDev\Parsers\MySqlSchemaParser;
use AnasNashat\EasyDev\Parsers\PostgresSchemaParser;
use AnasNashat\EasyDev\Parsers\SqliteSchemaParser;
use AnasNashat\EasyDev\Commands\EasyDevSnapshotCommand;
use AnasNashat\EasyDev\Commands\EasyDevInfoCommand;
use AnasNashat\EasyDev\Commands\EasyDevDreamCommand;
use AnasNashat\EasyDev\Commands\PublishStubsCommand;
use AnasNashat\EasyDev\Commands\EasyDevAiContextCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\ConnectionResolverInterface as DB;


class EasyDevServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/easy-dev.php', 'easy-dev');

        // Register schema parser based on database driver
        $this->app->bind(SchemaParser::class, function ($app) {
            $connection = $app->make(DB::class)->connection()->getDriverName();
            
            return match ($connection) {
                'mysql' => new MySqlSchemaParser($app->make(DB::class)),
                'pgsql' => new PostgresSchemaParser($app->make(DB::class)),
                'sqlite' => new SqliteSchemaParser($app->make(DB::class)),
                default => new MySqlSchemaParser($app->make(DB::class)),
            };
        });

        // Register core services
        $this->app->singleton(GenerationContext::class);
        $this->app->singleton(MigrationParser::class);
        $this->app->singleton(ModelEnhancer::class);
        $this->app->singleton(ServiceProviderManager::class);
    }

    public function boot(): void
    {
        $this->commands([
            // Core CRUD
            MakeCrudCommand::class,
            EnhancedCrudCommand::class,

            // Individual generators
            MakeRepositoryCommand::class,
            MakeApiResourceCommand::class,
            MakeModelRelationCommand::class,
            ModelSyncRelationsCommand::class,

            // New generators (v2.0)
            MakePolicyCommand::class,
            MakeDtoCommand::class,
            MakeObserverCommand::class,
            MakeFilterCommand::class,
            MakeEnumCommand::class,

            // Help & UI
            EasyDevHelpCommand::class,
            BeautifulHelpCommand::class,
            DemoUICommand::class,

            // AI-Native commands (v3.0)
            EasyDevSnapshotCommand::class,
            EasyDevInfoCommand::class,
            EasyDevDreamCommand::class,
            PublishStubsCommand::class,
            EasyDevAiContextCommand::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/easy-dev.php' => config_path('easy-dev.php'),
            ], 'easy-dev-config');

            $this->publishes([
                __DIR__.'/../../resources/stubs' => resource_path('stubs/vendor/easy-dev'),
            ], 'easy-dev-stubs');
        }

        // Register local web dashboard visual hub routes and views
        if ($this->app->environment('local')) {
            $this->loadViewsFrom(__DIR__.'/../../resources/views', 'easy-dev');

            Route::middleware(['web'])
                ->namespace('AnasNashat\EasyDev\Http\Controllers')
                ->group(function () {
                    Route::get('/easy-dev', 'DashboardController@index')->name('easy-dev.dashboard');
                    Route::get('/easy-dev/api/models', 'DashboardController@getModels')->name('easy-dev.api.models');
                    Route::post('/easy-dev/api/scaffold', 'DashboardController@scaffold')->name('easy-dev.api.scaffold');
                });
        }
    }
}
