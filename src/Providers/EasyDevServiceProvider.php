<?php

namespace AnasNashat\EasyDev\Providers;

use Illuminate\Support\ServiceProvider;
use AnasNashat\EasyDev\Commands\MakeCrudCommand;
use AnasNashat\EasyDev\Commands\MakeModelRelationCommand;
use AnasNashat\EasyDev\Commands\ModelSyncRelationsCommand;
use AnasNashat\EasyDev\Commands\MakeRepositoryCommand;
use AnasNashat\EasyDev\Commands\MakeApiResourceCommand;
use AnasNashat\EasyDev\Commands\EasyDevHelpCommand;
use AnasNashat\EasyDev\Commands\EnhancedCrudCommand;
use AnasNashat\EasyDev\Commands\BeautifulHelpCommand;
use AnasNashat\EasyDev\Commands\DemoUICommand;
use AnasNashat\EasyDev\Services\MigrationParser;
use AnasNashat\EasyDev\Services\ModelEnhancer;
use AnasNashat\EasyDev\Services\ServiceProviderManager;
use AnasNashat\EasyDev\Contracts\SchemaParser;
use AnasNashat\EasyDev\Parsers\MySqlSchemaParser;
use AnasNashat\EasyDev\Parsers\PostgresSchemaParser;
use AnasNashat\EasyDev\Parsers\SqliteSchemaParser;
use Illuminate\Database\ConnectionResolverInterface as DB;

class EasyDevServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/easy-dev.php', 'easy-dev');

        // Register schema parser
        $this->app->bind(SchemaParser::class, function ($app) {
            $connection = $app->make(DB::class)->connection()->getDriverName();
            
            return match ($connection) {
                'mysql' => new MySqlSchemaParser($app->make(DB::class)),
                'pgsql' => new PostgresSchemaParser($app->make(DB::class)),
                'sqlite' => new SqliteSchemaParser($app->make(DB::class)),
                default => new MySqlSchemaParser($app->make(DB::class)), // Default fallback
            };
        });

        // Register new services
        $this->app->singleton(MigrationParser::class);
        $this->app->singleton(ModelEnhancer::class);
        $this->app->singleton(ServiceProviderManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrudCommand::class,
                MakeModelRelationCommand::class,
                ModelSyncRelationsCommand::class,
                MakeRepositoryCommand::class,
                MakeApiResourceCommand::class,
                EasyDevHelpCommand::class,
                EnhancedCrudCommand::class,
                BeautifulHelpCommand::class,
                DemoUICommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../../config/easy-dev.php' => config_path('easy-dev.php'),
            ], 'easy-dev-config');

            $this->publishes([
                __DIR__.'/../../resources/stubs' => resource_path('stubs/vendor/easy-dev'),
            ], 'easy-dev-stubs');
        }
    }
}
