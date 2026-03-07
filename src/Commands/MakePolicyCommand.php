<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

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

        try {
            $policyName = "{$modelName}Policy";
            $policyPath = config('easy-dev.paths.policies', app_path('Policies')) . "/{$policyName}.php";

            if (file_exists($policyPath)) {
                if (!$this->confirm("Policy {$policyName} already exists. Overwrite?")) {
                    $this->line("  Skipped policy generation.");
                    return self::SUCCESS;
                }
            }

            $replacements = [
                'ModelName' => $modelName,
                'PolicyName' => $policyName,
                'modelName' => Str::camel($modelName),
            ];

            $this->generator->generateFile($policyPath, 'policy', $replacements);
            $this->info("  ✓ Generated policy: {$policyName}");

            $this->newLine();
            $this->line('<info>Next Steps:</info>');
            $this->line("  Register in AuthServiceProvider or use Laravel's auto-discovery.");
            $this->line("  Use in controllers: \$this->authorize('viewAny', {$modelName}::class);");

        } catch (\Exception $e) {
            $this->error("Error generating policy: {$e->getMessage()}");
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
}
