<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class PublishReadinessCommandTest extends TestCase
{
    public function test_new_publish_ready_commands_are_registered(): void
    {
        [, $output] = $this->callArtisanWithOutput('list', ['namespace' => 'easy-dev']);

        $this->assertStringContainsString('easy-dev:test', $output);
        $this->assertStringContainsString('easy-dev:swagger', $output);
        $this->assertStringContainsString('easy-dev:analyze', $output);
    }

    public function test_test_command_generates_feature_service_and_repository_tests(): void
    {
        config()->set('easy-dev.paths.feature_tests', $this->getTestPath('GeneratedTests/Feature'));
        config()->set('easy-dev.paths.unit_tests', $this->getTestPath('GeneratedTests/Unit'));

        [, $output] = $this->callArtisanWithOutput('easy-dev:test', [
            'model' => 'Product',
            '--api' => true,
            '--feature' => true,
            '--unit' => true,
            '--service' => true,
            '--repository' => true,
            '--ai' => true,
        ]);

        $this->assertStringContainsString('"status": "success"', $output);
        $this->assertFileExists($this->getTestPath('GeneratedTests/Feature/ProductControllerTest.php'));
        $this->assertFileExists($this->getTestPath('GeneratedTests/Unit/ProductServiceTest.php'));
        $this->assertFileExists($this->getTestPath('GeneratedTests/Unit/ProductRepositoryTest.php'));
    }

    public function test_module_test_generation_uses_psr4_test_roots(): void
    {
        config()->set('easy-dev.paths.feature_tests', $this->getTestPath('GeneratedTests/Feature'));
        config()->set('easy-dev.paths.unit_tests', $this->getTestPath('GeneratedTests/Unit'));

        $this->artisan('easy-dev:test', [
            'model' => 'ModuleProduct',
            '--module' => 'Catalog',
            '--architecture' => 'ddd',
            '--feature' => true,
            '--unit' => true,
            '--service' => true,
            '--repository' => true,
        ])->assertExitCode(0);

        $this->assertFileExists($this->getTestPath('GeneratedTests/Feature/ModuleProductControllerTest.php'));
        $this->assertFileExists($this->getTestPath('GeneratedTests/Unit/ModuleProductServiceTest.php'));
        $this->assertFileExists($this->getTestPath('GeneratedTests/Unit/ModuleProductRepositoryTest.php'));
        $this->assertFileDoesNotExist(base_path('app/Modules/Catalog/Tests/Feature/ModuleProductControllerTest.php'));
    }

    public function test_swagger_command_generates_openapi_json_for_model(): void
    {
        $outputPath = $this->getTestPath('openapi.json');

        [, $output] = $this->callArtisanWithOutput('easy-dev:swagger', [
            'model' => 'Product',
            '--output' => $outputPath,
            '--ai' => true,
        ]);

        $this->assertStringContainsString('"status": "success"', $output);
        $this->assertFileExists($outputPath);

        $content = file_get_contents($outputPath);
        $this->assertStringContainsString('"openapi": "3.0.3"', $content);
        $this->assertStringContainsString('/api/products', $content);
    }

    public function test_publish_stubs_lists_nested_frontend_and_test_stubs(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:publish-stubs', [
            '--list' => true,
            '--ai' => true,
        ]);

        $this->assertStringContainsString('"status": "success"', $output);
        $this->assertStringContainsString('relations\/hasMany', $output);
        $this->assertStringContainsString('frontend.vue', $output);
        $this->assertStringContainsString('test.feature.controller', $output);
    }

    public function test_analyze_command_returns_machine_readable_json(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:analyze', [
            '--model' => 'Product',
            '--json' => true,
        ]);

        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('easy-dev:analyze', $decoded['command']);
        $this->assertArrayHasKey('findings', $decoded);
    }

    public function test_crud_dry_run_displays_new_publish_ready_flags(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'SampleThing',
            '--api' => true,
            '--tests' => true,
            '--swagger' => true,
            '--vue' => true,
            '--force' => true,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('ApiController')
            ->expectsOutputToContain('tests/Feature/SampleThingControllerTest.php')
            ->expectsOutputToContain('storage/app/easy-dev/openapi.json')
            ->expectsOutputToContain('frontend scaffold for vue')
            ->assertExitCode(0);
    }
}
