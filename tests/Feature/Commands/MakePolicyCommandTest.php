<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class MakePolicyCommandTest extends TestCase
{
    public function test_policy_command_requires_model_argument(): void
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this->artisan('easy-dev:policy');
    }

    public function test_policy_command_generates_file(): void
    {
        $policyPath = app_path('Policies/PostPolicy.php');

        // Clean up if exists
        if (file_exists($policyPath)) {
            unlink($policyPath);
        }

        $this->artisan('easy-dev:policy', ['model' => 'Post'])
            ->expectsOutputToContain('Generated policy')
            ->assertExitCode(0);

        $this->assertFileExists($policyPath);

        $content = file_get_contents($policyPath);
        $this->assertStringContainsString('class PostPolicy', $content);
        $this->assertStringContainsString('function viewAny', $content);
        $this->assertStringContainsString('function create', $content);
        $this->assertStringContainsString('function update', $content);
        $this->assertStringContainsString('function delete', $content);
        $this->assertStringContainsString('function restore', $content);
        $this->assertStringContainsString('function forceDelete', $content);

        // Clean up
        unlink($policyPath);
        @rmdir(dirname($policyPath));
    }
}
