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

        try {
            $enumPath = config('easy-dev.paths.enums', app_path('Enums')) . "/{$name}.php";

            if (file_exists($enumPath)) {
                if (!$this->confirm("Enum {$name} already exists. Overwrite?")) {
                    $this->line("  Skipped enum generation.");
                    return self::SUCCESS;
                }
            }

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

            $replacements = [
                'EnumName' => $name,
                'cases' => $cases,
            ];

            $this->generator->generateFile($enumPath, 'enum', $replacements);
            $this->info("  ✓ Generated enum: {$name}");

            $this->newLine();
            $this->line('<info>Usage:</info>');
            $this->line("  In migration: \$table->string('status')->default({$name}::ACTIVE->value);");
            $this->line("  In model cast: protected \$casts = ['status' => {$name}::class];");

        } catch (\Exception $e) {
            $this->error("Error generating enum: {$e->getMessage()}");
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
        ];
    }
}
