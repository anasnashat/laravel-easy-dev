<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class MakeDtoCommandTest extends TestCase
{
    public function test_dto_command_requires_model_argument(): void
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this->artisan('easy-dev:dto');
    }

    public function test_dto_command_generates_file(): void
    {
        $dtoPath = app_path('DTOs/PostData.php');

        // Clean up if exists
        if (file_exists($dtoPath)) {
            unlink($dtoPath);
        }

        $this->artisan('easy-dev:dto', ['model' => 'Post'])
            ->expectsOutputToContain('Generated DTO')
            ->assertExitCode(0);

        $this->assertFileExists($dtoPath);

        $content = file_get_contents($dtoPath);
        $this->assertStringContainsString('class PostData', $content);
        $this->assertStringContainsString('fromRequest', $content);
        $this->assertStringContainsString('fromModel', $content);
        $this->assertStringContainsString('toArray', $content);

        // Clean up
        unlink($dtoPath);
        @rmdir(dirname($dtoPath));
    }
}
