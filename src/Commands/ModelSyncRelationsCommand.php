<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ModelSyncRelationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easy-dev:sync-relations {model? : The name of the model to synchronize relations for}
                            {--all : Sync relations for all models}
                            {--morph-targets= : Comma-separated list of models to apply polymorphic relationships to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically detect and add relationships to models based on schema.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->syncAllModels();
            return 0;
        }

        $modelName = $this->argument('model');
        if (!$modelName) {
            $this->error('Not enough arguments (missing: "model"). Use --all option to sync all models or provide a specific model name.');
            return 1;
        }

        $modelName = Str::studly($modelName);
        $bidirectionalRelationships = $this->syncModelRelations($modelName, true);
        
        // Process relationships for related models
        if (!empty($bidirectionalRelationships)) {
            $this->info("🔄 Adding reverse relationships to related models...");
            foreach ($bidirectionalRelationships as $relationData) {
                $relatedModelName = $relationData['related_model'];
                
                // Only add reverse relationship if model exists
                $relatedModelPath = app_path("Models/{$relatedModelName}.php");
                if (file_exists($relatedModelPath)) {
                    $reverseRelationship = [
                        $relationData['reverse_relationship']
                    ];
                    $this->updateModelWithRelationships($relatedModelName, $reverseRelationship);
                } else {
                    $this->warn("Model {$relatedModelName} not found, skipping reverse relationship.");
                }
            }
        }
        
        return 0;
    }

    /**
     * Synchronize relations for all models in the Models directory
     */
    protected function syncAllModels()
    {
        $modelsPath = app_path('Models');
        $files = scandir($modelsPath);
        $count = 0;
        $bidirectionalRelationshipsByModel = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $modelName = pathinfo($file, PATHINFO_FILENAME);
                $bidirectionalRelationships = $this->syncModelRelations($modelName, true);
                
                if (!empty($bidirectionalRelationships)) {
                    $bidirectionalRelationshipsByModel[$modelName] = $bidirectionalRelationships;
                }
                
                $count++;
            }
        }

        // After all models have been processed, add reverse relationships
        if (!empty($bidirectionalRelationshipsByModel)) {
            $this->info("🔄 Adding reverse relationships to related models...");
            
            foreach ($bidirectionalRelationshipsByModel as $sourceModel => $relationships) {
                foreach ($relationships as $relationData) {
                    $relatedModelName = $relationData['related_model'];
                    
                    // Only add reverse relationship if model exists
                    $relatedModelPath = app_path("Models/{$relatedModelName}.php");
                    if (file_exists($relatedModelPath)) {
                        $reverseRelationship = [
                            $relationData['reverse_relationship']
                        ];
                        $this->updateModelWithRelationships($relatedModelName, $reverseRelationship);
                    }
                }
            }
        }

        $this->info("✅ Synchronized relations for {$count} models");
    }

    /**
     * Sync relations for a specific model.
     */
    protected function syncModelRelations(string $modelName, bool $showHeader = true): int
    {
        try {
            if ($showHeader) {
                $this->info("Analyzing relations for {$modelName}...");
            }

            // Use improved relationship detection
            $relationships = $this->detectRelationshipsImproved($modelName);

            if (empty($relationships)) {
                $this->line("  No new relations found for {$modelName}.");
                return self::SUCCESS;
            }

            // Update the model with relationships
            $this->updateModelWithRelationships($modelName, $relationships);

            $this->info("  Successfully processed relationships for {$modelName}!");
            return self::SUCCESS;

        } catch (ModelNotFoundException $e) {
            $this->error("  Model not found: {$e->getMessage()}");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("  Error processing {$modelName}: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Improved relationship detection from database and migrations
     */
    protected function detectRelationshipsImproved(string $modelName): array
    {
        $relationships = [];
        $tableName = $this->getTableName($modelName);
        
        $this->info("  Checking table: {$tableName}");
        
        // First try database detection
        if (Schema::hasTable($tableName)) {
            $relationships = $this->detectDatabaseRelationships($modelName, $tableName);
        }
        
        // If no relationships found in database, try migration files
        if (empty($relationships)) {
            $this->info("  No database relationships found. Checking migration files...");
            $relationships = $this->detectRelationshipsFromMigrations($modelName, $tableName);
        }
        
        return $relationships;
    }

    /**
     * Detect relationships from database schema (SQLite)
     */
    protected function detectDatabaseRelationships(string $modelName, string $tableName): array
    {
        $relationships = [];
        
        try {
            // For SQLite, check foreign keys using PRAGMA
            $foreignKeys = DB::select("PRAGMA foreign_key_list({$tableName})");
            
            foreach ($foreignKeys as $fk) {
                $referencedTable = $fk->table;
                $referencedModelName = Str::studly(Str::singular($referencedTable));
                $methodName = Str::camel(Str::singular($referencedTable));
                
                $relationships[] = [
                    'field' => $fk->from,
                    'references' => $fk->to,
                    'on' => $referencedTable,
                    'relation_type' => 'belongsTo',
                    'method_name' => $methodName,
                    'related_model' => $referencedModelName
                ];
                
                $this->info("  Found belongsTo relationship: {$modelName} belongs to {$referencedModelName} via {$fk->from}");
            }
        } catch (\Exception $e) {
            $this->warn("  Error detecting database relationships: " . $e->getMessage());
        }
        
        return $relationships;
    }

    /**
     * Detect relationships from migration files
     */
    protected function detectRelationshipsFromMigrations(string $modelName, string $tableName): array
    {
        $relationships = [];
        $migrationDir = database_path('migrations');
        
        if (!is_dir($migrationDir)) {
            $this->warn("  Migrations directory not found");
            return $relationships;
        }
        
        $files = scandir($migrationDir);
        $migrationFile = null;
        
        // Find migration file for this table
        foreach ($files as $file) {
            if (strpos($file, 'create_' . $tableName . '_table') !== false) {
                $migrationFile = $file;
                $this->info("  Found migration file: {$file}");
                break;
            }
        }
        
        if ($migrationFile) {
            // Parse this model's own migration for belongsTo and morphTo relationships
            $content = file_get_contents($migrationDir . '/' . $migrationFile);
            
            // Search for foreignId columns
            preg_match_all('/\$table->foreignId\([\'"]([^\'"]+)[\'"]\)(?:->constrained\((?:[\'"]([^\'"]+)[\'"]\))?)/', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $foreignKeyColumn = $match[1];
                $referencedTable = isset($match[2]) ? $match[2] : null;
                
                // If no explicit table specified, guess from column name
                if (!$referencedTable) {
                    $referencedTable = Str::plural(str_replace('_id', '', $foreignKeyColumn));
                }
                
                $referencedModelName = Str::studly(Str::singular($referencedTable));
                $methodName = Str::camel(Str::singular($referencedTable));
                
                $relationships[] = [
                    'field' => $foreignKeyColumn,
                    'references' => 'id',
                    'on' => $referencedTable,
                    'relation_type' => 'belongsTo',
                    'method_name' => $methodName,
                    'related_model' => $referencedModelName
                ];
                
                $this->info("  Found belongsTo relationship: {$modelName} belongs to {$referencedModelName} via {$foreignKeyColumn}");
            }
            
            // Search for morphs
            preg_match_all('/\$table->morphs\([\'"]([^\'"]+)[\'"]\)/', $content, $morphMatches, PREG_SET_ORDER);
            
            foreach ($morphMatches as $match) {
                $morphName = $match[1];
                
                $relationships[] = [
                    'morph_name' => $morphName,
                    'relation_type' => 'morphTo',
                    'method_name' => $morphName
                ];
                
                $this->info("  Found morphTo relationship: {$modelName} morphTo via {$morphName}");
            }
        }
        
        // Now look for reverse relationships in other migration files
        $this->info("  Looking for reverse relationships in other migrations...");
        $singularTableName = Str::singular($tableName);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === $migrationFile) {
                continue;
            }
            
            if (!str_contains($file, '_create_') || !str_contains($file, '_table.php')) {
                continue;
            }
            
            $content = file_get_contents($migrationDir . '/' . $file);
            
            // Extract the table name this migration creates
            preg_match('/create_([a-z0-9_]+)_table/', $file, $tableMatches);
            if (empty($tableMatches)) {
                continue;
            }
            
            $otherTableName = $tableMatches[1];
            $otherModelName = Str::studly(Str::singular($otherTableName));
            
            // Look for foreign keys that reference our table
            // Pattern 1: foreignId with explicit constrained table
            preg_match_all('/\$table->foreignId\([\'"]([^\'"]+)[\'"]\)->constrained\([\'"]' . $tableName . '[\'"]\)/', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $foreignKeyColumn = $match[1];
                $methodName = Str::camel(Str::plural($otherTableName));
                
                $relationships[] = [
                    'field' => 'id',
                    'references' => $foreignKeyColumn,
                    'on' => $otherTableName,
                    'relation_type' => 'hasMany',
                    'method_name' => $methodName,
                    'related_model' => $otherModelName
                ];
                
                $this->info("  Found hasMany relationship: {$modelName} hasMany {$otherModelName} via {$foreignKeyColumn}");
            }
            
            // Pattern 2: foreignId without explicit table (Laravel convention)
            $expectedForeignKey = $singularTableName . '_id';
            if (strpos($content, "foreignId('{$expectedForeignKey}')") !== false) {
                $methodName = Str::camel(Str::plural($otherTableName));
                
                $relationships[] = [
                    'field' => 'id',
                    'references' => $expectedForeignKey,
                    'on' => $otherTableName,
                    'relation_type' => 'hasMany',
                    'method_name' => $methodName,
                    'related_model' => $otherModelName
                ];
                
                $this->info("  Found hasMany relationship: {$modelName} hasMany {$otherModelName} via {$expectedForeignKey}");
            }
            
            // Pattern 3: Check for morphs that could reference this model polymorphically
            preg_match_all('/\$table->morphs\([\'"]([^\'"]+)[\'"]\)/', $content, $morphMatches, PREG_SET_ORDER);
            
            foreach ($morphMatches as $match) {
                $morphName = $match[1];
                $methodName = Str::camel(Str::plural($otherTableName));
                
                $relationships[] = [
                    'morph_name' => $morphName,
                    'relation_type' => 'morphMany',
                    'method_name' => $methodName,
                    'related_model' => $otherModelName,
                    'suggested' => true // Mark as suggested since we can't be 100% sure
                ];
                
                $this->info("  Found potential morphMany relationship: {$modelName} morphMany {$otherModelName} via {$morphName}");
            }
        }
        
        return $relationships;
    }

    /**
     * Get table name from model name
     */
    protected function getTableName(string $modelName): string
    {
        $modelClass = "App\\Models\\{$modelName}";
        try {
            if (class_exists($modelClass)) {
                $model = new $modelClass;
                if (property_exists($model, 'table') && $model->table) {
                    return $model->table;
                }
            }
        } catch (\Exception $e) {
            // Use convention
        }
        
        return Str::snake(Str::plural($modelName));
    }

    /**
     * Update model with relationships
     */
    protected function updateModelWithRelationships(string $modelName, array $relationships): void
    {
        $modelPath = app_path("Models/{$modelName}.php");
        
        if (!file_exists($modelPath)) {
            $this->error("  Model file not found: {$modelPath}");
            return;
        }
        
        $content = file_get_contents($modelPath);
        $relationsCode = "";
        $addedCount = 0;
        $suggestedCount = 0;
        
        foreach ($relationships as $relationship) {
            $relationType = $relationship['relation_type'] ?? 'belongsTo';
            $methodName = $relationship['method_name'] ?? null;
            $isSuggested = $relationship['suggested'] ?? false;
            
            // Skip if method already exists
            if (strpos($content, "public function {$methodName}()") !== false) {
                $this->line("    - Skipped '{$methodName}()' (already exists)");
                continue;
            }
            
            if ($relationType === 'belongsTo') {
                $relatedModel = $relationship['related_model'];
                
                $relationMethod = "\n    /**\n     * Get the {$methodName} that owns this {$modelName}.\n     */\n    public function {$methodName}()\n    {\n        return \$this->belongsTo({$relatedModel}::class, '{$relationship['field']}');\n    }\n";
                
            } elseif ($relationType === 'hasMany') {
                $relatedModel = $relationship['related_model'];
                
                $relationMethod = "\n    /**\n     * Get the {$methodName} for this {$modelName}.\n     */\n    public function {$methodName}()\n    {\n        return \$this->hasMany({$relatedModel}::class, '{$relationship['references']}');\n    }\n";
                
            } elseif ($relationType === 'morphTo') {
                $relationMethod = "\n    /**\n     * Get the parent {$methodName} model (polymorphic).\n     */\n    public function {$methodName}()\n    {\n        return \$this->morphTo();\n    }\n";
                
            } elseif ($relationType === 'morphMany') {
                if ($isSuggested) {
                    // For suggested polymorphic relationships, ask user or show as suggestion
                    $this->line("    ? Suggested morphMany: {$methodName}() - {$modelName} morphMany {$relationship['related_model']} (polymorphic)");
                    $suggestedCount++;
                    continue;
                }
                
                $relatedModel = $relationship['related_model'];
                $morphName = $relationship['morph_name'];
                
                $relationMethod = "\n    /**\n     * Get the {$methodName} for this {$modelName} (polymorphic).\n     */\n    public function {$methodName}()\n    {\n        return \$this->morphMany({$relatedModel}::class, '{$morphName}');\n    }\n";
                
            } else {
                continue;
            }
            
            $relationsCode .= $relationMethod;
            $addedCount++;
            $this->line("    ✓ Added '{$methodName}()' relationship");
        }
        
        // Add relationships before closing bracket
        if (!empty($relationsCode)) {
            $content = preg_replace('/}(\s*)$/', $relationsCode . "}$1", $content);
            file_put_contents($modelPath, $content);
            $this->info("  ✅ Added {$addedCount} relationships to {$modelName} model");
        } else {
            $this->info("  No new relationships to add");
        }
        
        if ($suggestedCount > 0) {
            $this->line("  💡 {$suggestedCount} polymorphic relationships suggested (review manually)");
        }
    }

    /**
     * Add a relation to a model.
     */
    private function addRelationToModel(array $relation): bool
    {
        try {
            $this->codeWriter->addRelation(
                $relation['model_path'],
                $relation['method_name'],
                $relation['type'],
                $relation['related_model_class'] ?? ''
            );
            
            $modelClass = class_basename($relation['model_class']);
            $this->line("    ✓ Added '{$relation['method_name']}()' to {$modelClass}");
            return true;
            
        } catch (\AnasNashat\EasyDev\Exceptions\RelationAlreadyExistsException $e) {
            $modelClass = class_basename($relation['model_class']);
            $this->line("    - Skipped '{$relation['method_name']}()' in {$modelClass} (already exists)");
            return false;
        } catch (\Exception $e) {
            $modelClass = class_basename($relation['model_class']);
            $this->error("    ✗ Failed to add '{$relation['method_name']}()' to {$modelClass}: {$e->getMessage()}");
            return false;
        }
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::OPTIONAL, 'The name of the model to sync (optional if using --all).'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['all', null, InputOption::VALUE_NONE, 'Sync relationships for all models.'],
            ['morph-targets', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of models for polymorphic relations.'],
        ];
    }
}
