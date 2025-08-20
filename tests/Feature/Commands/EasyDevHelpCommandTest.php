<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class EasyDevHelpCommandTest extends TestCase
{
    public function test_help_command_displays_banner(): void
    {
        $this->artisan('easy-dev:help')
            ->expectsOutput('╔══════════════════════════════════════════════════════════════╗')
            ->expectsOutput('║                    Laravel Easy Dev Package                    ║')
            ->expectsOutput('║              Speed up your Laravel development!              ║')
            ->expectsOutput('╚══════════════════════════════════════════════════════════════╝')
            ->assertExitCode(0);
    }

    public function test_help_command_lists_all_commands(): void
    {
        $this->artisan('easy-dev:help')
            ->expectsOutputToContain('Available Commands:')
            ->expectsOutputToContain('easy-dev:crud')
            ->expectsOutputToContain('easy-dev:repository')
            ->expectsOutputToContain('easy-dev:api-resource')
            ->expectsOutputToContain('easy-dev:add-relation')
            ->expectsOutputToContain('easy-dev:sync-relations')
            ->assertExitCode(0);
    }

    public function test_help_command_shows_usage_examples(): void
    {
        $this->artisan('easy-dev:help')
            ->expectsOutputToContain('Usage Examples:')
            ->expectsOutputToContain('php artisan easy-dev:crud Post')
            ->expectsOutputToContain('php artisan easy-dev:repository Post')
            ->expectsOutputToContain('php artisan easy-dev:sync-relations --all')
            ->assertExitCode(0);
    }

    public function test_help_command_shows_tips_section(): void
    {
        $this->artisan('easy-dev:help')
            ->expectsOutputToContain('💡 Tips:')
            ->expectsOutputToContain('Use easy-dev:sync-relations --all to automatically detect relationships')
            ->expectsOutputToContain('Generate API resources after creating your models')
            ->expectsOutputToContain('Use repository pattern for better testability')
            ->expectsOutputToContain('Publish stubs to customize code generation')
            ->expectsOutputToContain('php artisan vendor:publish')
            ->assertExitCode(0);
    }

    public function test_help_command_shows_github_link(): void
    {
        $this->artisan('easy-dev:help')
            ->expectsOutputToContain('https://github.com/anasnashat/laravel-easy-dev')
            ->assertExitCode(0);
    }

    public function test_help_command_shows_command_descriptions(): void
    {
        $this->artisan('easy-dev:help')
            ->expectsOutputToContain('Generate complete CRUD (Controller, Requests, Routes)')
            ->expectsOutputToContain('Generate repository pattern (Interface + Implementation)')
            ->expectsOutputToContain('Generate API resource and collection classes')
            ->expectsOutputToContain('Add a relationship method to existing model')
            ->expectsOutputToContain('Auto-detect and add relationships from database schema')
            ->assertExitCode(0);
    }

    public function test_help_command_shows_command_options(): void
    {
        $this->artisan('easy-dev:help')
            ->expectsOutputToContain('--api, --without-routes')
            ->expectsOutputToContain('--without-interface')
            ->expectsOutputToContain('--without-collection')
            ->expectsOutputToContain('--method')
            ->expectsOutputToContain('--all')
            ->assertExitCode(0);
    }
}
