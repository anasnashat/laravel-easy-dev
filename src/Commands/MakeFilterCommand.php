<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Services\MigrationParser;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeFilterCommand extends Command
{
    protected $name = 'easy-dev:filter';
    protected $description = 'Generate a query filter class for a model.';

    public function __construct(
        protected FileGenerator $generator,
        protected MigrationParser $migrationParser
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = Str::studly($this->argument('model'));
        $isAiMode = $this->option('ai');
        $preset = $this->option('preset');

        $generatedFiles = [];

        try {
            $filterName = "{$modelName}Filter";
            
            $filterPath = $this->generator->resolveOutputPath(
                'filters',
                "{$filterName}.php",
                $this->option('path'),
                $this->option('module'),
                $preset
            );

            $shouldGenerate = true;
            if (file_exists($filterPath)) {
                if (!$isAiMode) {
                    if (!$this->confirm("Filter {$filterName} already exists. Overwrite?")) {
                        $this->line("  Skipped filter generation.");
                        $shouldGenerate = false;
                    }
                }
            }

            if ($shouldGenerate) {
                // Get fields from migration for filter methods
                $filterMethods = '';
                if ($this->migrationParser->migrationExists($modelName)) {
                    $migrationPath = $this->migrationParser->getMigrationPath($modelName);
                    $migrationData = $this->migrationParser->parseMigration($migrationPath);
                    $fillable = $migrationData['fillable'] ?? [];

                    if (!empty($fillable)) {
                        $methods = [];
                        foreach ($fillable as $field) {
                            $methodName = Str::camel($field);
                            $methods[] = $this->generateFilterMethod($field, $methodName);
                        }
                        $filterMethods = implode("\n\n", $methods);
                    }
                }

                // Determine namespaces dynamically
                $filterNamespace = $this->generator->getNamespaceForType('filters', $modelName, $this->option('path'), $this->option('module'), $preset);
                $modelNamespace = $this->generator->getNamespaceForType('models', $modelName, $this->option('path'), $this->option('module'), $preset);

                $replacements = [
                    'FilterNamespace' => $filterNamespace,
                    'ModelNamespace' => $modelNamespace,
                    'FilterName' => $filterName,
                    'ModelName' => $modelName,
                    'filterMethods' => $filterMethods ?: "    // Add your filter methods here\n    // Each method receives a \$value and applies a filter to \$this->builder",
                ];

                $this->generator->generateFile($filterPath, 'filter', $replacements, $this->option('stub'), $modelName, $this->option('path'), $this->option('module'), $preset);
                
                if (!$isAiMode) {
                    $this->info("  ✓ Generated filter: {$filterName}");
                }

                $generatedFiles[] = [
                    'type' => 'filter',
                    'name' => $filterName,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filterPath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('filter', $this->option('stub'))),
                ];
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:filter',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->line('<info>Usage:</info>');
                $this->line("  \$filtered = {$filterName}::apply(\$query, \$request->validated());");
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
                $this->error("Error generating filter: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function generateFilterMethod(string $field, string $methodName): string
    {
        if (str_starts_with($field, 'is_') || str_starts_with($field, 'has_')) {
            return "    public function {$methodName}(\$value): void\n    {\n        \$this->builder->where('{$field}', filter_var(\$value, FILTER_VALIDATE_BOOLEAN));\n    }";
        }

        if (str_ends_with($field, '_id')) {
            return "    public function {$methodName}(\$value): void\n    {\n        \$this->builder->where('{$field}', \$value);\n    }";
        }

        return "    public function {$methodName}(\$value): void\n    {\n        \$this->builder->where('{$field}', 'like', \"%{\$value}%\");\n    }";
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model to generate a filter for.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['stub', null, InputOption::VALUE_OPTIONAL, 'Override stub template name or absolute/relative file path.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Override the output directory path.'],
            ['module', null, InputOption::VALUE_OPTIONAL, 'Generate inside a domain module directory.'],
            ['preset', null, InputOption::VALUE_OPTIONAL, 'Use a pre-configured architecture preset (e.g. clean).'],
            ['ai', null, InputOption::VALUE_NONE, 'Output machine-friendly JSON format for AI integration.'],
        ];
    }
}
