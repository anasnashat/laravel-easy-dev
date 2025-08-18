<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\CodeWriter;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeModelRelationCommand extends Command
{
    protected $name = 'easy-dev:add-relation';
    protected $description = 'Add a relationship method to an existing model.';

    public function __construct(
        protected CodeWriter $codeWriter,
        protected FileGenerator $generator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = $this->argument('model');
        $relationType = $this->argument('relation');
        $relatedModel = $this->argument('related-model');
        $methodName = $this->option('method') ?? $this->generateMethodName($relationType, $relatedModel);

        try {
            $modelClass = $this->qualifyModel($modelName);
            $relatedModelClass = $this->qualifyModel($relatedModel);
            $modelPath = $this->getModelPath($modelName);

            // Validate inputs
            if (!class_exists($modelClass)) {
                $this->error("Model {$modelClass} not found.");
                return self::FAILURE;
            }

            if (!class_exists($relatedModelClass)) {
                $this->error("Related model {$relatedModelClass} not found.");
                return self::FAILURE;
            }

            if (!in_array($relationType, $this->getSupportedRelations())) {
                $this->error("Unsupported relation type: {$relationType}");
                $this->info('Supported relations: ' . implode(', ', $this->getSupportedRelations()));
                return self::FAILURE;
            }

            // Add the relation
            $this->codeWriter->addRelation($modelPath, $methodName, $relationType, $relatedModelClass);

            $this->info("Successfully added {$relationType} relation '{$methodName}' to {$modelName}!");

            // Ask if user wants to add inverse relation
            if ($this->shouldAddInverseRelation($relationType)) {
                $inverseRelationType = $this->getInverseRelationType($relationType);
                $inverseMethodName = $this->generateMethodName($inverseRelationType, $modelName);

                if ($this->confirm("Add inverse {$inverseRelationType} relation '{$inverseMethodName}' to {$relatedModel}?")) {
                    $relatedModelPath = $this->getModelPath($relatedModel);
                    $this->codeWriter->addRelation($relatedModelPath, $inverseMethodName, $inverseRelationType, $modelClass);
                    $this->info("Successfully added inverse {$inverseRelationType} relation '{$inverseMethodName}' to {$relatedModel}!");
                }
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Generate a method name based on relation type and related model.
     */
    protected function generateMethodName(string $relationType, string $relatedModel): string
    {
        return match ($relationType) {
            'hasMany', 'belongsToMany', 'morphMany' => Str::camel(Str::plural($relatedModel)),
            'hasOne', 'belongsTo', 'morphTo' => Str::camel(Str::singular($relatedModel)),
            default => Str::camel($relatedModel),
        };
    }

    /**
     * Get supported relation types.
     */
    protected function getSupportedRelations(): array
    {
        return [
            'hasOne',
            'hasMany',
            'belongsTo',
            'belongsToMany',
            'morphTo',
            'morphOne',
            'morphMany',
        ];
    }

    /**
     * Check if relation type should have an inverse relation suggested.
     */
    protected function shouldAddInverseRelation(string $relationType): bool
    {
        return in_array($relationType, ['hasOne', 'hasMany', 'belongsTo', 'belongsToMany']);
    }

    /**
     * Get the inverse relation type.
     */
    protected function getInverseRelationType(string $relationType): string
    {
        return match ($relationType) {
            'hasOne' => 'belongsTo',
            'hasMany' => 'belongsTo',
            'belongsTo' => 'hasMany',
            'belongsToMany' => 'belongsToMany',
            default => $relationType,
        };
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

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of the model to add the relation to.'],
            ['relation', InputArgument::REQUIRED, 'The type of relation (hasOne, hasMany, belongsTo, etc.).'],
            ['related-model', InputArgument::REQUIRED, 'The name of the related model.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['method', 'm', InputOption::VALUE_REQUIRED, 'The name of the relation method.'],
        ];
    }
}
