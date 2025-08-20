<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeRepositoryCommand extends Command
{
    protected $name = 'easy-dev:repository';
    protected $description = 'Generate repository pattern files (Interface and Implementation) for a model.';

    public function __construct(protected FileGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = $this->argument('model');
        $withInterface = !$this->option('without-interface');

        try {
            $modelClass = $this->qualifyModel($modelName);

            // Check if model exists
            if (!class_exists($modelClass)) {
                $this->error("Model {$modelName} not found. Please create it first.");
                return self::FAILURE;
            }

            $this->info("Generating repository pattern for {$modelName}...");

            // Generate interface
            if ($withInterface) {
                $this->generateRepositoryInterface($modelName);
            }

            // Generate repository implementation
            $this->generateRepositoryImplementation($modelName, $withInterface);

            $this->info("✓ Repository pattern generated successfully for {$modelName}!");
            $this->showNextSteps($modelName, $withInterface);

        } catch (\Exception $e) {
            $this->error("Error generating repository: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Generate repository interface.
     */
    protected function generateRepositoryInterface(string $modelName): void
    {
        $interfaceName = "{$modelName}RepositoryInterface";
        $interfacePath = $this->getRepositoryInterfacePath($interfaceName);

        // Skip if interface already exists
        if (file_exists($interfacePath)) {
            if (!$this->confirm("Interface {$interfaceName} already exists. Overwrite?")) {
                $this->line("  Skipped interface generation.");
                return;
            }
        }

        $replacements = [
            'ModelName' => $modelName,
            'InterfaceName' => $interfaceName,
            'modelName' => Str::camel($modelName),
        ];

        $this->generator->generateFile($interfacePath, 'repository.interface', $replacements);
        $this->info("  ✓ Generated interface: {$interfaceName}");
    }

    /**
     * Generate repository implementation.
     */
    protected function generateRepositoryImplementation(string $modelName, bool $withInterface): void
    {
        $repositoryName = "{$modelName}Repository";
        $repositoryPath = $this->getRepositoryPath($repositoryName);

        // Skip if repository already exists
        if (file_exists($repositoryPath)) {
            if (!$this->confirm("Repository {$repositoryName} already exists. Overwrite?")) {
                $this->line("  Skipped repository implementation generation.");
                return;
            }
        }

        $replacements = [
            'ModelName' => $modelName,
            'RepositoryName' => $repositoryName,
            'InterfaceName' => $withInterface ? "{$modelName}RepositoryInterface" : '',
            'modelName' => Str::camel($modelName),
            'WithInterface' => $withInterface,
            'InterfaceUse' => $withInterface ? "use App\\Repositories\\Interfaces\\{$modelName}RepositoryInterface;\n" : '',
            'InterfaceImplements' => $withInterface ? " implements {$modelName}RepositoryInterface" : '',
        ];

        $this->generator->generateFile($repositoryPath, 'repository', $replacements);
        $this->info("  ✓ Generated repository: {$repositoryName}");
    }

    /**
     * Show next steps to the user.
     */
    protected function showNextSteps(string $modelName, bool $withInterface): void
    {
        $this->newLine();
        $this->line('<info>Next Steps:</info>');
        
        if ($withInterface) {
            $this->line("1. Bind the interface to implementation in a service provider:");
            $this->line("   <comment>\$this->app->bind({$modelName}RepositoryInterface::class, {$modelName}Repository::class);</comment>");
            $this->line("2. Use dependency injection in your controllers:");
            $this->line("   <comment>public function __construct(protected {$modelName}RepositoryInterface \$repository) {}</comment>");
        } else {
            $this->line("1. Use the repository in your controllers:");
            $this->line("   <comment>public function __construct(protected {$modelName}Repository \$repository) {}</comment>");
        }
        
        $this->line("3. Customize the repository methods as needed");
        $this->line("4. Add any additional business logic to your repository");
    }

    /**
     * Get the fully qualified model class name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = Str::studly($model);
        $rootNamespace = app()->getNamespace();
        return config('easy-dev.model_namespace', $rootNamespace . 'Models\\') . $model;
    }

    /**
     * Get repository interface file path.
     */
    protected function getRepositoryInterfacePath(string $interfaceName): string
    {
        $basePath = config('easy-dev.paths.repositories', app_path('Repositories'));
        return $basePath . '/Interfaces/' . $interfaceName . '.php';
    }

    /**
     * Get repository file path.
     */
    protected function getRepositoryPath(string $repositoryName): string
    {
        $basePath = config('easy-dev.paths.repositories', app_path('Repositories'));
        return $basePath . '/' . $repositoryName . '.php';
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of the model to generate repository for.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['without-interface', null, InputOption::VALUE_NONE, 'Generate repository without interface.'],
        ];
    }
}
