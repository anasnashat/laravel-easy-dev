<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class DebugHelpCommandTest extends TestCase
{
    public function test_debug_help_output(): void
    {
        $exitCode = Artisan::call('easy-dev:help');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Laravel Easy Dev', $output);
        $this->assertNotEmpty($output);
    }
}
