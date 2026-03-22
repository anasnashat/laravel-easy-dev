<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class MakeCrudCommandTest extends TestCase
{
    public function test_crud_command_requires_model_argument(): void
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this->artisan('easy-dev:crud');
    }

    public function test_crud_command_rejects_conflicting_options(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'Post',
            '--api-only' => true,
            '--web-only' => true,
        ])->assertExitCode(1);
    }

    public function test_dry_run_does_not_create_files(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('Files that would be created')
            ->expectsOutputToContain('TestProduct')
            ->expectsOutputToContain('No files were created or modified')
            ->assertExitCode(0);

        // Verify no files were actually created
        $this->assertFalse(file_exists(app_path('Models/TestProduct.php')));
        $this->assertFalse(file_exists(app_path('Http/Controllers/TestProductController.php')));
    }

    public function test_dry_run_shows_repository_files_when_flag_set(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
            '--with-repository' => true,
        ])
            ->expectsOutputToContain('Repositories/TestProductRepository')
            ->expectsOutputToContain('Repositories/Contracts/TestProductRepositoryInterface')
            ->assertExitCode(0);
    }

    public function test_dry_run_shows_service_files_when_flag_set(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
            '--with-service' => true,
        ])
            ->expectsOutputToContain('Services/TestProductService')
            ->expectsOutputToContain('Services/Contracts/TestProductServiceInterface')
            ->assertExitCode(0);
    }

    public function test_dry_run_shows_policy_file_when_flag_set(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
            '--with-policy' => true,
        ])
            ->expectsOutputToContain('Policies/TestProductPolicy')
            ->assertExitCode(0);
    }

    public function test_dry_run_shows_dto_file_when_flag_set(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
            '--with-dto' => true,
        ])
            ->expectsOutputToContain('DTOs/TestProductData')
            ->assertExitCode(0);
    }

    public function test_dry_run_shows_observer_file_when_flag_set(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
            '--with-observer' => true,
        ])
            ->expectsOutputToContain('Observers/TestProductObserver')
            ->assertExitCode(0);
    }

    public function test_dry_run_api_only_does_not_show_web_controller(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
            '--api-only' => true,
        ])
            ->expectsOutputToContain('ApiController')
            ->assertExitCode(0);
    }

    public function test_dry_run_web_only_does_not_show_api_controller(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'TestProduct',
            '--dry-run' => true,
            '--web-only' => true,
        ])
            ->expectsOutputToContain('TestProductController')
            ->assertExitCode(0);
    }
}
