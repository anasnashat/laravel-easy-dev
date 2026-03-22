<?php

namespace AnasNashat\EasyDev\Tests\Unit\Services;

use AnasNashat\EasyDev\Services\CodeWriter;
use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Exceptions\RelationAlreadyExistsException;
use AnasNashat\EasyDev\Tests\UnitTestCase;
use Illuminate\Filesystem\Filesystem;
use Mockery;

class CodeWriterTest extends UnitTestCase
{
    private CodeWriter $codeWriter;
    protected Filesystem $filesystem;
    private FileGenerator $fileGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->fileGenerator = Mockery::mock(FileGenerator::class);
        $this->codeWriter = new CodeWriter($this->filesystem, $this->fileGenerator);
    }

    public function test_add_relation_successfully(): void
    {
        $modelPath = '/path/to/User.php';
        $methodName = 'posts';
        $relationType = 'hasMany';
        $relatedModelClass = 'App\\Models\\Post';

        $existingContent = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [\'name\', \'email\'];
}';

        $relationStub = '
/**
 * Get the posts for this user.
 */
public function posts()
{
    return $this->hasMany(Post::class);
}';

        $this->filesystem
            ->shouldReceive('exists')
            ->with($modelPath)
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->with($modelPath)
            ->andReturn($existingContent);

        $this->fileGenerator
            ->shouldReceive('getStubContent')
            ->with("relations/{$relationType}", [
                'methodName' => $methodName,
                'relatedModel' => 'Post',
                'relatedModelClass' => $relatedModelClass,
            ])
            ->andReturn($relationStub);

        $this->filesystem
            ->shouldReceive('put')
            ->with($modelPath, Mockery::on(function($content) {
                // Ignore all whitespace for comparison
                return preg_replace('/\s+/', '', $content) === preg_replace('/\s+/', '', '<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class User extends Model
{
    protected $fillable = [\'name\', \'email\'];
    /**
     * Get the posts for this user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}');
            }))
            ->once();

        $this->codeWriter->addRelation($modelPath, $methodName, $relationType, $relatedModelClass);
        $this->assertTrue(true); // Prevent risky test
    }

    public function test_add_relation_throws_exception_for_existing_method(): void
    {
        $modelPath = '/path/to/User.php';
        $methodName = 'posts';
        $relationType = 'hasMany';
        $relatedModelClass = 'App\\Models\\Post';

        $existingContent = '<?php

class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}';

        $this->filesystem
            ->shouldReceive('exists')
            ->with($modelPath)
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->with($modelPath)
            ->andReturn($existingContent);

        $this->expectException(RelationAlreadyExistsException::class);
        $this->expectExceptionMessage("Method 'posts' already exists on model.");

        $this->codeWriter->addRelation($modelPath, $methodName, $relationType, $relatedModelClass);
    }

    public function test_add_relation_throws_exception_for_missing_file(): void
    {
        $modelPath = '/path/to/NonExistent.php';
        $methodName = 'posts';
        $relationType = 'hasMany';
        $relatedModelClass = 'App\\Models\\Post';

        $this->filesystem
            ->shouldReceive('exists')
            ->with($modelPath)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Model file not found at path: {$modelPath}");

        $this->codeWriter->addRelation($modelPath, $methodName, $relationType, $relatedModelClass);
    }

    public function test_add_use_statement(): void
    {
        $filePath = '/path/to/Controller.php';
        $className = 'App\\Models\\Post';

        $existingContent = '<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PostController extends Controller
{
}';

        $this->filesystem
            ->shouldReceive('exists')
            ->with($filePath)
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->with($filePath)
            ->andReturn($existingContent);

        $this->filesystem
            ->shouldReceive('put')
            ->with($filePath, Mockery::on(function($content) {
                 return preg_replace('/\s+/', '', $content) === preg_replace('/\s+/', '', '<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Post;
class PostController extends Controller
{
}');
            }))
            ->once();

        $this->codeWriter->addUseStatement($filePath, $className);
        $this->assertTrue(true); // Prevent risky test
    }

    public function test_add_use_statement_skips_if_already_exists(): void
    {
        $filePath = '/path/to/Controller.php';
        $className = 'App\\Models\\Post';

        $existingContent = '<?php

namespace App\Http\Controllers;

use App\\Models\\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
}';

        $this->filesystem
            ->shouldReceive('exists')
            ->with($filePath)
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->with($filePath)
            ->andReturn($existingContent);

        // Should not call put since use statement already exists
        $this->filesystem
            ->shouldNotReceive('put');

        $this->codeWriter->addUseStatement($filePath, $className);
        $this->assertTrue(true); // Prevent risky test
    }

    public function test_method_exists(): void
    {
        $filePath = '/path/to/Model.php';
        
        $contentWithMethod = '<?php
class Model {
    public function testMethod() {}
}';

        $contentWithoutMethod = '<?php
class Model {
    public function otherMethod() {}
}';

        // Test when method exists
        $this->filesystem
            ->shouldReceive('exists')
            ->with($filePath)
            ->andReturn(true);

        $this->filesystem
            ->shouldReceive('get')
            ->once()
            ->with($filePath)
            ->andReturn($contentWithMethod);

        $this->assertTrue($this->codeWriter->methodExists($filePath, 'testMethod'));

        // Test when method doesn't exist
        $this->filesystem
            ->shouldReceive('get')
            ->once()
            ->with($filePath)
            ->andReturn($contentWithoutMethod);

        $this->assertFalse($this->codeWriter->methodExists($filePath, 'testMethod'));

        // Test when file doesn't exist
        $this->filesystem
            ->shouldReceive('exists')
            ->with('/nonexistent.php')
            ->andReturn(false);

        $this->assertFalse($this->codeWriter->methodExists('/nonexistent.php', 'testMethod'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
