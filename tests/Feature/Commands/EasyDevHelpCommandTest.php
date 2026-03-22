<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class EasyDevHelpCommandTest extends TestCase
{
    public function test_help_command_displays_banner(): void
    {
        $exitCode = Artisan::call('easy-dev:help');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Laravel Easy Dev', $output);
    }

    public function test_help_command_lists_all_commands(): void
    {
        Artisan::call('easy-dev:help');
        $output = Artisan::output();

        $this->assertStringContainsString('Available Commands', $output);
        $this->assertStringContainsString('easy-dev:make', $output);
        $this->assertStringContainsString('easy-dev:crud', $output);
        $this->assertStringContainsString('easy-dev:repository', $output);
        $this->assertStringContainsString('easy-dev:sync-relations', $output);
    }

    public function test_help_command_shows_usage_examples(): void
    {
        Artisan::call('easy-dev:help', ['--examples' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('Usage Examples', $output);
        $this->assertStringContainsString('easy-dev:make Product', $output);
        $this->assertStringContainsString('easy-dev:crud Order', $output);
        $this->assertStringContainsString('easy-dev:sync-relations', $output);
    }

    public function test_help_command_shows_tips_section(): void
    {
        Artisan::call('easy-dev:help', ['--examples' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('Pro Tips', $output);
        $this->assertStringContainsString('--interactive', $output);
        $this->assertStringContainsString('--with-repository', $output);
    }

    public function test_help_command_shows_github_link(): void
    {
        Artisan::call('easy-dev:help');
        $output = Artisan::output();

        $this->assertStringContainsString('github.com/anasnashat/laravel-easy-dev', $output);
    }

    public function test_help_command_shows_command_descriptions(): void
    {
        Artisan::call('easy-dev:help');
        $output = Artisan::output();

        $this->assertStringContainsString('CRUD generator', $output);
        $this->assertStringContainsString('repository', $output);
        $this->assertStringContainsString('relationships', $output);
    }

    public function test_help_command_shows_command_options(): void
    {
        Artisan::call('easy-dev:help');
        $output = Artisan::output();

        $this->assertStringContainsString('--with-repository', $output);
        $this->assertStringContainsString('--with-service', $output);
        $this->assertStringContainsString('--without-interface', $output);
        $this->assertStringContainsString('--api-only', $output);
        $this->assertStringContainsString('--web-only', $output);
        $this->assertStringContainsString('--interactive', $output);
    }
}
