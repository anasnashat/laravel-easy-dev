<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class DebugHelpCommandTest extends TestCase
{
    public function test_debug_help_output(): void
    {
        // Capture output using buffer
        ob_start();
        $this->artisan('easy-dev:help');
        $output = ob_get_clean();
        
        // Debug the actual output
        file_put_contents(__DIR__ . '/debug_output.txt', $output);
        
        $this->assertTrue(str_contains($output, 'Laravel Easy Dev Package'));
    }
}
