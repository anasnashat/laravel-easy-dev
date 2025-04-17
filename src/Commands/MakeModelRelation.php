<?php

namespace PackgeTest\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModelRelation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model-relation 
                            {model : The name of the model}
                            {--relations= : Define relationships (format: "model:type,model:type")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add relationships to an existing model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = Str::studly($this->argument('model'));
        
        // Check if model exists
        $modelPath = app_path("Models/{$modelName}.php");
        if (!file_exists($modelPath)) {
            $this->error("Model {$modelName} not found at {$modelPath}");
            return 1;
        }

        // Parse relationships
        $relationships = $this->parseRelationshipDefinitions($modelName);
        
        if (empty($relationships)) {
            $this->error("No valid relationships specified. Use --relations option with format 'model:type,model:type'");
            return 1;
        }

        $this->updateModelWithRelationships($modelName, $relationships);
        $this->info("✓ Relationships successfully added to {$modelName} model");
        
        return 0;
    }

    /**
     * Parse user-provided relationship definitions
     *
     * @param string $currentModel
     * @return array
     */
    protected function parseRelationshipDefinitions($currentModel)
    {
        $relationships = [];
        $relationsOption = $this->option('relations');

        if (empty($relationsOption)) {
            return $relationships;
        }

        // Format: "model:type,model:type" 
        // e.g. "user:belongsTo,comment:hasMany,category:belongsTo"
        $relationParts = explode(',', $relationsOption);

        foreach ($relationParts as $relation) {
            $parts = explode(':', $relation);
            
            if (count($parts) != 2) {
                $this->warn("⚠️ Invalid relationship format: $relation. Expected format: model:type");
                continue;
            }

            $relatedModel = trim($parts[0]);
            $relationType = trim($parts[1]);

            // Validate relation type
            if (!in_array($relationType, ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany'])) {
                $this->warn("⚠️ Invalid relationship type: $relationType. Supported types: belongsTo, hasMany, hasOne, belongsToMany");
                continue;
            }

            // Format relationship data
            $tableName = Str::snake(Str::plural($relatedModel));
            
            if ($relationType === 'belongsTo') {
                $relationships[] = [
                    'field' => Str::snake(Str::singular($relatedModel)) . '_id',
                    'references' => 'id',
                    'on' => $tableName,
                    'relation_type' => 'belongsTo'
                ];
            } else if ($relationType === 'hasMany' || $relationType === 'hasOne') {
                $relationships[] = [
                    'field' => 'id',
                    'references' => Str::snake(Str::singular($currentModel)) . '_id',
                    'on' => $tableName,
                    'relation_type' => $relationType
                ];
            } else if ($relationType === 'belongsToMany') {
                $pivotTable = $this->guessPivotTableName($currentModel, $relatedModel);
                
                $relationships[] = [
                    'field' => 'id',
                    'references' => 'id',
                    'on' => $tableName,
                    'relation_type' => 'belongsToMany',
                    'pivot_table' => $pivotTable
                ];
            }
        }

        return $relationships;
    }

    /**
     * Guess the name of a pivot table between two models
     *
     * @param string $model1
     * @param string $model2
     * @return string
     */
    protected function guessPivotTableName($model1, $model2)
    {
        $models = [
            Str::snake(Str::singular($model1)),
            Str::snake(Str::singular($model2))
        ];
        
        // Sort alphabetically to ensure consistent naming
        sort($models);
        
        return implode('_', $models);
    }

    /**
     * Update model with relationships
     */
    protected function updateModelWithRelationships($name, $relationships)
    {
        $modelPath = app_path("Models/{$name}.php");
        $content = file_get_contents($modelPath);
        
        $relationsCode = "";
        $addedRelations = [];
        
        foreach ($relationships as $relationship) {
            $relationType = $relationship['relation_type'] ?? 'belongsTo';
            
            if ($relationType === 'belongsTo') {
                // For belongsTo: user() belongs to users table through user_id
                $methodName = Str::camel(Str::singular($relationship['on']));
                $relatedModel = Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} that owns this {$name}.
     */
    public function {$methodName}()
    {
        return \$this->belongsTo({$relatedModel}::class, '{$relationship['field']}');
    }
PHP;
            } else if ($relationType === 'hasMany') {
                // For hasMany: users() has many user through user_id
                $methodName = Str::camel(Str::plural($relationship['on']));
                $relatedModel = Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} for this {$name}.
     */
    public function {$methodName}()
    {
        return \$this->hasMany({$relatedModel}::class, '{$relationship['references']}', '{$relationship['field']}');
    }
PHP;
            } else if ($relationType === 'hasOne') {
                // For hasOne: user() has one user through user_id
                $methodName = Str::camel(Str::singular($relationship['on']));
                $relatedModel = Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} for this {$name}.
     */
    public function {$methodName}()
    {
        return \$this->hasOne({$relatedModel}::class, '{$relationship['references']}', '{$relationship['field']}');
    }
PHP;
            } else if ($relationType === 'belongsToMany') {
                // For belongsToMany: users() belongs to many users through pivot table
                $methodName = Str::camel(Str::plural($relationship['on']));
                $relatedModel = Str::studly(Str::singular($relationship['on']));
                $pivotTable = $relationship['pivot_table'] ?? '';
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * The {$methodName} that belong to this {$name}.
     */
    public function {$methodName}()
    {
        return \$this->belongsToMany({$relatedModel}::class, '{$pivotTable}');
    }
PHP;
            }
            
            // Only add relationship if it doesn't already exist
            if (strpos($content, "public function $methodName()") === false) {
                $relationsCode .= $relationMethod;
            } else {
                $this->warn("Relationship method '{$methodName}' already exists in {$name} model");
            }
        }
        
        // Add relationships to model before the closing bracket
        if (!empty($relationsCode)) {
            $content = preg_replace('/}(\s*)$/', $relationsCode . "\n}$1", $content);
            file_put_contents($modelPath, $content);
            $this->info("✅ Added " . count($addedRelations) . " relationships to model");
        }
    }
}