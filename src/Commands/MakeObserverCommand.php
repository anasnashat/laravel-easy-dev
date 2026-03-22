<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

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

        try {
            $observerName = "{$modelName}Observer";
            $observerPath = config('easy-dev.paths.observers', app_path('Observers')) . "/{$observerName}.php";

            if (file_exists($observerPath)) {
                if (!$this->confirm("Observer {$observerName} already exists. Overwrite?")) {
                    $this->line("  Skipped observer generation.");
                    return self::SUCCESS;
                }
            }

            $replacements = [
                'ModelName' => $modelName,
                'ObserverName' => $observerName,
                'modelName' => Str::camel($modelName),
            ];

            $this->generator->generateFile($observerPath, 'observer', $replacements);
            $this->info("  ✓ Generated observer: {$observerName}");

            $this->newLine();
            $this->line('<info>Next Steps:</info>');
            $this->line("  Register in AppServiceProvider boot():");
            $this->line("    {$modelName}::observe({$observerName}::class);");
            $this->line("  Or use the #[ObservedBy] attribute on the model (Laravel 10+).");

        } catch (\Exception $e) {
            $this->error("Error generating observer: {$e->getMessage()}");
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
}
