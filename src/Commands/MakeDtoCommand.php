<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Services\MigrationParser;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeDtoCommand extends Command
{
    protected $name = 'easy-dev:dto';
    protected $description = 'Generate a Data Transfer Object (DTO) for a model.';

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
            $dtoName = "{$modelName}Data";
            
            $dtoPath = $this->generator->resolveOutputPath(
                'dtos',
                "{$dtoName}.php",
                $this->option('path'),
                $this->option('module'),
                $preset
            );

            $shouldGenerate = true;
            if (file_exists($dtoPath)) {
                if (!$isAiMode) {
                    if (!$this->confirm("DTO {$dtoName} already exists. Overwrite?")) {
                        $this->line("  Skipped DTO generation.");
                        $shouldGenerate = false;
                    }
                }
            }

            if ($shouldGenerate) {
                // Get fields from migration
                $properties = '';
                $fromRequestBody = '';
                $fromModelBody = '';

                if ($this->migrationParser->migrationExists($modelName)) {
                    $migrationPath = $this->migrationParser->getMigrationPath($modelName);
                    $migrationData = $this->migrationParser->parseMigration($migrationPath);
                    $fillable = $migrationData['fillable'] ?? [];

                    if (!empty($fillable)) {
                        $propLines = [];
                        $requestLines = [];
                        $modelLines = [];

                        foreach ($fillable as $field) {
                            $type = $this->guessPhpType($field);
                            $propLines[] = "        public readonly ?{$type} \${$field},";
                            $requestLines[] = "            {$field}: \$request->validated('{$field}'),";
                            $modelLines[] = "            {$field}: \$model->{$field},";
                        }

                        $properties = implode("\n", $propLines);
                        $fromRequestBody = implode("\n", $requestLines);
                        $fromModelBody = implode("\n", $modelLines);
                    }
                }

                // Determine namespaces dynamically
                $dtoNamespace = $this->generator->getNamespaceForType('dtos', $modelName, $this->option('path'), $this->option('module'), $preset);
                $modelNamespace = $this->generator->getNamespaceForType('models', $modelName, $this->option('path'), $this->option('module'), $preset);

                $replacements = [
                    'DtoNamespace' => $dtoNamespace,
                    'ModelNamespace' => $modelNamespace,
                    'DtoName' => $dtoName,
                    'ModelName' => $modelName,
                    'properties' => $properties ?: "        public readonly ?string \$name,",
                    'fromRequestBody' => $fromRequestBody ?: "            name: \$request->validated('name'),",
                    'fromModelBody' => $fromModelBody ?: "            name: \$model->name,",
                ];

                $this->generator->generateFile($dtoPath, 'dto', $replacements, $this->option('stub'), $modelName, $this->option('path'), $this->option('module'), $preset);
                
                if (!$isAiMode) {
                    $this->info("  ✓ Generated DTO: {$dtoName}");
                }

                $generatedFiles[] = [
                    'type' => 'dto',
                    'name' => $dtoName,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $dtoPath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('dto', $this->option('stub'))),
                ];
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:dto',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->line('<info>Next Steps:</info>');
                $this->line("  Use in service: \${$modelName}Data::fromRequest(\$request);");
                $this->line("  Use for response: \${$modelName}Data::fromModel(\$model);");
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
                $this->error("Error generating DTO: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Guess PHP type from field name.
     */
    protected function guessPhpType(string $field): string
    {
        if (str_ends_with($field, '_id')) return 'int';
        if (str_starts_with($field, 'is_') || str_starts_with($field, 'has_')) return 'bool';
        if (in_array($field, ['price', 'amount', 'total', 'cost', 'rate'])) return 'float';
        if (in_array($field, ['quantity', 'count', 'order', 'position', 'age'])) return 'int';
        return 'string';
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model to generate a DTO for.'],
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
