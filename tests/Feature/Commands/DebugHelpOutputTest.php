<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;

class DebugHelpOutputTest extends TestCase
{
    public function test_debug_actual_output(): void
    {
        [$exitCode, $output] = $this->callArtisanWithOutput('easy-dev:help');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Laravel Easy Dev', $output);
        $this->assertStringContainsString('Available Commands', $output);
    }
}
