<?php

namespace PackgeTest\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class SyncModelRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model:sync-relations {model? : The name of the model to synchronize relations for}
                            {--all : Sync relations for all models}
                            {--morph-targets= : Comma-separated list of models to apply polymorphic relationships to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans the database for relationships and adds them to the specified model';

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
     * Synchronize relations for a single model
     *
     * @param string $modelName
     * @param bool $collectBidirectional Whether to return bidirectional relationships for later processing
     * @return array Array of bidirectional relationships to be added to related models
     */
    protected function syncModelRelations($modelName, $collectBidirectional = false)
    {
        // Check if model exists
        $modelPath = app_path("Models/{$modelName}.php");
        if (!file_exists($modelPath)) {
            $this->error("Model {$modelName} not found at {$modelPath}");
            return [];
        }

        $this->info("🔍 Analyzing database for relationships for model: {$modelName}");

        // Get table name from model name
        $tableName = $this->getTableName($modelName);
        
        // Try to find relationships in the database first
        $relationships = [];
        $bidirectionalRelationships = [];
        
        if (Schema::hasTable($tableName)) {
            $relationships = $this->detectDatabaseRelationships($modelName);
        } else {
            $this->warn("Table '{$tableName}' not found in database. Looking in migration files...");
        }
        
        // If no relationships found in database, try migration files
        if (empty($relationships)) {
            $relationshipsFromMigrations = $this->detectRelationshipsFromMigrations($modelName);
            $relationships = array_merge($relationships, $relationshipsFromMigrations);
        }
        
        if (empty($relationships)) {
            $this->info("No relationships found for {$modelName}");
            return [];
        }

        // Process relationships and collect bidirectional info
        if ($collectBidirectional) {
            foreach ($relationships as $key => $relationship) {
                $reverseRelationship = $this->createReverseRelationship($modelName, $relationship);
                if ($reverseRelationship) {
                    $bidirectionalRelationships[] = [
                        'related_model' => $relationship['related_model'],
                        'reverse_relationship' => $reverseRelationship
                    ];
                }
            }
        }

        // Update the model with the relationships
        $this->updateModelWithRelationships($modelName, $relationships);
        
        return $bidirectionalRelationships;
    }
    
    /**
     * Create a reverse relationship definition for bidirectional relationships
     *
     * @param string $sourceModelName
     * @param array $relationship
     * @return array|null The reverse relationship definition or null if not applicable
     */
    protected function createReverseRelationship($sourceModelName, $relationship)
    {
        $relationType = $relationship['relation_type'] ?? '';
        $relatedModel = $relationship['related_model'] ?? '';
        
        if (empty($relationType) || empty($relatedModel)) {
            return null;
        }
        
        switch ($relationType) {
            case 'belongsTo':
                // If model A belongs to model B, then model B hasMany/hasOne model A
                // Determine if it should be hasOne or hasMany
                $reverseType = 'hasMany'; // Default to hasMany
                $reverseMethodName = Str::camel(Str::plural($sourceModelName)); // posts, comments, etc
                
                return [
                    'field' => 'id', // Primary key
                    'references' => $relationship['field'], // Foreign key in the source model
                    'on' => Str::snake(Str::plural($sourceModelName)), // Table name
                    'relation_type' => $reverseType,
                    'method_name' => $reverseMethodName,
                    'related_model' => $sourceModelName
                ];
                
            case 'hasMany':
                // If model A hasMany model B, then model B belongsTo model A
                $reverseMethodName = Str::camel(Str::singular($sourceModelName)); // post, comment, etc
                
                return [
                    'field' => $relationship['references'], // Foreign key in the target model
                    'references' => $relationship['field'], // Primary key in the source model
                    'on' => Str::snake(Str::plural($sourceModelName)), // Table name
                    'relation_type' => 'belongsTo',
                    'method_name' => $reverseMethodName,
                    'related_model' => $sourceModelName
                ];
                
            case 'hasOne':
                // If model A hasOne model B, then model B belongsTo model A
                $reverseMethodName = Str::camel(Str::singular($sourceModelName)); // post, comment, etc
                
                return [
                    'field' => $relationship['references'], // Foreign key in the target model
                    'references' => $relationship['field'], // Primary key in the source model
                    'on' => Str::snake(Str::plural($sourceModelName)), // Table name
                    'relation_type' => 'belongsTo',
                    'method_name' => $reverseMethodName,
                    'related_model' => $sourceModelName
                ];
                
            case 'belongsToMany':
                // If model A belongsToMany model B, then model B belongsToMany model A
                $reverseMethodName = Str::camel(Str::plural($sourceModelName)); // posts, comments, etc
                
                return [
                    'pivot_table' => $relationship['pivot_table'], // Same pivot table
                    'related_table' => Str::snake(Str::plural($sourceModelName)),
                    'relation_type' => 'belongsToMany',
                    'method_name' => $reverseMethodName,
                    'related_model' => $sourceModelName
                ];
        }
        
        return null;
    }

    /**
     * Get the table name from a model name
     *
     * @param string $modelName
     * @return string
     */
    protected function getTableName($modelName)
    {
        // Try to get table name from model instance
        $modelClass = "App\\Models\\{$modelName}";
        try {
            if (class_exists($modelClass)) {
                $model = new $modelClass;
                if (property_exists($model, 'table') && $model->table) {
                    return $model->table;
                }
            }
        } catch (\Exception $e) {
            // Silently fail and use convention-based name
        }

        // Use Laravel convention to determine table name
        return Str::snake(Str::plural($modelName));
    }

    /**
     * Detect relationships from the database schema
     *
     * @param string $modelName
     * @return array
     */
    protected function detectDatabaseRelationships($modelName)
    {
        $relationships = [];
        $tableName = $this->getTableName($modelName);
        
        // 1. Detect belongsTo relationships (foreign keys in this model's table)
        try {
            // For MySQL
            if (DB::connection()->getDriverName() === 'mysql') {
                $foreignKeys = DB::select("
                    SELECT 
                        COLUMN_NAME as column_name,
                        REFERENCED_TABLE_NAME as referenced_table,
                        REFERENCED_COLUMN_NAME as referenced_column
                    FROM 
                        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE 
                        TABLE_SCHEMA = ?
                        AND TABLE_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [env('DB_DATABASE'), $tableName]);
                
                foreach ($foreignKeys as $fk) {
                    $referencedModelName = Str::studly(Str::singular($fk->referenced_table));
                    
                    $relationships[] = [
                        'field' => $fk->column_name,
                        'references' => $fk->referenced_column,
                        'on' => $fk->referenced_table,
                        'relation_type' => 'belongsTo',
                        'related_model' => $referencedModelName
                    ];
                    
                    $this->info("Found belongsTo relationship: {$modelName} belongs to {$referencedModelName} via {$fk->column_name}");
                }
            }
            // For PostgreSQL
            else if (DB::connection()->getDriverName() === 'pgsql') {
                $foreignKeys = DB::select("
                    SELECT
                        kcu.column_name,
                        ccu.table_name AS referenced_table,
                        ccu.column_name AS referenced_column
                    FROM
                        information_schema.table_constraints AS tc
                        JOIN information_schema.key_column_usage AS kcu
                            ON tc.constraint_name = kcu.constraint_name
                            AND tc.table_schema = kcu.table_schema
                        JOIN information_schema.constraint_column_usage AS ccu
                            ON ccu.constraint_name = tc.constraint_name
                            AND ccu.table_schema = tc.table_schema
                    WHERE tc.constraint_type = 'FOREIGN KEY' 
                        AND tc.table_name = ?
                ", [$tableName]);
                
                foreach ($foreignKeys as $fk) {
                    $referencedModelName = Str::studly(Str::singular($fk->referenced_table));
                    
                    $relationships[] = [
                        'field' => $fk->column_name,
                        'references' => $fk->referenced_column,
                        'on' => $fk->referenced_table,
                        'relation_type' => 'belongsTo',
                        'related_model' => $referencedModelName
                    ];
                    
                    $this->info("Found belongsTo relationship: {$modelName} belongs to {$referencedModelName} via {$fk->column_name}");
                }
            }
            // For SQLite
            else if (DB::connection()->getDriverName() === 'sqlite') {
                try {
                    $tables = DB::select("PRAGMA foreign_key_list({$tableName})");
                    
                    foreach ($tables as $fk) {
                        $referencedTable = $fk->table;
                        $referencedModelName = Str::studly(Str::singular($referencedTable));
                        
                        $relationships[] = [
                            'field' => $fk->from,
                            'references' => $fk->to,
                            'on' => $referencedTable,
                            'relation_type' => 'belongsTo',
                            'related_model' => $referencedModelName
                        ];
                        
                        $this->info("Found belongsTo relationship: {$modelName} belongs to {$referencedModelName} via {$fk->from}");
                    }
                } catch (\Exception $e) {
                    $this->warn("Could not read SQLite foreign keys: " . $e->getMessage());
                }
            }
            
            // If no database relationships were found, try to parse migration files
            if (empty($relationships)) {
                $this->info("No database relationships found for {$modelName}. Trying to parse migration files...");
                $relationshipsFromMigrations = $this->detectRelationshipsFromMigrations($modelName);
                $relationships = array_merge($relationships, $relationshipsFromMigrations);
            }
        } catch (\Exception $e) {
            $this->warn("Error detecting belongsTo relationships: " . $e->getMessage());
            // Try to parse migration files as fallback
            $relationshipsFromMigrations = $this->detectRelationshipsFromMigrations($modelName);
            $relationships = array_merge($relationships, $relationshipsFromMigrations);
        }
        
        // 2. Detect hasMany/hasOne relationships (other tables that reference this model)
        try {
            // Get all tables
            $tables = $this->getAllDatabaseTables();
            
            foreach ($tables as $table) {
                // Skip if it's the same table
                if ($table === $tableName) continue;
                
                // Check for foreign keys that reference this model's table
                if (DB::connection()->getDriverName() === 'mysql') {
                    $foreignKeys = DB::select("
                        SELECT 
                            COLUMN_NAME as column_name,
                            TABLE_NAME as table_name,
                            REFERENCED_COLUMN_NAME as referenced_column
                        FROM 
                            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                        WHERE 
                            TABLE_SCHEMA = ?
                            AND TABLE_NAME = ?
                            AND REFERENCED_TABLE_NAME = ?
                    ", [env('DB_DATABASE'), $table, $tableName]);
                    
                    foreach ($foreignKeys as $fk) {
                        // Determine if hasOne or hasMany
                        $relationType = $this->guessRelationType($tableName, $table, $fk->column_name);
                        $relationName = $relationType === 'hasOne' 
                            ? Str::camel(Str::singular($table))
                            : Str::camel(Str::plural($table));
                        
                        $relatedModelName = Str::studly(Str::singular($table));
                        
                        $relationships[] = [
                            'field' => $fk->referenced_column,
                            'references' => $fk->column_name,
                            'on' => $table,
                            'relation_type' => $relationType,
                            'method_name' => $relationName,
                            'related_model' => $relatedModelName
                        ];
                        
                        $this->info("Found {$relationType} relationship: {$modelName} {$relationType} {$relatedModelName} via {$fk->column_name}");
                    }
                }
                else if (DB::connection()->getDriverName() === 'pgsql') {
                    $foreignKeys = DB::select("
                        SELECT
                            kcu.column_name,
                            tc.table_name AS table_name,
                            ccu.column_name AS referenced_column
                        FROM
                            information_schema.table_constraints AS tc
                            JOIN information_schema.key_column_usage AS kcu
                                ON tc.constraint_name = kcu.constraint_name
                                AND tc.table_schema = kcu.table_schema
                            JOIN information_schema.constraint_column_usage AS ccu
                                ON ccu.constraint_name = tc.constraint_name
                                AND ccu.table_schema = tc.table_schema
                        WHERE tc.constraint_type = 'FOREIGN KEY' 
                            AND tc.table_name = ?
                            AND ccu.table_name = ?
                    ", [$table, $tableName]);
                    
                    foreach ($foreignKeys as $fk) {
                        $relationType = $this->guessRelationType($tableName, $table, $fk->column_name);
                        $relationName = $relationType === 'hasOne' 
                            ? Str::camel(Str::singular($table))
                            : Str::camel(Str::plural($table));
                        
                        $relatedModelName = Str::studly(Str::singular($table));
                        
                        $relationships[] = [
                            'field' => $fk->referenced_column,
                            'references' => $fk->column_name,
                            'on' => $table,
                            'relation_type' => $relationType,
                            'method_name' => $relationName,
                            'related_model' => $relatedModelName
                        ];
                        
                        $this->info("Found {$relationType} relationship: {$modelName} {$relationType} {$relatedModelName} via {$fk->column_name}");
                    }
                }
                else if (DB::connection()->getDriverName() === 'sqlite') {
                    $foreignKeys = DB::select("PRAGMA foreign_key_list({$table})");
                    
                    foreach ($foreignKeys as $fk) {
                        if ($fk->table === $tableName) {
                            $relationType = $this->guessRelationType($tableName, $table, $fk->from);
                            $relationName = $relationType === 'hasOne' 
                                ? Str::camel(Str::singular($table))
                                : Str::camel(Str::plural($table));
                            
                            $relatedModelName = Str::studly(Str::singular($table));
                            
                            $relationships[] = [
                                'field' => $fk->to,
                                'references' => $fk->from,
                                'on' => $table,
                                'relation_type' => $relationType,
                                'method_name' => $relationName,
                                'related_model' => $relatedModelName
                            ];
                            
                            $this->info("Found {$relationType} relationship: {$modelName} {$relationType} {$relatedModelName} via {$fk->from}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("Error detecting hasMany/hasOne relationships: " . $e->getMessage());
        }
        
        // 3. Detect many-to-many relationships
        try {
            // Look for pivot tables (tables that have foreign keys to two different tables)
            $tables = $this->getAllDatabaseTables();
            $singularTableName = Str::singular($tableName);
            
            foreach ($tables as $table) {
                // Skip if it's not a potential pivot table
                if ($table === $tableName) continue;
                
                // Check if the table name suggests a pivot relationship (e.g., "post_tag")
                $tableParts = explode('_', $table);
                if (count($tableParts) == 2) {
                    // If one part matches our model's table (singular)
                    if ($tableParts[0] === $singularTableName || $tableParts[1] === $singularTableName) {
                        // The other part is the related model's table
                        $otherTable = $tableParts[0] === $singularTableName ? $tableParts[1] : $tableParts[0];
                        $otherTablePlural = Str::plural($otherTable);
                        $relatedModelName = Str::studly(Str::singular($otherTable));
                        
                        // Verify that table has foreign keys to both tables
                        $hasBothForeignKeys = $this->checkPivotTableForeignKeys($table, $tableName, $otherTablePlural);
                        
                        if ($hasBothForeignKeys) {
                            $relationships[] = [
                                'pivot_table' => $table,
                                'related_table' => $otherTablePlural,
                                'relation_type' => 'belongsToMany',
                                'method_name' => Str::camel(Str::plural($otherTable)),
                                'related_model' => $relatedModelName
                            ];
                            
                            $this->info("Found belongsToMany relationship: {$modelName} belongsToMany {$relatedModelName} via {$table}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("Error detecting many-to-many relationships: " . $e->getMessage());
        }
        
        return $relationships;
    }
    
    /**
     * Detect relationships by parsing migration files
     * 
     * @param string $modelName
     * @return array
     */
    protected function detectRelationshipsFromMigrations($modelName)
    {
        $relationships = [];
        $migrationDir = database_path('migrations');
        $tableName = $this->getTableName($modelName);
        $files = scandir($migrationDir);
        
        // Find the migration file for this model
        $migrationFile = null;
        foreach ($files as $file) {
            if (strpos($file, 'create_' . $tableName . '_table') !== false) {
                $migrationFile = $file;
                break;
            }
        }
        
        if (!$migrationFile) {
            $this->warn("Could not find migration file for {$tableName} table.");
            return $relationships;
        }
        
        $content = file_get_contents($migrationDir . '/' . $migrationFile);
        
        // Search for polymorphic relationships (morphs method)
        preg_match_all('/\$table->morphs\([\'"]([^\'"]+)[\'"]\)/', $content, $morphMatches, PREG_SET_ORDER);
        
        foreach ($morphMatches as $match) {
            $morphName = $match[1];
            
            // Add morphTo relationship for the polymorphic model
            $relationships[] = [
                'morph_name' => $morphName,
                'relation_type' => 'morphTo',
                'method_name' => $morphName
            ];
            
            $this->info("Found polymorphic relationship: {$modelName} morphTo via {$morphName}");
            
            // Find models that might have polymorphic relationships to this model
            $this->detectPolymorphicRelatedModels($modelName, $morphName, $relationships);
        }
        
        // Search for foreignId columns
        preg_match_all('/\$table->foreignId\([\'"]([^\'"]+)[\'"]\)(?:->constrained\((?:[\'"]([^\'"]+)[\'"]\))?)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $foreignKeyColumn = $match[1];
            $referencedTable = isset($match[2]) ? $match[2] : null;
            
            // If no explicit table is specified, guess it from the column name (Laravel convention)
            if (!$referencedTable) {
                // Remove _id suffix to get the related table name
                $referencedTable = Str::plural(str_replace('_id', '', $foreignKeyColumn));
            }
            
            $referencedModelName = Str::studly(Str::singular($referencedTable));
            
            $relationships[] = [
                'field' => $foreignKeyColumn,
                'references' => 'id',
                'on' => $referencedTable,
                'relation_type' => 'belongsTo',
                'related_model' => $referencedModelName
            ];
            
            $this->info("Found foreignId relationship in migration: {$modelName} belongs to {$referencedModelName} via {$foreignKeyColumn}");
        }
        
        // Search for foreign method calls
        preg_match_all('/\$table->foreign\([\'"]([^\'"]+)[\'"]\)->references\([\'"]([^\'"]+)[\'"]\)->on\([\'"]([^\'"]+)[\'"]\)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $foreignKeyColumn = $match[1];
            $referencedColumn = $match[2];
            $referencedTable = $match[3];
            
            $referencedModelName = Str::studly(Str::singular($referencedTable));
            
            $relationships[] = [
                'field' => $foreignKeyColumn,
                'references' => $referencedColumn,
                'on' => $referencedTable,
                'relation_type' => 'belongsTo',
                'related_model' => $referencedModelName
            ];
            
            $this->info("Found foreign key relationship in migration: {$modelName} belongs to {$referencedModelName} via {$foreignKeyColumn}");
        }
        
        // Also try to find reverse relationships in other migration files
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === $migrationFile) {
                continue;
            }
            
            $content = file_get_contents($migrationDir . '/' . $file);
            
            // Look for references to our table
            if (strpos($content, "'".$tableName."'") !== false || strpos($content, '"'.$tableName.'"') !== false) {
                // Extract the table name this migration is creating
                preg_match('/create_([a-z0-9_]+)_table/', $file, $tableMatches);
                if (empty($tableMatches)) {
                    continue;
                }
                
                $otherTableName = $tableMatches[1];
                $otherModelName = Str::studly(Str::singular($otherTableName));
                
                // Search for foreign keys referencing our table
                preg_match_all('/\$table->foreignId\([\'"]([^\'"]+)[\'"]\)->constrained\([\'"]'.$tableName.'[\'"]\)/', $content, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $foreignKeyColumn = $match[1];
                    
                    // This represents a hasMany relationship from our model to this model
                    $relationships[] = [
                        'field' => 'id',
                        'references' => $foreignKeyColumn,
                        'on' => $otherTableName,
                        'relation_type' => 'hasMany',
                        'method_name' => Str::camel(Str::plural($otherTableName)),
                        'related_model' => $otherModelName
                    ];
                    
                    $this->info("Found reverse relationship in migration: {$modelName} hasMany {$otherModelName} via {$foreignKeyColumn}");
                }
                
                // Look for more complex foreign references
                preg_match_all('/\$table->foreign\([\'"]([^\'"]+)[\'"]\)->references\([\'"]([^\'"]+)[\'"]\)->on\([\'"]'.$tableName.'[\'"]\)/', $content, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $foreignKeyColumn = $match[1];
                    $referencedColumn = $match[2];
                    
                    $relationships[] = [
                        'field' => $referencedColumn,
                        'references' => $foreignKeyColumn,
                        'on' => $otherTableName,
                        'relation_type' => 'hasMany',
                        'method_name' => Str::camel(Str::plural($otherTableName)),
                        'related_model' => $otherModelName
                    ];
                    
                    $this->info("Found reverse foreign key relationship in migration: {$modelName} hasMany {$otherModelName} via {$foreignKeyColumn}");
                }
            }
        }
        
        return $relationships;
    }
    
    /**
     * Find models that might have polymorphic relationships with the current model
     * 
     * @param string $modelName
     * @param string $morphName
     * @param array &$relationships
     * @return void
     */
    protected function detectPolymorphicRelatedModels($modelName, $morphName, &$relationships)
    {
        // Get morph target models from option (if specified)
        $morphTargets = $this->option('morph-targets');
        $targetModels = [];
        
        if (!empty($morphTargets)) {
            // Parse the comma-separated list of models
            $targetModels = array_map('trim', explode(',', $morphTargets));
            $this->info("Using specified morph targets: " . implode(', ', $targetModels));
        } else {
            // If no targets specified, use all models
            $modelsPath = app_path('Models');
            $files = scandir($modelsPath);
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $targetModels[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        
        // For each model, check if it might have a polymorphic relationship with our model
        foreach ($targetModels as $potentialModel) {
            if ($potentialModel === $modelName) continue;
            
            // Check if model file exists
            $modelPath = app_path("Models/{$potentialModel}.php");
            if (!file_exists($modelPath)) {
                $this->warn("Target model {$potentialModel} not found, skipping");
                continue;
            }
            
            // Common naming patterns for polymorphic relations
            $commonMorphNames = [
                strtolower($modelName) . 'able',  // imageable, commentable, etc.
                $morphName,                      // direct match with the morph name
            ];
            
            foreach ($commonMorphNames as $checkMorphName) {
                $this->info("Checking if {$potentialModel} has a polymorphic relation '{$checkMorphName}' to {$modelName}");
                
                // This is a potential polymorphic relationship
                // We'll suggest it in the model updates
                $relationships[] = [
                    'morph_name' => $morphName,
                    'relation_type' => 'suggested_morph',
                    'related_model' => $potentialModel,
                    'method_name' => Str::camel(Str::plural(strtolower($modelName))),
                    'suggested_code' => "public function " . Str::camel(Str::plural(strtolower($modelName))) . "()\n    {\n        return \$this->morphMany(" . $modelName . "::class, '" . $morphName . "');\n    }"
                ];
            }
        }
    }
    
    /**
     * Get all table names from the database
     *
     * @return array
     */
    protected function getAllDatabaseTables()
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $tables = DB::select('SHOW TABLES');
            $database = env('DB_DATABASE');
            $tableKey = "Tables_in_{$database}";
            
            return array_map(function ($table) use ($tableKey) {
                return $table->$tableKey;
            }, $tables);
        }
        else if (DB::connection()->getDriverName() === 'pgsql') {
            $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            
            return array_map(function ($table) {
                return $table->table_name;
            }, $tables);
        }
        else if (DB::connection()->getDriverName() === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            
            return array_map(function ($table) {
                return $table->name;
            }, $tables);
        }
        
        return [];
    }
    
    /**
     * Check if a potential pivot table has foreign keys to both related tables
     *
     * @param string $pivotTable
     * @param string $table1
     * @param string $table2
     * @return bool
     */
    protected function checkPivotTableForeignKeys($pivotTable, $table1, $table2)
    {
        try {
            if (DB::connection()->getDriverName() === 'mysql') {
                $foreignKeys = DB::select("
                    SELECT 
                        REFERENCED_TABLE_NAME as referenced_table
                    FROM 
                        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE 
                        TABLE_SCHEMA = ?
                        AND TABLE_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [env('DB_DATABASE'), $pivotTable]);
                
                $referencedTables = array_map(function ($fk) {
                    return $fk->referenced_table;
                }, $foreignKeys);
                
                return in_array($table1, $referencedTables) && in_array($table2, $referencedTables);
            }
            else if (DB::connection()->getDriverName() === 'sqlite') {
                $foreignKeys = DB::select("PRAGMA foreign_key_list({$pivotTable})");
                
                $referencedTables = array_map(function ($fk) {
                    return $fk->table;
                }, $foreignKeys);
                
                return in_array($table1, $referencedTables) && in_array($table2, $referencedTables);
            }
        } catch (\Exception $e) {
            return false;
        }
        
        return false;
    }
    
    /**
     * Guess relationship type (hasOne or hasMany) based on table structure
     *
     * @param string $mainTable
     * @param string $relatedTable
     * @param string $foreignKey
     * @return string
     */
    protected function guessRelationType($mainTable, $relatedTable, $foreignKey)
    {
        try {
            // Check if the foreign key has a unique constraint
            if (DB::connection()->getDriverName() === 'mysql') {
                $uniqueConstraint = DB::select("
                    SELECT COUNT(*) as count
                    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                        ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                    WHERE
                        tc.CONSTRAINT_TYPE IN ('UNIQUE', 'PRIMARY KEY')
                        AND tc.TABLE_SCHEMA = ?
                        AND tc.TABLE_NAME = ?
                        AND kcu.COLUMN_NAME = ?
                ", [env('DB_DATABASE'), $relatedTable, $foreignKey]);
                
                if ($uniqueConstraint[0]->count > 0) {
                    return 'hasOne';
                }
            }
        } catch (\Exception $e) {
            // If we can't determine, default to hasMany
        }
        
        // Default to hasMany as it's more common
        return 'hasMany';
    }
    
    /**
     * Update model with relationships
     *
     * @param string $modelName
     * @param array $relationships
     */
    protected function updateModelWithRelationships($modelName, $relationships)
    {
        $modelPath = app_path("Models/{$modelName}.php");
        $content = file_get_contents($modelPath);
        
        $relationsCode = "";
        $addedRelations = [];
        $suggestedMorphs = [];
        
        foreach ($relationships as $relationship) {
            $relationType = $relationship['relation_type'] ?? 'belongsTo';
            $methodName = $relationship['method_name'] ?? null;
            
            if ($relationType === 'belongsTo') {
                // For belongsTo: user() belongs to users table through user_id
                $methodName = $methodName ?? Str::camel(Str::singular($relationship['on']));
                $relatedModel = $relationship['related_model'] ?? Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} that owns this {$modelName}.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function {$methodName}()
    {
        return \$this->belongsTo({$relatedModel}::class, '{$relationship['field']}');
    }
PHP;
            } 
            else if ($relationType === 'hasMany') {
                // For hasMany: users() has many user through user_id
                $methodName = $methodName ?? Str::camel(Str::plural($relationship['on']));
                $relatedModel = $relationship['related_model'] ?? Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} for this {$modelName}.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function {$methodName}()
    {
        return \$this->hasMany({$relatedModel}::class, '{$relationship['references']}', '{$relationship['field']}');
    }
PHP;
            } 
            else if ($relationType === 'hasOne') {
                // For hasOne: profile() has one profile through user_id
                $methodName = $methodName ?? Str::camel(Str::singular($relationship['on']));
                $relatedModel = $relationship['related_model'] ?? Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} for this {$modelName}.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function {$methodName}()
    {
        return \$this->hasOne({$relatedModel}::class, '{$relationship['references']}', '{$relationship['field']}');
    }
PHP;
            } 
            else if ($relationType === 'belongsToMany') {
                // For belongsToMany: tags() belongs to many tags through post_tag pivot
                $methodName = $methodName ?? Str::camel(Str::plural($relationship['related_table']));
                $relatedModel = $relationship['related_model'] ?? Str::studly(Str::singular($relationship['related_table']));
                $pivotTable = $relationship['pivot_table'];
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * The {$methodName} that belong to this {$modelName}.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function {$methodName}()
    {
        return \$this->belongsToMany({$relatedModel}::class, '{$pivotTable}');
    }
PHP;
            }
            else if ($relationType === 'morphTo') {
                // For morphTo: imageable() returns the polymorphic parent
                $methodName = $methodName ?? $relationship['morph_name'];
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the parent {$methodName} model (polymorphic).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function {$methodName}()
    {
        return \$this->morphTo();
    }
PHP;
            }
            else if ($relationType === 'suggested_morph') {
                // These are suggestions for other models, not for this model
                // We'll collect them to display as suggestions
                $suggestedMorphs[] = [
                    'model' => $relationship['related_model'],
                    'code' => $relationship['suggested_code']
                ];
                continue;
            }
            
            // Only add relationship if it doesn't already exist
            if (strpos($content, "public function $methodName()") === false) {
                $relationsCode .= $relationMethod;
            } else {
                $this->warn("Relationship method '{$methodName}' already exists in {$modelName} model - skipping");
            }
        }
        
        // Add relationships to model before the closing bracket
        if (!empty($relationsCode)) {
            $content = preg_replace('/}(\s*)$/', $relationsCode . "\n}$1", $content);
            file_put_contents($modelPath, $content);
            $this->info("✅ Added " . count($addedRelations) . " relationships to {$modelName} model");
        } else {
            $this->info("No new relationships were added to {$modelName} model");
        }
        
        // Display suggestions for polymorphic relations that should be added to other models
        if (!empty($suggestedMorphs)) {
            $this->info("🔄 Processing polymorphic relationships for related models:");
            
            // Get morph targets from command option
            $morphTargetsOption = $this->option('morph-targets');
            $autoConfirm = !empty($morphTargetsOption); // If targets are specified, auto-confirm
            
            foreach ($suggestedMorphs as $suggestion) {
                $this->info("In {$suggestion['model']} model, you might want to add:");
                $this->line($suggestion['code']);
                
                // Ask if the user wants to add this relationship (or auto-confirm if targets are specified)
                if ($autoConfirm || $this->confirm("Do you want to add this relationship to the {$suggestion['model']} model?", true)) {
                    $relatedModelPath = app_path("Models/{$suggestion['model']}.php");
                    if (file_exists($relatedModelPath)) {
                        $relatedContent = file_get_contents($relatedModelPath);
                        
                        // Extract method name from the suggested code
                        preg_match('/public function ([a-zA-Z0-9_]+)\(\)/', $suggestion['code'], $methodMatches);
                        $methodName = $methodMatches[1] ?? null;
                        
                        // Only add if it doesn't exist
                        if ($methodName && strpos($relatedContent, "public function $methodName()") === false) {
                            $formattedMethod = "\n    " . trim($suggestion['code']);
                            $relatedContent = preg_replace('/}(\s*)$/', $formattedMethod . "\n}$1", $relatedContent);
                            file_put_contents($relatedModelPath, $relatedContent);
                            $this->info("✅ Added '{$methodName}' relationship to {$suggestion['model']} model");
                        } else {
                            $this->warn("Relationship method already exists in {$suggestion['model']} model - skipping");
                        }
                    }
                }
            }
        }
    }
}