<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class EasyDevNewCommandsTest extends TestCase
{
    /**
     * Test easy-dev:snapshot standard command execution.
     */
    public function test_snapshot_command_displays_visual_banner(): void
    {
        $exitCode = Artisan::call('easy-dev:snapshot');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Laravel Easy Dev v3 - AI-Native Model Snapshot', $output);
        $this->assertStringContainsString('Model: User', $output);
        $this->assertStringContainsString('Model: Post', $output);
    }

    /**
     * Test easy-dev:snapshot AI-mode JSON output.
     */
    public function test_snapshot_command_outputs_ai_json(): void
    {
        $exitCode = Artisan::call('easy-dev:snapshot', ['--ai' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        
        $json = json_decode($output, true);
        $this->assertNotNull($json);
        $this->assertEquals('success', $json['status']);
        $this->assertArrayHasKey('models', $json);
        $this->assertArrayHasKey('User', $json['models']);
        $this->assertArrayHasKey('Post', $json['models']);
        
        // Assert Post columns details
        $postModel = $json['models']['Post'];
        $this->assertEquals('posts', $postModel['table']);
        $this->assertNotEmpty($postModel['columns']);
        
        // Assert Post relations details
        $this->assertNotEmpty($postModel['relations']);
        
        $relationsByName = [];
        foreach ($postModel['relations'] as $rel) {
            $relationsByName[$rel['name']] = $rel;
        }

        $this->assertArrayHasKey('user', $relationsByName);
        $this->assertEquals('belongsTo', $relationsByName['user']['type']);
        $this->assertEquals('User', $relationsByName['user']['related']);

        $this->assertArrayHasKey('category', $relationsByName);
        $this->assertEquals('belongsTo', $relationsByName['category']['type']);
        $this->assertEquals('Category', $relationsByName['category']['related']);
    }

    /**
     * Test easy-dev:info standard command execution.
     */
    public function test_info_command_displays_markdown_audit(): void
    {
        $exitCode = Artisan::call('easy-dev:info', ['model' => 'Post']);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Laravel Easy Dev v3 - Model Audit for Post', $output);
        $this->assertStringContainsString('| Field | Type | Nullable | Default | Fillable | Hidden | Cast |', $output);
        $this->assertStringContainsString('title', $output);
        $this->assertStringContainsString('user()', $output);
    }

    /**
     * Test easy-dev:info AI-mode JSON output.
     */
    public function test_info_command_outputs_ai_json(): void
    {
        $exitCode = Artisan::call('easy-dev:info', ['model' => 'Post', '--ai' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);

        $json = json_decode($output, true);
        $this->assertNotNull($json);
        $this->assertEquals('success', $json['status']);
        
        $data = $json['data'];
        $this->assertEquals('Post', $data['model']);
        $this->assertEquals('posts', $data['table']);
        $this->assertNotEmpty($data['columns']);
        $this->assertNotEmpty($data['relations']);
    }

    /**
     * Test easy-dev:info fails gracefully for nonexistent model.
     */
    public function test_info_command_handles_nonexistent_model(): void
    {
        $exitCode = Artisan::call('easy-dev:info', ['model' => 'NonExistentModel']);
        $output = Artisan::output();

        // Since AnasNashat\EasyDev\Tests\Fixtures\Models\NonExistentModel exists but we haven't loaded it or it's not a real class on path:
        // Wait, does it exist? Let's check:
        // In the Fixtures folder there is a NonExistentModel.php but let's see. Let's try inspecting 'FakeModel' instead.
        $exitCode = Artisan::call('easy-dev:info', ['model' => 'FakeModel']);
        $output = Artisan::output();
        
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Error: Model class', $output);
    }

    /**
     * Test easy-dev:dream parser and dry-run output.
     */
    public function test_dream_command_dry_run_parses_prompt(): void
    {
        $exitCode = Artisan::call('easy-dev:dream', [
            'prompt' => 'Create new product with name:string and price:decimal connected to users',
            '--dry-run' => true
        ]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Compiled Scaffolding Blueprint Plan', $output);
        $this->assertStringContainsString('Entity (Model Name): Product', $output);
        $this->assertStringContainsString('name : string', $output);
        $this->assertStringContainsString('price : decimal', $output);
        $this->assertStringContainsString('BelongsTo User', $output);
    }

    /**
     * Test easy-dev:dream AI-mode dry-run JSON.
     */
    public function test_dream_command_ai_dry_run_outputs_json(): void
    {
        $exitCode = Artisan::call('easy-dev:dream', [
            'prompt' => 'Create a post with title:string and body:text connected to users',
            '--dry-run' => true,
            '--ai' => true
        ]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);

        $json = json_decode($output, true);
        $this->assertNotNull($json);
        $this->assertEquals('success', $json['status']);
        $this->assertTrue($json['dry_run']);
        
        $plans = $json['plans'];
        $this->assertEquals('Post', $plans['model']);
        $this->assertEquals(['User'], $plans['relations']);
        $this->assertEquals('string', $plans['fields']['title']);
        $this->assertEquals('text', $plans['fields']['body']);
    }

    /**
     * Test that dashboard routes are not registered or accessible in a non-local environment (like testing).
     */
    public function test_dashboard_routes_are_inaccessible_in_non_local_environments(): void
    {
        $response = $this->get('/easy-dev');
        $response->assertStatus(404);
    }
}
