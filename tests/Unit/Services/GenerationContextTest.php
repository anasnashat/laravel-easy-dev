<?php

namespace AnasNashat\EasyDev\Tests\Unit\Services;

use AnasNashat\EasyDev\Services\GenerationContext;
use AnasNashat\EasyDev\Tests\UnitTestCase;
use Illuminate\Filesystem\Filesystem;
use Mockery;

class GenerationContextTest extends UnitTestCase
{
    private GenerationContext $context;
    protected Filesystem $mockFilesystem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockFilesystem = Mockery::mock(Filesystem::class);
        $this->context = new GenerationContext($this->mockFilesystem);
    }

    public function test_dry_run_mode_defaults_to_false(): void
    {
        $this->assertFalse($this->context->isDryRun());
    }

    public function test_set_dry_run_mode(): void
    {
        $this->context->setDryRun(true);
        $this->assertTrue($this->context->isDryRun());
    }

    public function test_record_created_file(): void
    {
        $this->context->recordCreatedFile('/path/to/file.php', 'controller');

        $created = $this->context->getCreatedFiles();
        $this->assertArrayHasKey('/path/to/file.php', $created);
        $this->assertEquals('controller', $created['/path/to/file.php']);
    }

    public function test_record_modified_file_creates_backup(): void
    {
        $originalPath = '/path/to/model.php';
        $backupPath = '/path/to/model.php.bak';

        $this->mockFilesystem
            ->shouldReceive('exists')
            ->with($originalPath)
            ->andReturn(true);

        $this->mockFilesystem
            ->shouldReceive('copy')
            ->with($originalPath, $backupPath)
            ->once();

        $this->context->recordModifiedFile($originalPath);

        $modified = $this->context->getModifiedFiles();
        $this->assertArrayHasKey($originalPath, $modified);
        $this->assertEquals($backupPath, $modified[$originalPath]);
    }

    public function test_dry_run_does_not_create_backup(): void
    {
        $this->context->setDryRun(true);

        $this->mockFilesystem->shouldNotReceive('exists');
        $this->mockFilesystem->shouldNotReceive('copy');

        $this->context->recordModifiedFile('/path/to/file.php');

        $this->assertEmpty($this->context->getModifiedFiles());
    }

    public function test_rollback_deletes_created_files(): void
    {
        $path = '/path/to/created.php';

        $this->context->recordCreatedFile($path, 'file');

        $this->mockFilesystem
            ->shouldReceive('exists')
            ->with($path)
            ->andReturn(true);

        $this->mockFilesystem
            ->shouldReceive('delete')
            ->with($path)
            ->once();

        $this->context->rollback();

        $this->assertEmpty($this->context->getCreatedFiles());
    }

    public function test_rollback_restores_modified_files(): void
    {
        $originalPath = '/path/to/model.php';
        $backupPath = '/path/to/model.php.bak';

        $this->mockFilesystem
            ->shouldReceive('exists')
            ->with($originalPath)
            ->andReturn(true);

        $this->mockFilesystem
            ->shouldReceive('copy')
            ->with($originalPath, $backupPath)
            ->once();

        $this->context->recordModifiedFile($originalPath);

        // Now rollback
        $this->mockFilesystem
            ->shouldReceive('exists')
            ->with($backupPath)
            ->andReturn(true);

        $this->mockFilesystem
            ->shouldReceive('move')
            ->with($backupPath, $originalPath)
            ->once();

        $this->context->rollback();

        $this->assertEmpty($this->context->getModifiedFiles());
    }

    public function test_get_dry_run_summary(): void
    {
        $this->context->recordCreatedFile('/path/to/controller.php', 'controller');
        $this->context->setDryRun(true);
        $this->context->recordModifiedFile('/path/to/routes.php');

        $summary = $this->context->getDryRunSummary();

        $this->assertCount(2, $summary);
        $this->assertStringContainsString('[CREATE]', $summary[0]);
        $this->assertStringContainsString('[MODIFY]', $summary[1]);
    }

    public function test_get_summary_counts(): void
    {
        $this->context->recordCreatedFile('/file1.php', 'controller');
        $this->context->recordCreatedFile('/file2.php', 'model');
        $this->context->setDryRun(true);
        $this->context->recordModifiedFile('/file3.php');

        $summary = $this->context->getSummary();

        $this->assertEquals(2, $summary['created']);
        $this->assertEquals(0, $summary['modified']); // dry-run, so no backup created
        $this->assertEquals(3, $summary['total_actions']);
    }

    public function test_reset_clears_all_state(): void
    {
        $this->context->recordCreatedFile('/file1.php', 'controller');
        $this->context->setDryRun(true);

        $this->context->reset();

        $this->assertFalse($this->context->isDryRun());
        $this->assertEmpty($this->context->getCreatedFiles());
        $this->assertEmpty($this->context->getModifiedFiles());
        $this->assertEmpty($this->context->getDryRunSummary());
    }

    public function test_cleanup_backups_removes_backup_files(): void
    {
        $originalPath = '/path/to/model.php';
        $backupPath = '/path/to/model.php.bak';

        $this->mockFilesystem
            ->shouldReceive('exists')
            ->with($originalPath)
            ->andReturn(true);

        $this->mockFilesystem
            ->shouldReceive('copy')
            ->with($originalPath, $backupPath)
            ->once();

        $this->context->recordModifiedFile($originalPath);

        // Cleanup
        $this->mockFilesystem
            ->shouldReceive('exists')
            ->with($backupPath)
            ->andReturn(true);

        $this->mockFilesystem
            ->shouldReceive('delete')
            ->with($backupPath)
            ->once();

        $this->context->cleanupBackups();

        // Verify the backup was requested for cleanup
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
