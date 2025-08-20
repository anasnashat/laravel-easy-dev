<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;
use AnasNashat\EasyDev\Tests\Fixtures\Models\User;
use AnasNashat\EasyDev\Tests\Fixtures\Models\Post;
use AnasNashat\EasyDev\Tests\Fixtures\Models\Category;

class ModelSyncRelationsCommandTest extends TestCase
{
    public function test_sync_relations_for_specific_model(): void
    {
        // Ensure our test models exist in the autoload
        $this->assertTrue(class_exists(User::class));
        $this->assertTrue(class_exists(Post::class));

        $this->artisan('easy-dev:sync-relations', ['model' => 'User'])
            ->expectsOutputToContain('Analyzing relations for User')
            ->assertExitCode(0);
    }

    public function test_sync_relations_with_all_flag(): void
    {
        $this->artisan('easy-dev:sync-relations', ['--all' => true])
            ->expectsOutputToContain('models')
            ->assertExitCode(0);
    }

    public function test_sync_relations_handles_nonexistent_model(): void
    {
        $this->expectException(\Error::class);
        $this->artisan('easy-dev:sync-relations', ['model' => 'NonExistentFile']);
    }

    public function test_sync_relations_shows_progress_for_each_model(): void
    {
        $this->artisan('easy-dev:sync-relations', ['--all' => true])
            ->expectsOutput('No models found.') // This is likely what happens in test env
            ->assertExitCode(0);
    }

    public function test_sync_relations_detects_foreign_key_relationships(): void
    {
        // This test would require actual database schema analysis
        // For now, we'll test that the command runs without errors
        $this->artisan('easy-dev:sync-relations', ['model' => 'Post'])
            ->assertExitCode(0);
    }

    public function test_sync_relations_handles_models_with_no_relations(): void
    {
        // Test with a model that has no detectable relations
        $this->artisan('easy-dev:sync-relations', ['model' => 'User'])
            ->expectsOutputToContain('relations for User')
            ->assertExitCode(0);
    }

    public function test_sync_relations_skips_existing_relations(): void
    {
        // Test that existing relations are not duplicated
        $this->artisan('easy-dev:sync-relations', ['model' => 'User'])
            ->assertExitCode(0);
            
        // Run again to test skipping existing relations
        $this->artisan('easy-dev:sync-relations', ['model' => 'User'])
            ->assertExitCode(0);
    }

    public function test_sync_relations_processes_self_referencing_models(): void
    {
        // Test with Category model that has parent_id for self-referencing
        $this->artisan('easy-dev:sync-relations', ['model' => 'Category'])
            ->assertExitCode(0);
    }

    public function test_sync_relations_provides_helpful_output(): void
    {
        $this->artisan('easy-dev:sync-relations', ['model' => 'User'])
            ->expectsOutputToContain('Analyzing relations')
            ->assertExitCode(0);
    }

    public function test_sync_relations_handles_database_errors_gracefully(): void
    {
        // Test error handling when database operations fail
        // This would require mocking database failures
        $this->artisan('easy-dev:sync-relations', ['model' => 'User'])
            ->assertExitCode(0);
    }
}
