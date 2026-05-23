<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeEnumCommand extends Command
{
    protected $name = 'easy-dev:enum';
    protected $description = 'Generate a PHP 8.1+ backed enum class.';

    public function __construct(protected FileGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $isAiMode = $this->option('ai');
        $preset = $this->option('preset');

        $generatedFiles = [];

        try {
            $enumPath = $this->generator->resolveOutputPath(
                'enums',
                "{$name}.php",
                $this->option('path'),
                $this->option('module'),
                $preset
            );

            $shouldGenerate = true;
            if (file_exists($enumPath)) {
                if (!$isAiMode) {
                    if (!$this->confirm("Enum {$name} already exists. Overwrite?")) {
                        $this->line("  Skipped enum generation.");
                        $shouldGenerate = false;
                    }
                }
            }

            if ($shouldGenerate) {
                // Parse values from option
                $cases = '';
                $valuesOption = $this->option('values');
                if ($valuesOption) {
                    $values = array_map('trim', explode(',', $valuesOption));
                    $caseLines = [];
                    foreach ($values as $value) {
                        $caseName = Str::upper(Str::snake($value));
                        $caseLines[] = "    case {$caseName} = '{$value}';";
                    }
                    $cases = implode("\n", $caseLines);
                } else {
                    $cases = "    case ACTIVE = 'active';\n    case INACTIVE = 'inactive';";
                }

                // Determine namespaces dynamically
                $enumNamespace = $this->generator->getNamespaceForType('enums', $name, $this->option('path'), $this->option('module'), $preset);

                $replacements = [
                    'EnumNamespace' => $enumNamespace,
                    'EnumName' => $name,
                    'cases' => $cases,
                ];

                $this->generator->generateFile($enumPath, 'enum', $replacements, $this->option('stub'), $name, $this->option('path'), $this->option('module'), $preset);
                
                if (!$isAiMode) {
                    $this->info("  ✓ Generated enum: {$name}");
                }

                $generatedFiles[] = [
                    'type' => 'enum',
                    'name' => $name,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $enumPath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('enum', $this->option('stub'))),
                ];
            }

            if ($isAiMode) {
                $this->line(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:enum',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->line('<info>Usage:</info>');
                $this->line("  In migration: \$table->string('status')->default({$name}::ACTIVE->value);");
                $this->line("  In model cast: protected \$casts = ['status' => {$name}::class];");
            }

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->line(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        "Ensure standard directory permissions are properly configured.",
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("Error generating enum: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the enum.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['values', null, InputOption::VALUE_OPTIONAL, 'Comma-separated enum values (e.g., "active,inactive,pending").'],
            ['stub', null, InputOption::VALUE_OPTIONAL, 'Override stub template name or absolute/relative file path.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Override the output directory path.'],
            ['module', null, InputOption::VALUE_OPTIONAL, 'Generate inside a domain module directory.'],
            ['preset', null, InputOption::VALUE_OPTIONAL, 'Use a pre-configured architecture preset (e.g. clean).'],
            ['ai', null, InputOption::VALUE_NONE, 'Output machine-friendly JSON format for AI integration.'],
        ];
    }
}
