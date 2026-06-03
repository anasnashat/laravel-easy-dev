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
        $preset = $this->resolveArchitecturePreset();

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

            if ($this->option('register')) {
                $modelPath = $this->generator->resolveOutputPath(
                    'models',
                    "{$modelName}.php",
                    $this->option('path'),
                    $this->option('module'),
                    $preset
                );

                if (file_exists($modelPath)) {
                    $this->registerObserverOnModel($modelPath, "{$this->generator->getNamespaceForType('observers', $modelName, $this->option('path'), $this->option('module'), $preset)}\\{$observerName}");

                    $generatedFiles[] = [
                        'type' => 'observer_registration',
                        'name' => "{$modelName} observed by {$observerName}",
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $modelPath),
                        'stub_used' => 'model_attribute',
                    ];
                }
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
            ['architecture', null, InputOption::VALUE_OPTIONAL, 'Use an architecture layout: laravel, clean, or ddd. Alias for --preset where applicable.'],
            ['register', null, InputOption::VALUE_NONE, 'Register the observer on the model with the ObservedBy attribute.'],
            ['ai', null, InputOption::VALUE_NONE, 'Output machine-friendly JSON format for AI integration.'],
        ];
    }

    protected function registerObserverOnModel(string $modelPath, string $observerClass): void
    {
        $content = file_get_contents($modelPath);

        if (str_contains($content, 'ObservedBy') && str_contains($content, $observerClass . '::class')) {
            return;
        }

        if (!str_contains($content, 'use Illuminate\\Database\\Eloquent\\Attributes\\ObservedBy;')) {
            $content = preg_replace(
                '/(<\?php\s+namespace\s+[^;]+;\s*)/m',
                "$1\nuse Illuminate\\Database\\Eloquent\\Attributes\\ObservedBy;\n",
                $content,
                1
            );
        }

        if (!str_contains($content, "use {$observerClass};")) {
            $content = preg_replace(
                '/(use Illuminate\\\\Database\\\\Eloquent\\\\Attributes\\\\ObservedBy;\s*)/m',
                "$1use {$observerClass};\n",
                $content,
                1
            );
        }

        $shortObserver = class_basename($observerClass);
        $content = preg_replace('/(\nclass\s+)/', "\n#[ObservedBy([{$shortObserver}::class])]\nclass ", $content, 1);

        file_put_contents($modelPath, $content);
    }

    protected function resolveArchitecturePreset(): ?string
    {
        $architecture = $this->option('architecture');

        if ($architecture === 'laravel') {
            return null;
        }

        if ($architecture === 'ddd') {
            return 'ddd';
        }

        return $architecture ?: $this->option('preset');
    }
}
