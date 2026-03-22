<?php

namespace AnasNashat\EasyDev\Tests\Unit\Services;

use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Tests\UnitTestCase;
use Illuminate\Filesystem\Filesystem;
use Mockery;

class FileGeneratorTest extends UnitTestCase
{
    private FileGenerator $fileGenerator;
    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->fileGenerator = new FileGenerator($this->filesystem);
    }

    public function test_get_stub_content_with_replacements(): void
    {
        $stubContent = 'Hello {{ name }}, welcome to {{ app }}!';
        $stubName = 'test';
        
        // Mock both potential paths that getStubPath() checks
        $this->filesystem
            ->shouldReceive('exists')
            ->atLeast()->once()
            ->andReturn(false, true); // First false for custom, second true for package stub
            
        $this->filesystem
            ->shouldReceive('get')
            ->once()
            ->andReturn($stubContent);

        $result = $this->fileGenerator->getStubContent($stubName, [
            'name' => 'John',
            'app' => 'Laravel',
        ]);

        $this->assertEquals('Hello John, welcome to Laravel!', $result);
    }

    public function test_generate_file_creates_directory_if_not_exists(): void
    {
        $filePath = '/path/to/file.php';
        $stubName = 'test';
        $directory = '/path/to';
        
        $this->filesystem
            ->shouldReceive('exists')
            ->andReturn(true);
            
        $this->filesystem
            ->shouldReceive('get')
            ->andReturn('Test content');
            
        $this->filesystem
            ->shouldReceive('isDirectory')
            ->with($directory)
            ->andReturn(false);
            
        $this->filesystem
            ->shouldReceive('makeDirectory')
            ->with($directory, 0755, true)
            ->once();
            
        $this->filesystem
            ->shouldReceive('put')
            ->with($filePath, 'Test content')
            ->once();

        $this->fileGenerator->generateFile($filePath, $stubName, []);
        $this->assertTrue(true);
    }

    public function test_get_model_name_from_table(): void
    {
        $this->assertEquals('User', $this->fileGenerator->getModelNameFromTable('users'));
        $this->assertEquals('Post', $this->fileGenerator->getModelNameFromTable('posts'));
        $this->assertEquals('Category', $this->fileGenerator->getModelNameFromTable('categories'));
        $this->assertEquals('PostTag', $this->fileGenerator->getModelNameFromTable('post_tags'));
    }

    public function test_get_controller_name_from_model(): void
    {
        $this->assertEquals('UserController', $this->fileGenerator->getControllerNameFromModel('User'));
        $this->assertEquals('PostController', $this->fileGenerator->getControllerNameFromModel('Post'));
        $this->assertEquals('CategoryController', $this->fileGenerator->getControllerNameFromModel('Category'));
    }

    public function test_get_request_names_from_model(): void
    {
        $result = $this->fileGenerator->getRequestNamesFromModel('Post');
        
        $this->assertEquals([
            'store' => 'StorePostRequest',
            'update' => 'UpdatePostRequest',
        ], $result);
    }

    public function test_get_stub_content_throws_exception_for_missing_stub(): void
    {
        $this->filesystem
            ->shouldReceive('exists')
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stub file not found:');

        $this->fileGenerator->getStubContent('nonexistent');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
