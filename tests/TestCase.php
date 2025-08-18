<?php

namespace AnasNashat\EasyDev\Tests;

use AnasNashat\EasyDev\Providers\EasyDevServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Filesystem\Filesystem;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = new Filesystem();
        $this->setUpDatabase();
        $this->setUpTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTestFiles();
        parent::tearDown();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            EasyDevServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('easy-dev.model_namespace', 'AnasNashat\\EasyDev\\Tests\\Fixtures\\Models\\');
        $app['config']->set('easy-dev.paths', [
            'controllers' => $this->getTestPath('Controllers'),
            'requests' => $this->getTestPath('Requests'),
            'repositories' => $this->getTestPath('Repositories'),
        ]);
    }

    /**
     * Set up test database with sample tables.
     */
    protected function setUpDatabase(): void
    {
        // Users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamps();
        });

        // Posts table
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        // Categories table (self-referencing)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });

        // Tags table
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        // Post-Tag pivot table
        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Comments table (polymorphic)
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->morphs('commentable');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Images table (polymorphic)
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->morphs('imageable');
            $table->timestamps();
        });
    }

    /**
     * Set up test file directories.
     */
    protected function setUpTestFiles(): void
    {
        $testDirs = [
            'Controllers',
            'Controllers/Api',
            'Requests',
            'Repositories',
            'Repositories/Interfaces',
            'Resources',
            'Models',
        ];

        foreach ($testDirs as $dir) {
            $path = $this->getTestPath($dir);
            if (!$this->filesystem->isDirectory($path)) {
                $this->filesystem->makeDirectory($path, 0755, true);
            }
        }
    }

    /**
     * Clean up test files after each test.
     */
    protected function cleanUpTestFiles(): void
    {
        $testPath = $this->getTestPath();
        if ($this->filesystem->isDirectory($testPath)) {
            $this->filesystem->deleteDirectory($testPath);
        }
    }

    /**
     * Get test file path.
     */
    protected function getTestPath(string $subPath = ''): string
    {
        $basePath = __DIR__ . '/temp';
        return $subPath ? $basePath . '/' . $subPath : $basePath;
    }

    /**
     * Create a test model file.
     */
    protected function createTestModel(string $modelName, string $content = null): string
    {
        if (!$content) {
            $content = $this->getDefaultModelContent($modelName);
        }

        $path = $this->getTestPath("Models/{$modelName}.php");
        $this->filesystem->put($path, $content);

        return $path;
    }

    /**
     * Get default model content.
     */
    protected function getDefaultModelContent(string $modelName): string
    {
        return "<?php

namespace AnasNashat\\EasyDev\\Tests\\Fixtures\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$modelName} extends Model
{
    protected \$fillable = ['name'];
}
";
    }

    /**
     * Assert that a file exists and contains specific content.
     */
    protected function assertFileContains(string $filePath, string $expectedContent): void
    {
        $this->assertFileExists($filePath);
        $content = $this->filesystem->get($filePath);
        $this->assertStringContainsString($expectedContent, $content);
    }

    /**
     * Assert that a file exists and doesn't contain specific content.
     */
    protected function assertFileNotContains(string $filePath, string $unexpectedContent): void
    {
        $this->assertFileExists($filePath);
        $content = $this->filesystem->get($filePath);
        $this->assertStringNotContainsString($unexpectedContent, $content);
    }

    /**
     * Get test stub content.
     */
    protected function getTestStub(string $stubName): string
    {
        $stubPath = __DIR__ . "/Fixtures/stubs/{$stubName}.stub";
        
        if (!$this->filesystem->exists($stubPath)) {
            // Create a basic stub if it doesn't exist
            return "Test stub for {$stubName}";
        }

        return $this->filesystem->get($stubPath);
    }

    /**
     * Mock Artisan command execution.
     */
    protected function mockArtisanCommand(string $command, array $parameters = []): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan($command, $parameters);
    }

    /**
     * Create test database connection for specific driver.
     */
    protected function createTestConnection(string $driver): void
    {
        $connections = [
            'mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'easy_dev_test',
                'username' => 'root',
                'password' => '',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'easy_dev_test',
                'username' => 'postgres',
                'password' => '',
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ];

        if (isset($connections[$driver])) {
            config(["database.connections.test_{$driver}" => $connections[$driver]]);
        }
    }
}
