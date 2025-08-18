<?php

namespace AnasNashat\EasyDev\Services;

use AnasNashat\EasyDev\Contracts\SchemaParser;
use AnasNashat\EasyDev\Exceptions\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class RelationDetector
{
    public function __construct(
        protected SchemaParser $schemaParser, 
        protected Filesystem $files,
        protected FileGenerator $generator
    ) {
    }

    /**
     * Discovers all potential relationships for a given model.
     * Returns a structured array with direct and inverse relations.
     */
    public function discoverRelations(string $modelName): array
    {
        $modelClass = $this->qualifyModel($modelName);
        $table = (new $modelClass)->getTable();

        // Get schema information
        $foreignKeys = $this->schemaParser->getForeignKeysForTable($table);
        $pivotTables = $this->schemaParser->findPivotTablesForTable($table);
        $polymorphicColumns = $this->schemaParser->getPolymorphicColumnsForTable($table);

        $directRelations = [];
        $inverseRelations = [];

        // 1. Process foreign keys to detect belongsTo relationships
        foreach ($foreignKeys as $fk) {
            $relatedTable = $fk->referenced_table_name;
            $relatedModel = $this->generator->getModelNameFromTable($relatedTable);
            $relatedModelClass = $this->qualifyModel($relatedModel);

            // Check if related model exists
            if (!class_exists($relatedModelClass)) {
                continue;
            }

            // Create belongsTo relation
            $relationName = Str::camel(Str::singular($relatedTable));
            $directRelations[] = [
                'model_class' => $modelClass,
                'model_path' => $this->getModelPath($modelName),
                'method_name' => $relationName,
                'type' => 'belongsTo',
                'related_model_class' => $relatedModelClass,
            ];

            // Create inverse hasMany relation
            $inverseRelationName = Str::camel(Str::plural($table));
            $inverseRelations[] = [
                'model_class' => $relatedModelClass,
                'model_path' => $this->getModelPath($relatedModel),
                'method_name' => $inverseRelationName,
                'type' => 'hasMany',
                'related_model_class' => $modelClass,
            ];
        }

        // 2. Process pivot tables to detect belongsToMany relationships
        foreach ($pivotTables as $pivot) {
            if ($pivot->referenced_table === $table) {
                continue; // Skip self-references in this context
            }

            $relatedTable = $pivot->referenced_table;
            $relatedModel = $this->generator->getModelNameFromTable($relatedTable);
            $relatedModelClass = $this->qualifyModel($relatedModel);

            // Check if related model exists
            if (!class_exists($relatedModelClass)) {
                continue;
            }

            // Create belongsToMany relation
            $relationName = Str::camel(Str::plural($relatedTable));
            $directRelations[] = [
                'model_class' => $modelClass,
                'model_path' => $this->getModelPath($modelName),
                'method_name' => $relationName,
                'type' => 'belongsToMany',
                'related_model_class' => $relatedModelClass,
                'pivot_table' => $pivot->pivot_table,
            ];

            // Create inverse belongsToMany relation
            $inverseRelationName = Str::camel(Str::plural($table));
            $inverseRelations[] = [
                'model_class' => $relatedModelClass,
                'model_path' => $this->getModelPath($relatedModel),
                'method_name' => $inverseRelationName,
                'type' => 'belongsToMany',
                'related_model_class' => $modelClass,
                'pivot_table' => $pivot->pivot_table,
            ];
        }

        // 3. Process polymorphic relationships
        foreach ($polymorphicColumns as $polyCol) {
            $columnName = $polyCol->column_name;
            if (str_ends_with($columnName, '_type')) {
                $morphPrefix = str_replace('_type', '', $columnName);
                
                // This model can morph to many other models
                $relationName = Str::camel($morphPrefix);
                $directRelations[] = [
                    'model_class' => $modelClass,
                    'model_path' => $this->getModelPath($modelName),
                    'method_name' => $relationName,
                    'type' => 'morphTo',
                    'related_model_class' => null, // morphTo doesn't specify a single class
                ];
            }
        }

        // 4. Check for self-referencing relationships (parent_id)
        $tableColumns = $this->schemaParser->getTableColumns($table);
        foreach ($tableColumns as $column) {
            if ($column->column_name === 'parent_id') {
                // Add parent relationship
                $directRelations[] = [
                    'model_class' => $modelClass,
                    'model_path' => $this->getModelPath($modelName),
                    'method_name' => 'parent',
                    'type' => 'belongsTo',
                    'related_model_class' => $modelClass,
                ];

                // Add children relationship
                $directRelations[] = [
                    'model_class' => $modelClass,
                    'model_path' => $this->getModelPath($modelName),
                    'method_name' => 'children',
                    'type' => 'hasMany',
                    'related_model_class' => $modelClass,
                    'foreign_key' => 'parent_id',
                ];
            }
        }

        return ['direct' => $directRelations, 'inverse' => $inverseRelations];
    }

    /**
     * Get the fully qualified model class name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = Str::studly($model);
        $rootNamespace = app()->getNamespace();
        $modelClass = config('easy-dev.model_namespace', $rootNamespace . 'Models\\') . $model;

        return $modelClass;
    }

    /**
     * Get the file path for a model.
     */
    protected function getModelPath(string $modelName): string
    {
        $modelClass = $this->qualifyModel($modelName);
        
        // Convert namespace to file path
        $relativePath = str_replace(['App\\', '\\'], ['', '/'], $modelClass) . '.php';
        
        return app_path($relativePath);
    }

    /**
     * Get all models in the application.
     */
    public function getAllModels(): array
    {
        $modelPath = app_path('Models');
        
        if (!$this->files->isDirectory($modelPath)) {
            return [];
        }

        $models = [];
        $files = $this->files->allFiles($modelPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $models[] = $file->getBasename('.php');
            }
        }

        return $models;
    }
}
