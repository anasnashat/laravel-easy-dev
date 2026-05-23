<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakePolicyCommand extends Command
{
    protected $name = 'easy-dev:policy';
    protected $description = 'Generate an authorization policy for a model with full CRUD methods.';

    public function __construct(protected FileGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = Str::studly($this->argument('model'));
        $isAiMode = $this->option('ai');
        $preset = $this->option('preset');

        $generatedFiles = [];

        try {
            $policyName = "{$modelName}Policy";
            
            $policyPath = $this->generator->resolveOutputPath(
                'policies',
                "{$policyName}.php",
                $this->option('path'),
                $this->option('module'),
                $preset
            );

            $shouldGenerate = true;
            if (file_exists($policyPath)) {
                if (!$isAiMode) {
                    if (!$this->confirm("Policy {$policyName} already exists. Overwrite?")) {
                        $this->line("  Skipped policy generation.");
                        $shouldGenerate = false;
                    }
                }
            }

            if ($shouldGenerate) {
                // Determine model namespace dynamically
                $modelNamespace = $this->generator->getNamespaceForType(
                    'models',
                    $modelName,
                    $this->option('path'),
                    $this->option('module'),
                    $preset
                );

                $replacements = [
                    'ModelName' => $modelName,
                    'PolicyName' => $policyName,
                    'modelName' => Str::camel($modelName),
                    'ModelNamespace' => $modelNamespace,
                ];

                $this->generator->generateFile($policyPath, 'policy', $replacements, $this->option('stub'), $modelName, $this->option('path'), $this->option('module'), $preset);
                
                if (!$isAiMode) {
                    $this->info("  ✓ Generated policy: {$policyName}");
                }

                $generatedFiles[] = [
                    'type' => 'policy',
                    'name' => $policyName,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $policyPath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('policy', $this->option('stub'))),
                ];
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:policy',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->line('<info>Next Steps:</info>');
                $this->line("  Register in AuthServiceProvider or use Laravel's auto-discovery.");
                $this->line("  Use in controllers: \$this->authorize('viewAny', {$modelName}::class);");
            }

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        "Check write permissions for policy storage path.",
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("Error generating policy: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model to generate a policy for.'],
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
