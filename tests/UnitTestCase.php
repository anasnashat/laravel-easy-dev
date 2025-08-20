<?php

namespace AnasNashat\EasyDev\Tests;

use AnasNashat\EasyDev\Providers\EasyDevServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Filesystem\Filesystem;

abstract class UnitTestCase extends OrchestraTestCase
{
    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = new Filesystem();
    }

    protected function getPackageProviders($app)
    {
        return [
            EasyDevServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
