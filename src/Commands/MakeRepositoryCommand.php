<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use AnasNashat\EasyDev\Services\RelationDetector;

class MakeRepositoryCommand extends Command
{
    protected $name = 'easy-dev:repository';
    protected $description = 'Generate repository pattern files (Interface and Implementation) for a model.';

    public function __construct(protected FileGenerator $generator, protected RelationDetector $relationDetector)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = $this->argument('model');
        $withInterface = !$this->option('without-interface');
        $isAiMode = $this->option('ai');
        $preset = $this->option('preset');

        $generatedFiles = [];

        try {
            $modelClass = $this->qualifyModel($modelName);

            // Check if model exists
            if (!class_exists($modelClass)) {
                if ($isAiMode) {
                    $this->output->write(json_encode([
                        'status' => 'error',
                        'message' => "Model {$modelName} not found. Please create it first.",
                        'suggestions' => [
                            "Create the model using: php artisan easy-dev:crud {$modelName}",
                            "Or run: php artisan easy-dev:dream \"Create {$modelName} ...\"",
                        ]
                    ], JSON_PRETTY_PRINT));
                } else {
                    $this->error("Model {$modelName} not found. Please create it first.");
                }
                return self::FAILURE;
            }

            if (!$isAiMode) {
                $this->info("Generating repository pattern for {$modelName}...");
            }

            // Generate interface
            if ($withInterface) {
                $interfaceName = "{$modelName}RepositoryInterface";
                
                // Resolve path using upgraded resolveOutputPath
                $customPath = $this->option('path');
                $interfacePath = $this->generator->resolveOutputPath(
                    'repository_contracts',
                    "{$interfaceName}.php",
                    $customPath ? rtrim($customPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null,
                    $this->option('module'),
                    $preset
                );

                // Overwrite protection
                $shouldGenerate = true;
                if (file_exists($interfacePath)) {
                    if (!$isAiMode) {
                        if (!$this->confirm("Interface {$interfaceName} already exists. Overwrite?")) {
                            $this->line("  Skipped interface generation.");
                            $shouldGenerate = false;
                        }
                    }
                }

                if ($shouldGenerate) {
                    $replacements = [
                        'ModelName' => $modelName,
                        'InterfaceName' => $interfaceName,
                        'modelName' => Str::camel($modelName),
                    ];

                    $this->generator->generateFile($interfacePath, 'repository_interface', $replacements, $this->option('stub'), $modelName, $customPath, $this->option('module'), $preset);
                    
                    if (!$isAiMode) {
                        $this->info("  ✓ Generated interface: {$interfaceName}");
                    }
                    
                    $generatedFiles[] = [
                        'type' => 'interface',
                        'name' => $interfaceName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $interfacePath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('repository_interface', $this->option('stub'))),
                    ];
                }
            }

            // Generate repository implementation
            $repositoryName = "{$modelName}Repository";
            $repositoryPath = $this->generator->resolveOutputPath(
                'repositories',
                "{$repositoryName}.php",
                $this->option('path'),
                $this->option('module'),
                $preset
            );

            // Overwrite protection
            $shouldGenerateRepo = true;
            if (file_exists($repositoryPath)) {
                if (!$isAiMode) {
                    if (!$this->confirm("Repository {$repositoryName} already exists. Overwrite?")) {
                        $this->line("  Skipped repository implementation generation.");
                        $shouldGenerateRepo = false;
                    }
                }
            }

            if ($shouldGenerateRepo) {
                // Determine interface namespace using FileGenerator helper
                $interfaceNamespace = $this->generator->getNamespaceForType(
                    'repository_contracts',
                    $modelName,
                    $this->option('path') ? rtrim($this->option('path'), '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null,
                    $this->option('module'),
                    $preset
                );

                $replacements = [
                    'ModelName' => $modelName,
                    'RepositoryName' => $repositoryName,
                    'InterfaceName' => $withInterface ? "{$modelName}RepositoryInterface" : '',
                    'modelName' => Str::camel($modelName),
                    'WithInterface' => $withInterface,
                    'InterfaceUse' => $withInterface ? "use {$interfaceNamespace}\\{$modelName}RepositoryInterface;\n" : '',
                    'InterfaceImplements' => $withInterface ? " implements {$modelName}RepositoryInterface" : '',
                ];

                $this->generator->generateFile($repositoryPath, 'repository', $replacements, $this->option('stub'), $modelName, $this->option('path'), $this->option('module'), $preset);
                
                if (!$isAiMode) {
                    $this->info("  ✓ Generated repository: {$repositoryName}");
                }

                $generatedFiles[] = [
                    'type' => 'repository',
                    'name' => $repositoryName,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $repositoryPath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('repository', $this->option('stub'))),
                ];
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:repository',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->info("✓ Repository pattern generated successfully for {$modelName}!");
                $this->showNextSteps($modelName, $withInterface);
            }

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        "Ensure the model is imported correctly and spelled accurately.",
                        "Check write permissions in your project directory.",
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("Error generating repository: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
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

    protected function qualifyModel(string $model): string
    {
        return $this->relationDetector->qualifyModel($model);
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
            ['stub', null, InputOption::VALUE_OPTIONAL, 'Override stub template name or absolute/relative file path.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Override the output directory path.'],
            ['module', null, InputOption::VALUE_OPTIONAL, 'Generate inside a domain module directory.'],
            ['preset', null, InputOption::VALUE_OPTIONAL, 'Use a pre-configured architecture preset (e.g. clean).'],
            ['ai', null, InputOption::VALUE_NONE, 'Output machine-friendly JSON format for AI integration.'],
        ];
    }
}
