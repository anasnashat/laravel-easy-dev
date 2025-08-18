<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeApiResourceCommand extends Command
{
    protected $name = 'easy-dev:api-resource';
    protected $description = 'Generate API resource and collection classes for a model.';

    public function __construct(protected FileGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = $this->argument('model');
        $withCollection = !$this->option('without-collection');

        try {
            $modelClass = $this->qualifyModel($modelName);

            // Check if model exists
            if (!class_exists($modelClass)) {
                $this->error("Model {$modelName} not found. Please create it first.");
                return self::FAILURE;
            }

            $this->info("Generating API resources for {$modelName}...");

            // Generate resource
            $this->generateResource($modelName);

            // Generate collection
            if ($withCollection) {
                $this->generateCollection($modelName);
            }

            $this->info("✓ API resources generated successfully for {$modelName}!");
            $this->showNextSteps($modelName, $withCollection);

        } catch (\Exception $e) {
            $this->error("Error generating API resources: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Generate API resource.
     */
    protected function generateResource(string $modelName): void
    {
        $resourceName = "{$modelName}Resource";
        $resourcePath = $this->getResourcePath($resourceName);

        // Skip if resource already exists
        if (file_exists($resourcePath)) {
            if (!$this->confirm("Resource {$resourceName} already exists. Overwrite?")) {
                $this->line("  Skipped resource generation.");
                return;
            }
        }

        $replacements = [
            'ModelName' => $modelName,
            'ResourceName' => $resourceName,
            'modelName' => Str::camel($modelName),
        ];

        $this->generator->generateFile($resourcePath, 'api.resource', $replacements);
        $this->info("  ✓ Generated resource: {$resourceName}");
    }

    /**
     * Generate API resource collection.
     */
    protected function generateCollection(string $modelName): void
    {
        $collectionName = "{$modelName}Collection";
        $collectionPath = $this->getResourcePath($collectionName);

        // Skip if collection already exists
        if (file_exists($collectionPath)) {
            if (!$this->confirm("Collection {$collectionName} already exists. Overwrite?")) {
                $this->line("  Skipped collection generation.");
                return;
            }
        }

        $replacements = [
            'ModelName' => $modelName,
            'CollectionName' => $collectionName,
            'ResourceName' => "{$modelName}Resource",
            'modelName' => Str::camel($modelName),
        ];

        $this->generator->generateFile($collectionPath, 'api.collection', $replacements);
        $this->info("  ✓ Generated collection: {$collectionName}");
    }

    /**
     * Show next steps to the user.
     */
    protected function showNextSteps(string $modelName, bool $withCollection): void
    {
        $this->newLine();
        $this->line('<info>Next Steps:</info>');
        $this->line("1. Customize the {$modelName}Resource::toArray() method");
        
        if ($withCollection) {
            $this->line("2. Use {$modelName}Collection for paginated responses");
            $this->line("3. Example usage in controller:");
            $this->line("   <comment>return new {$modelName}Collection({$modelName}::paginate());</comment>");
            $this->line("   <comment>return new {$modelName}Resource(\${Str::camel($modelName)});</comment>");
        } else {
            $this->line("2. Example usage in controller:");
            $this->line("   <comment>return {$modelName}Resource::collection({$modelName}::all());</comment>");
            $this->line("   <comment>return new {$modelName}Resource(\${Str::camel($modelName)});</comment>");
        }
    }

    /**
     * Get the fully qualified model class name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = Str::studly($model);
        $rootNamespace = app()->getNamespace();
        return config('easy-dev.model_namespace', $rootNamespace . 'Models\\') . $model;
    }

    /**
     * Get resource file path.
     */
    protected function getResourcePath(string $resourceName): string
    {
        return app_path("Http/Resources/{$resourceName}.php");
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of the model to generate API resources for.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['without-collection', null, InputOption::VALUE_NONE, 'Generate resource without collection class.'],
        ];
    }
}
