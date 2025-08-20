<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class DebugHelpOutputTest extends TestCase
{
    public function test_debug_actual_output(): void
    {
        // Use a different approach - capture via artisan call and output buffer
        $exitCode = Artisan::call('easy-dev:help');
        $output = Artisan::output();
        
        // Write to a file for inspection
        $debugPath = __DIR__ . '/actual_output.txt';
        file_put_contents($debugPath, $output);
        
        // Basic assertions
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Laravel Easy Dev Package', $output);
    }
}
