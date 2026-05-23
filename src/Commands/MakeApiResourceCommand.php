<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use AnasNashat\EasyDev\Services\RelationDetector;

class MakeApiResourceCommand extends Command
{
    protected $name = 'easy-dev:api-resource';
    protected $description = 'Generate API resource and collection classes for a model.';

    public function __construct(protected FileGenerator $generator, protected RelationDetector $relationDetector)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = $this->argument('model');
        $withCollection = !$this->option('without-collection');
        $isAiMode = $this->option('ai');
        $preset = $this->option('preset');

        $generatedFiles = [];

        try {
            $modelClass = $this->qualifyModel($modelName);

            // Check if model exists
            if (!class_exists($modelClass)) {
                if ($isAiMode) {
                    $this->output->write(json_encode([
                        'status' => 'error',
                        'message' => "Model {$modelName} not found. Please create it first.",
                        'suggestions' => [
                            "Create the model using: php artisan easy-dev:crud {$modelName}",
                        ]
                    ], JSON_PRETTY_PRINT));
                } else {
                    $this->error("Model {$modelName} not found. Please create it first.");
                }
                return self::FAILURE;
            }

            if (!$isAiMode) {
                $this->info("Generating API resources for {$modelName}...");
            }

            // Generate Resource
            $resourceName = "{$modelName}Resource";
            $resourcePath = $this->generator->resolveOutputPath(
                'resources',
                "{$resourceName}.php",
                $this->option('path'),
                $this->option('module'),
                $preset
            );

            $shouldGenerateResource = true;
            if (file_exists($resourcePath)) {
                if (!$isAiMode) {
                    if (!$this->confirm("Resource {$resourceName} already exists. Overwrite?")) {
                        $this->line("  Skipped resource generation.");
                        $shouldGenerateResource = false;
                    }
                }
            }

            if ($shouldGenerateResource) {
                $replacements = [
                    'ModelName' => $modelName,
                    'ResourceName' => $resourceName,
                    'modelName' => Str::camel($modelName),
                ];

                $this->generator->generateFile($resourcePath, 'api_resource', $replacements, $this->option('stub'), $modelName, $this->option('path'), $this->option('module'), $preset);
                
                if (!$isAiMode) {
                    $this->info("  ✓ Generated resource: {$resourceName}");
                }

                $generatedFiles[] = [
                    'type' => 'resource',
                    'name' => $resourceName,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $resourcePath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('api_resource', $this->option('stub'))),
                ];
            }

            // Generate Collection
            if ($withCollection) {
                $collectionName = "{$modelName}Collection";
                $collectionPath = $this->generator->resolveOutputPath(
                    'resources',
                    "{$collectionName}.php",
                    $this->option('path'),
                    $this->option('module'),
                    $preset
                );

                $shouldGenerateCollection = true;
                if (file_exists($collectionPath)) {
                    if (!$isAiMode) {
                        if (!$this->confirm("Collection {$collectionName} already exists. Overwrite?")) {
                            $this->line("  Skipped collection generation.");
                            $shouldGenerateCollection = false;
                        }
                    }
                }

                if ($shouldGenerateCollection) {
                    $replacements = [
                        'ModelName' => $modelName,
                        'CollectionName' => $collectionName,
                        'ResourceName' => "{$modelName}Resource",
                        'modelName' => Str::camel($modelName),
                    ];

                    $this->generator->generateFile($collectionPath, 'api_collection', $replacements, $this->option('stub'), $modelName, $this->option('path'), $this->option('module'), $preset);
                    
                    if (!$isAiMode) {
                        $this->info("  ✓ Generated collection: {$collectionName}");
                    }

                    $generatedFiles[] = [
                        'type' => 'collection',
                        'name' => $collectionName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $collectionPath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('api_collection', $this->option('stub'))),
                    ];
                }
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:api-resource',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->info("✓ API resources generated successfully for {$modelName}!");
                $this->showNextSteps($modelName, $withCollection);
            }

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        "Ensure standard directory permissions are properly configured.",
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("Error generating API resources: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
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

    protected function qualifyModel(string $model): string
    {
        return $this->relationDetector->qualifyModel($model);
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
            ['stub', null, InputOption::VALUE_OPTIONAL, 'Override stub template name or absolute/relative file path.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Override the output directory path.'],
            ['module', null, InputOption::VALUE_OPTIONAL, 'Generate inside a domain module directory.'],
            ['preset', null, InputOption::VALUE_OPTIONAL, 'Use a pre-configured architecture preset (e.g. clean).'],
            ['ai', null, InputOption::VALUE_NONE, 'Output machine-friendly JSON format for AI integration.'],
        ];
    }
}
