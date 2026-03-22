<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Services\MigrationParser;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

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

        try {
            $filterName = "{$modelName}Filter";
            $filterPath = config('easy-dev.paths.filters', app_path('Filters')) . "/{$filterName}.php";

            if (file_exists($filterPath)) {
                if (!$this->confirm("Filter {$filterName} already exists. Overwrite?")) {
                    $this->line("  Skipped filter generation.");
                    return self::SUCCESS;
                }
            }

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

            $replacements = [
                'FilterName' => $filterName,
                'ModelName' => $modelName,
                'filterMethods' => $filterMethods ?: "    // Add your filter methods here\n    // Each method receives a \$value and applies a filter to \$this->builder",
            ];

            $this->generator->generateFile($filterPath, 'filter', $replacements);
            $this->info("  ✓ Generated filter: {$filterName}");

            $this->newLine();
            $this->line('<info>Usage:</info>');
            $this->line("  \$filtered = {$filterName}::apply(\$query, \$request->validated());");

        } catch (\Exception $e) {
            $this->error("Error generating filter: {$e->getMessage()}");
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
}
