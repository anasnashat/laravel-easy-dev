<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Services\MigrationParser;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

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

        try {
            $dtoName = "{$modelName}Data";
            $dtoPath = config('easy-dev.paths.dtos', app_path('DTOs')) . "/{$dtoName}.php";

            if (file_exists($dtoPath)) {
                if (!$this->confirm("DTO {$dtoName} already exists. Overwrite?")) {
                    $this->line("  Skipped DTO generation.");
                    return self::SUCCESS;
                }
            }

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
                        $propLines[] = "        public readonly {$type} \${$field},";
                        $requestLines[] = "            {$field}: \$request->validated('{$field}'),";
                        $modelLines[] = "            {$field}: \$model->{$field},";
                    }

                    $properties = implode("\n", $propLines);
                    $fromRequestBody = implode("\n", $requestLines);
                    $fromModelBody = implode("\n", $modelLines);
                }
            }

            $replacements = [
                'DtoName' => $dtoName,
                'ModelName' => $modelName,
                'properties' => $properties ?: "        // Add your properties here",
                'fromRequestBody' => $fromRequestBody ?: "            // Map request fields here",
                'fromModelBody' => $fromModelBody ?: "            // Map model fields here",
            ];

            $this->generator->generateFile($dtoPath, 'dto', $replacements);
            $this->info("  ✓ Generated DTO: {$dtoName}");

            $this->newLine();
            $this->line('<info>Next Steps:</info>');
            $this->line("  Use in service: \${$modelName}Data::fromRequest(\$request);");
            $this->line("  Use for response: \${$modelName}Data::fromModel(\$model);");

        } catch (\Exception $e) {
            $this->error("Error generating DTO: {$e->getMessage()}");
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
}
