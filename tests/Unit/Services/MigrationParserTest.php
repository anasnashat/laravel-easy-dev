<?php

namespace AnasNashat\EasyDev\Tests\Unit\Services;

use AnasNashat\EasyDev\Services\MigrationParser;
use AnasNashat\EasyDev\Tests\UnitTestCase;

class MigrationParserTest extends UnitTestCase
{
    private MigrationParser $migrationParser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrationParser = app(MigrationParser::class);
    }

    public function test_parse_migration_extracts_columns(): void
    {
        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->foreignId('category_id')->constrained();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
PHP;

        $tempPath = sys_get_temp_dir() . '/test_migration_' . time() . '.php';
        file_put_contents($tempPath, $migrationContent);

        try {
            $result = $this->migrationParser->parseMigration($tempPath);

            $this->assertArrayHasKey('columns', $result);
            $this->assertArrayHasKey('fillable', $result);
            $this->assertArrayHasKey('relationships', $result);

            // fillable should contain columns from migration
            $this->assertContains('name', $result['fillable']);
            $this->assertContains('description', $result['fillable']);
            $this->assertContains('quantity', $result['fillable']);
            $this->assertNotContains('id', $result['fillable']);
        } finally {
            unlink($tempPath);
        }
    }

    public function test_generate_validation_rules_for_string_columns(): void
    {
        $columns = [
            ['name' => 'title', 'type' => 'string', 'length' => null, 'nullable' => false, 'unique' => false],
            ['name' => 'email', 'type' => 'string', 'length' => 255, 'nullable' => false, 'unique' => false],
        ];

        $rules = $this->migrationParser->generateValidationRules($columns);

        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('required', $rules['title']);
        $this->assertContains('string', $rules['title']);
    }

    public function test_generate_validation_rules_for_integer_columns(): void
    {
        $columns = [
            ['name' => 'quantity', 'type' => 'integer', 'length' => null, 'nullable' => false, 'unique' => false],
        ];

        $rules = $this->migrationParser->generateValidationRules($columns);

        $this->assertArrayHasKey('quantity', $rules);
        $this->assertContains('integer', $rules['quantity']);
    }

    public function test_generate_validation_rules_for_boolean_columns(): void
    {
        $columns = [
            ['name' => 'is_active', 'type' => 'boolean', 'length' => null, 'nullable' => false, 'unique' => false],
        ];

        $rules = $this->migrationParser->generateValidationRules($columns);

        $this->assertArrayHasKey('is_active', $rules);
        $this->assertContains('boolean', $rules['is_active']);
    }

    public function test_generate_validation_rules_for_nullable_columns(): void
    {
        $columns = [
            ['name' => 'bio', 'type' => 'text', 'length' => null, 'nullable' => true, 'unique' => false],
        ];

        $rules = $this->migrationParser->generateValidationRules($columns);

        $this->assertArrayHasKey('bio', $rules);
        $this->assertNotContains('required', $rules['bio']);
    }

    public function test_migration_exists_returns_false_for_nonexistent(): void
    {
        $result = $this->migrationParser->migrationExists('NonExistentModel12345');
        $this->assertFalse($result);
    }
}
