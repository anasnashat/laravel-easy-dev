<?php

namespace AnasNashat\EasyDev\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class MigrationParser
{
    public function __construct(protected Filesystem $files)
    {
    }

    /**
     * Check if migration exists for the given model.
     */
    public function migrationExists(string $modelName): bool
    {
        $tableName = Str::snake(Str::plural($modelName));
        $migrationPattern = database_path("migrations/*_create_{$tableName}_table.php");
        
        return !empty(glob($migrationPattern));
    }

    /**
     * Get migration file path for the given model.
     */
    public function getMigrationPath(string $modelName): ?string
    {
        $tableName = Str::snake(Str::plural($modelName));
        $migrationPattern = database_path("migrations/*_create_{$tableName}_table.php");
        $files = glob($migrationPattern);
        
        if (empty($files)) {
            return null;
        }
        
        // Sort files by name (newest first due to timestamp prefix)
        rsort($files);
        
        return $files[0] ?? null;
    }

    /**
     * Parse migration file and extract column information.
     */
    public function parseMigration(string $migrationPath): array
    {
        if (!$this->files->exists($migrationPath)) {
            return [
                'columns' => [],
                'fillable' => [],
                'relationships' => []
            ];
        }

        $content = $this->files->get($migrationPath);
        
        $columns = $this->extractColumns($content);
        $fillable = $this->extractFillableColumns($content);
        $relationships = $this->extractRelationships($content);
        
        return [
            'columns' => $columns,
            'fillable' => $fillable,
            'relationships' => $relationships
        ];
    }

    /**
     * Extract column definitions from migration content.
     */
    protected function extractColumns(string $content): array
    {
        $columns = [];
        
        // Updated patterns to match Laravel migration syntax including chained methods
        $patterns = [
            // Match: $table->foreignId('user_id') - must come first to avoid conflicts
            '/\$table->foreignId\([\'"](\w+)[\'"]?\)(?:->[\w()]+)*/',
            // Match: $table->morphs('imageable') - creates imageable_type and imageable_id
            '/\$table->morphs\([\'"](\w+)[\'"]?\)(?:->[\w()]+)*/',
            // Match: $table->string('name') or $table->string('name', 255) - general pattern
            '/\$table->(\w+)\([\'"](\w+)[\'"](?:,\s*(\d+))?\)(?:->[\w()]+)*/',
        ];

        foreach ($patterns as $index => $pattern) {
            try {
                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        if ($index === 0) {
                            // foreignId
                            $type = 'foreignId';
                            $name = $match[1];
                            $length = null;
                        } elseif ($index === 1) {
                            // morphs - creates two columns but don't add the base name to columns
                            $baseName = $match[1];
                            // Add both type and id columns for morphs
                            $columns[] = [
                                'name' => $baseName . '_type',
                                'type' => 'string',
                                'length' => null,
                                'nullable' => false,
                                'unique' => false,
                            ];
                            $columns[] = [
                                'name' => $baseName . '_id',
                                'type' => 'unsignedBigInteger',
                                'length' => null,
                                'nullable' => false,
                                'unique' => false,
                            ];
                            continue;
                        } elseif ($index === 2) {
                            // Standard column type
                            $type = $match[1];
                            $name = $match[2];
                            $length = $match[3] ?? null;
                            
                            // Skip if it's actually a foreignId or morphs (already handled)
                            if ($type === 'foreignId' || $type === 'morphs') {
                                continue;
                            }
                        }
                        
                        // Skip Laravel's default columns
                        if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                            continue;
                        }

                        $columns[] = [
                            'name' => $name,
                            'type' => $type,
                            'length' => $length,
                            'nullable' => $this->isColumnNullable($content, $name),
                            'unique' => $this->isColumnUnique($content, $name),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Skip invalid patterns
                continue;
            }
        }

        return $columns;
    }

    /**
     * Extract fillable columns from migration (exclude timestamps and IDs).
     */
    protected function extractFillableColumns(string $content): array
    {
        $columns = $this->extractColumns($content);
        $fillable = [];

        foreach ($columns as $column) {
            // Skip foreign key columns ending with _id for fillable
            if (!str_ends_with($column['name'], '_id')) {
                $fillable[] = $column['name'];
            } else {
                // Include foreign keys in fillable too, as they might be mass assignable
                $fillable[] = $column['name'];
            }
        }

        return $fillable;
    }

    /**
     * Extract relationship information from foreign key constraints.
     */
    protected function extractRelationships(string $content): array
    {
        $relationships = [];

        // Simple pattern for foreign keys ending with _id
        if (preg_match_all('/(\w+_id)/', $content, $matches)) {
            foreach ($matches[1] as $columnName) {
                if ($columnName !== 'id') {
                    $referencedTable = $this->guessTableFromForeignKey($columnName);
                    if ($referencedTable) {
                        $relationships[] = [
                            'type' => 'belongsTo',
                            'column' => $columnName,
                            'related_table' => $referencedTable,
                            'related_model' => Str::studly(Str::singular($referencedTable)),
                            'method_name' => $this->generateMethodName($columnName, 'belongsTo')
                        ];
                    }
                }
            }
        }

        return $relationships;
    }

    /**
     * Check if a column is nullable.
     */
    protected function isColumnNullable(string $content, string $columnName): bool
    {
        $pattern = '/\$table->\w+\([\'"]' . preg_quote($columnName) . '[\'"].*?\)->nullable\(\)/';
        return preg_match($pattern, $content) === 1;
    }

    /**
     * Check if a column is unique.
     */
    protected function isColumnUnique(string $content, string $columnName): bool
    {
        $patterns = [
            '/\$table->\w+\([\'"]' . preg_quote($columnName) . '[\'"].*?\)->unique\(\)/',
            '/\$table->unique\([\'"]' . preg_quote($columnName) . '[\'"]\)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guess table name from foreign key column name.
     */
    protected function guessTableFromForeignKey(string $columnName): ?string
    {
        if (str_ends_with($columnName, '_id')) {
            $tableName = substr($columnName, 0, -3);
            return Str::plural($tableName);
        }

        return null;
    }

    /**
     * Generate method name for relationship.
     */
    protected function generateMethodName(string $columnName, string $relationType): string
    {
        if ($relationType === 'belongsTo' && str_ends_with($columnName, '_id')) {
            return Str::camel(substr($columnName, 0, -3));
        }

        return Str::camel($columnName);
    }

    /**
     * Find reverse relationships by scanning all migrations.
     */
    public function findReverseRelationships(string $modelName): array
    {
        $tableName = Str::snake(Str::plural($modelName));
        $relationships = [];

        // For now, return empty array to avoid regex issues
        // This can be enhanced later with simpler parsing
        return $relationships;
    }

    /**
     * Generate validation rules based on column definitions.
     */
    public function generateValidationRules(array $columns): array
    {
        $rules = [];

        foreach ($columns as $column) {
            $columnRules = [];

            // Required rule
            if (!$column['nullable']) {
                $columnRules[] = 'required';
            }

            // Type-based rules
            switch ($column['type']) {
                case 'string':
                case 'varchar':
                    $columnRules[] = 'string';
                    if ($column['length']) {
                        $columnRules[] = "max:{$column['length']}";
                    } else {
                        $columnRules[] = 'max:255';
                    }
                    break;

                case 'text':
                case 'longText':
                    $columnRules[] = 'string';
                    break;

                case 'integer':
                case 'bigInteger':
                case 'unsignedInteger':
                case 'unsignedBigInteger':
                    $columnRules[] = 'integer';
                    break;

                case 'decimal':
                case 'double':
                case 'float':
                    $columnRules[] = 'numeric';
                    break;

                case 'boolean':
                    $columnRules[] = 'boolean';
                    break;

                case 'date':
                    $columnRules[] = 'date';
                    break;

                case 'datetime':
                case 'timestamp':
                    $columnRules[] = 'date_format:Y-m-d H:i:s';
                    break;

                case 'email':
                    $columnRules[] = 'email';
                    break;
            }

            // Unique rule
            if ($column['unique']) {
                $columnRules[] = 'unique:' . Str::snake(Str::plural(class_basename($column['name'])));
            }

            // Foreign key rule
            if (str_ends_with($column['name'], '_id')) {
                $tableName = $this->guessTableFromForeignKey($column['name']);
                if ($tableName) {
                    $columnRules[] = "exists:{$tableName},id";
                }
            }

            if (!empty($columnRules)) {
                $rules[$column['name']] = $columnRules;
            }
        }

        return $rules;
    }
}
