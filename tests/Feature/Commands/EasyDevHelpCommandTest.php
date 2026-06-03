<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class EasyDevHelpCommandTest extends TestCase
{
    public function test_help_command_displays_banner(): void
    {
        [$exitCode, $output] = $this->callArtisanWithOutput('easy-dev:help');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Laravel Easy Dev', $output);
    }

    public function test_help_command_lists_all_commands(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:help');

        $this->assertStringContainsString('Available Commands', $output);
        $this->assertStringContainsString('easy-dev:make', $output);
        $this->assertStringContainsString('easy-dev:crud', $output);
        $this->assertStringContainsString('easy-dev:repository', $output);
        $this->assertStringContainsString('easy-dev:sync-relations', $output);
    }

    public function test_help_command_shows_usage_examples(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:help', ['--examples' => true]);

        $this->assertStringContainsString('Usage Examples', $output);
        $this->assertStringContainsString('easy-dev:make Product', $output);
        $this->assertStringContainsString('easy-dev:crud Order', $output);
        $this->assertStringContainsString('easy-dev:sync-relations', $output);
    }

    public function test_help_command_shows_tips_section(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:help', ['--examples' => true]);

        $this->assertStringContainsString('Pro Tips', $output);
        $this->assertStringContainsString('--interactive', $output);
        $this->assertStringContainsString('--with-repository', $output);
    }

    public function test_help_command_shows_github_link(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:help');

        $this->assertStringContainsString('github.com/anasnashat/laravel-easy-dev', $output);
    }

    public function test_help_command_shows_command_descriptions(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:help');

        $this->assertStringContainsString('CRUD generator', $output);
        $this->assertStringContainsString('repository', $output);
        $this->assertStringContainsString('relationships', $output);
    }

    public function test_help_command_shows_command_options(): void
    {
        [, $output] = $this->callArtisanWithOutput('easy-dev:help');

        $this->assertStringContainsString('--with-repository', $output);
        $this->assertStringContainsString('--with-service', $output);
        $this->assertStringContainsString('--without-interface', $output);
        $this->assertStringContainsString('--api-only', $output);
        $this->assertStringContainsString('--web-only', $output);
        $this->assertStringContainsString('--interactive', $output);
    }
}
