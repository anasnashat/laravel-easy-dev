<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeObserverCommand extends Command
{
    protected $name = 'easy-dev:observer';
    protected $description = 'Generate a model observer with lifecycle event hooks.';

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
            $observerName = "{$modelName}Observer";
            
            $observerPath = $this->generator->resolveOutputPath(
                'observers',
                "{$observerName}.php",
                $this->option('path'),
                $this->option('module'),
                $preset
            );

            $shouldGenerate = true;
            if (file_exists($observerPath)) {
                if (!$isAiMode) {
                    if (!$this->confirm("Observer {$observerName} already exists. Overwrite?")) {
                        $this->line("  Skipped observer generation.");
                        $shouldGenerate = false;
                    }
                }
            }

            if ($shouldGenerate) {
                // Determine namespaces dynamically
                $observerNamespace = $this->generator->getNamespaceForType('observers', $modelName, $this->option('path'), $this->option('module'), $preset);
                $modelNamespace = $this->generator->getNamespaceForType('models', $modelName, $this->option('path'), $this->option('module'), $preset);

                $replacements = [
                    'ObserverNamespace' => $observerNamespace,
                    'ModelNamespace' => $modelNamespace,
                    'ModelName' => $modelName,
                    'ObserverName' => $observerName,
                    'modelName' => Str::camel($modelName),
                ];

                $this->generator->generateFile($observerPath, 'observer', $replacements, $this->option('stub'), $modelName, $this->option('path'), $this->option('module'), $preset);
                
                if (!$isAiMode) {
                    $this->info("  ✓ Generated observer: {$observerName}");
                }

                $generatedFiles[] = [
                    'type' => 'observer',
                    'name' => $observerName,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $observerPath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('observer', $this->option('stub'))),
                ];
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:observer',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->line('<info>Next Steps:</info>');
                $this->line("  Register in AppServiceProvider boot():");
                $this->line("    {$modelName}::observe({$observerName}::class);");
                $this->line("  Or use the #[ObservedBy] attribute on the model (Laravel 10+).");
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
                $this->error("Error generating observer: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model to generate an observer for.'],
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
