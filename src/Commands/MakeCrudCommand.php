<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Services\GenerationContext;
use AnasNashat\EasyDev\Services\RouteWriter;
use AnasNashat\EasyDev\Services\MigrationParser;
use AnasNashat\EasyDev\Services\ModelEnhancer;
use AnasNashat\EasyDev\Services\ServiceProviderManager;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeCrudCommand extends Command
{
    protected $name = 'easy-dev:crud';
    protected $description = 'Generate complete CRUD files with optional Repository and Service patterns.';

    public function __construct(
        protected FileGenerator $generator,
        protected GenerationContext $context,
        protected RouteWriter $routeWriter,
        protected MigrationParser $migrationParser,
        protected ModelEnhancer $modelEnhancer,
        protected ServiceProviderManager $serviceProviderManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = $this->argument('model');
        $withRepository = $this->option('with-repository') || config('easy-dev.defaults.with_repository', false);
        $withService = $this->option('with-service') || config('easy-dev.defaults.with_service', false);
        $withPolicy = $this->option('with-policy');
        $withDto = $this->option('with-dto');
        $withObserver = $this->option('with-observer');
        $apiOnly = $this->option('api-only');
        $webOnly = $this->option('web-only');
        $withoutInterface = $this->option('without-interface') || !config('easy-dev.defaults.with_interface', true);
        $dryRun = $this->option('dry-run');
        
        $isAiMode = $this->option('ai');
        $customPath = $this->option('path');
        $customStub = $this->option('stub');
        $module = $this->option('module');
        $preset = $this->option('preset');

        // Validation
        if ($apiOnly && $webOnly) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => 'Cannot specify both --api-only and --web-only options.'
                ]));
            } else {
                $this->error('Cannot specify both --api-only and --web-only options.');
            }
            return self::FAILURE;
        }

        // Context
        $this->context->reset();
        $this->context->setDryRun($dryRun);

        $generatedFiles = [];

        try {
            if ($dryRun) {
                if (!$isAiMode) {
                    $this->info("🔍 DRY RUN — Previewing CRUD generation for {$modelName}...");
                    $this->newLine();
                }
            } else {
                if (!$isAiMode) {
                    $this->info("Generating enhanced CRUD files for {$modelName}...");
                }
            }

            // 1. Parse migration data
            $migrationData = $this->parseMigrationData($modelName);

            if (!$dryRun) {
                // 2. Generate or enhance model
                $modelPath = $this->generator->resolveOutputPath('models', "{$modelName}.php", $customPath, $module, $preset);
                $shouldGenModel = true;
                
                if (file_exists($modelPath)) {
                    $this->modelEnhancer->enhanceModel($modelName, $migrationData);
                    if (!$isAiMode) {
                        $this->line("  ✓ Enhanced existing model: {$modelName}");
                    }
                    $shouldGenModel = false;
                } else {
                    $replacements = [
                        'class' => $modelName,
                        'table' => Str::snake(Str::plural($modelName)),
                    ];
                    $this->generator->generateFile($modelPath, 'model', $replacements, $customStub, $modelName, $customPath, $module, $preset);
                    if (!$isAiMode) {
                        $this->line("  ✓ Created model: {$modelName}");
                    }
                }

                $generatedFiles[] = [
                    'type' => 'model',
                    'name' => $modelName,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $modelPath),
                    'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('model', $customStub)),
                ];

                // 3. Generate migration if not exists
                if (!$this->migrationParser->migrationExists($modelName)) {
                    $tableName = Str::snake(Str::plural($modelName));
                    $migrationName = "create_{$tableName}_table";
                    $exitCode = $this->callSilent('make:migration', [
                        'name' => $migrationName,
                        '--create' => $tableName,
                    ]);

                    if ($exitCode === 0) {
                        if (!$isAiMode) {
                            $this->line("  ✓ Created migration: {$migrationName}");
                        }
                        
                        // Try to find the newly created migration file to return its path
                        $migrationsPath = database_path('migrations');
                        $migrationFile = '';
                        if (file_exists($migrationsPath)) {
                            $files = scandir($migrationsPath);
                            foreach ($files as $file) {
                                if (str_contains($file, "create_{$tableName}_table")) {
                                    $migrationFile = "database/migrations/{$file}";
                                    break;
                                }
                            }
                        }
                        
                        $generatedFiles[] = [
                            'type' => 'migration',
                            'name' => $migrationName,
                            'path' => $migrationFile ?: "database/migrations/*_create_{$tableName}_table.php",
                            'stub_used' => 'laravel:migration',
                        ];
                    }
                }

                // 4. Generate repository if requested
                if ($withRepository) {
                    $repoName = "{$modelName}Repository";
                    $repoPath = $this->generator->resolveOutputPath('repositories', "{$repoName}.php", $customPath, $module, $preset);

                    // Generate Repository Interface
                    if (!$withoutInterface) {
                        $interfaceName = "{$modelName}RepositoryInterface";
                        $interfacePath = $this->generator->resolveOutputPath(
                            'repository_contracts',
                            "{$interfaceName}.php",
                            $customPath ? rtrim($customPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null,
                            $module,
                            $preset
                        );

                        $replacements = [
                            'InterfaceName' => $interfaceName,
                            'ModelName' => $modelName,
                            'modelName' => Str::camel($modelName),
                        ];

                        $this->generator->generateFile($interfacePath, 'repository_interface', $replacements, $customStub, $modelName, $customPath, $module, $preset);
                        
                        $generatedFiles[] = [
                            'type' => 'repository_interface',
                            'name' => $interfaceName,
                            'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $interfacePath),
                            'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('repository_interface', $customStub)),
                        ];
                    }

                    // Generate Repository Implementation
                    $relationships = array_merge($migrationData['relationships'] ?? [], $this->migrationParser->findReverseRelationships($modelName));
                    $eagerLoad = $this->generateEagerLoadString($relationships);
                    $filterLogic = $this->generateFilterLogic($migrationData['fillable'] ?? []);

                    $interfaceNamespace = $this->generator->getNamespaceForType(
                        'repository_contracts',
                        $modelName,
                        $customPath ? rtrim($customPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null,
                        $module,
                        $preset
                    );

                    $replacements = [
                        'RepositoryName' => $repoName,
                        'ModelName' => $modelName,
                        'modelName' => Str::camel($modelName),
                        'InterfaceUse' => !$withoutInterface ? "use {$interfaceNamespace}\\{$modelName}RepositoryInterface;\n" : '',
                        'InterfaceImplements' => !$withoutInterface ? " implements {$modelName}RepositoryInterface" : '',
                        'eagerLoadRelationships' => $eagerLoad,
                        'filterLogic' => $filterLogic,
                    ];

                    $this->generator->generateFile($repoPath, 'repository', $replacements, $customStub, $modelName, $customPath, $module, $preset);
                    
                    if (!$isAiMode) {
                        $this->line("  ✓ Generated repository: {$repoName}");
                    }

                    $generatedFiles[] = [
                        'type' => 'repository',
                        'name' => $repoName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $repoPath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('repository', $customStub)),
                    ];
                }

                // 5. Generate service if requested
                if ($withService) {
                    $serviceName = "{$modelName}Service";
                    $servicePath = $this->generator->resolveOutputPath('services', "{$serviceName}.php", $customPath, $module, $preset);

                    // Generate Service Interface
                    if (!$withoutInterface) {
                        $interfaceName = "{$modelName}ServiceInterface";
                        $interfacePath = $this->generator->resolveOutputPath(
                            'service_contracts',
                            "{$interfaceName}.php",
                            $customPath ? rtrim($customPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null,
                            $module,
                            $preset
                        );

                        $replacements = [
                            'ServiceInterfaceName' => $interfaceName,
                            'ModelName' => $modelName,
                            'modelName' => Str::camel($modelName),
                        ];

                        $this->generator->generateFile($interfacePath, 'service_interface', $replacements, $customStub, $modelName, $customPath, $module, $preset);
                        
                        $generatedFiles[] = [
                            'type' => 'service_interface',
                            'name' => $interfaceName,
                            'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $interfacePath),
                            'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('service_interface', $customStub)),
                        ];
                    }

                    // Generate Service Implementation
                    $serviceInterfaceNamespace = $this->generator->getNamespaceForType(
                        'service_contracts',
                        $modelName,
                        $customPath ? rtrim($customPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null,
                        $module,
                        $preset
                    );
                    $repositoryInterfaceNamespace = $this->generator->getNamespaceForType(
                        'repository_contracts',
                        $modelName,
                        $customPath ? rtrim($customPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null,
                        $module,
                        $preset
                    );

                    $repositoryInterface = $withRepository ? "{$modelName}RepositoryInterface" : null;

                    $replacements = [
                        'ServiceName' => $serviceName,
                        'ModelName' => $modelName,
                        'modelName' => Str::camel($modelName),
                        'ServiceInterfaceUse' => !$withoutInterface ? "use {$serviceInterfaceNamespace}\\{$modelName}ServiceInterface;\n" : '',
                        'ServiceInterfaceImplements' => !$withoutInterface ? " implements {$modelName}ServiceInterface" : '',
                        'RepositoryInterfaceUse' => $withRepository && !$withoutInterface ? "use {$repositoryInterfaceNamespace}\\{$repositoryInterface};\n" : '',
                        'RepositoryDependency' => $withRepository ? "protected " . ($withoutInterface ? "{$modelName}Repository" : $repositoryInterface) . " \$repository" : '',
                    ];

                    $this->generator->generateFile($servicePath, 'service', $replacements, $customStub, $modelName, $customPath, $module, $preset);
                    
                    if (!$isAiMode) {
                        $this->line("  ✓ Generated service: {$serviceName}");
                    }

                    $generatedFiles[] = [
                        'type' => 'service',
                        'name' => $serviceName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $servicePath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('service', $customStub)),
                    ];
                }

                // 6. Generate controllers
                if (!$webOnly) {
                    // API Controller
                    $controllerName = "{$modelName}ApiController";
                    $controllerPath = $this->generator->resolveOutputPath('api_controllers', "{$controllerName}.php", $customPath, $module, $preset);
                    
                    $stub = $withService ? 'controller.api.service' : 'controller.api';
                    $namespace = $this->generator->getNamespaceForType('api_controllers', $modelName, $customPath, $module, $preset);
                    $replacements = $this->getControllerReplacements($modelName, $controllerName, $namespace, $withService, $migrationData, $customPath, $module, $withoutInterface, $preset);

                    $this->generator->generateFile($controllerPath, $stub, $replacements, $customStub, $modelName, $customPath, $module, $preset);
                    
                    if (!$isAiMode) {
                        $this->line("  ✓ Created API controller: {$controllerName}");
                    }

                    $generatedFiles[] = [
                        'type' => 'api_controller',
                        'name' => $controllerName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $controllerPath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath($stub, $customStub)),
                    ];
                }

                if (!$apiOnly) {
                    // Web Controller
                    $controllerName = "{$modelName}Controller";
                    $controllerPath = $this->generator->resolveOutputPath('controllers', "{$controllerName}.php", $customPath, $module, $preset);
                    
                    $stub = $withService ? 'controller.web.service' : 'controller';
                    $namespace = $this->generator->getNamespaceForType('controllers', $modelName, $customPath, $module, $preset);
                    $replacements = $this->getControllerReplacements($modelName, $controllerName, $namespace, $withService, $migrationData, $customPath, $module, $withoutInterface, $preset);

                    $this->generator->generateFile($controllerPath, $stub, $replacements, $customStub, $modelName, $customPath, $module, $preset);
                    
                    if (!$isAiMode) {
                        $this->line("  ✓ Created web controller: {$controllerName}");
                    }

                    $generatedFiles[] = [
                        'type' => 'controller',
                        'name' => $controllerName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $controllerPath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath($stub, $customStub)),
                    ];
                }

                // 7. Generate API resources
                if (!$webOnly) {
                    $resourceName = "{$modelName}Resource";
                    $resourcePath = $this->generator->resolveOutputPath('resources', "{$resourceName}.php", $customPath, $module, $preset);
                    
                    $replacements = [
                        'ModelName' => $modelName,
                        'ResourceName' => $resourceName,
                        'modelName' => Str::camel($modelName),
                    ];
                    $this->generator->generateFile($resourcePath, 'api_resource', $replacements, $customStub, $modelName, $customPath, $module, $preset);

                    $generatedFiles[] = [
                        'type' => 'api_resource',
                        'name' => $resourceName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $resourcePath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('api_resource', $customStub)),
                    ];

                    $collectionName = "{$modelName}Collection";
                    $collectionPath = $this->generator->resolveOutputPath('resources', "{$collectionName}.php", $customPath, $module, $preset);
                    
                    $collectionReplacements = [
                        'ModelName' => $modelName,
                        'CollectionName' => $collectionName,
                        'ResourceName' => $resourceName,
                        'modelName' => Str::camel($modelName),
                    ];
                    $this->generator->generateFile($collectionPath, 'api_collection', $collectionReplacements, $customStub, $modelName, $customPath, $module, $preset);

                    $generatedFiles[] = [
                        'type' => 'api_collection',
                        'name' => $collectionName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $collectionPath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath('api_collection', $customStub)),
                    ];
                }

                // 8. Generate Form Requests
                $requestNames = $this->generator->getRequestNamesFromModel($modelName);
                $requestPath = $this->generator->resolveOutputPath('requests', 'Dummy.php', $customPath, $module, $preset);
                $requestDir = dirname($requestPath);
                
                $validationRules = $this->migrationParser->generateValidationRules($migrationData['columns'] ?? []);

                foreach (['store', 'update'] as $type) {
                    $requestName = $requestNames[$type];
                    $requestFilePath = "{$requestDir}/{$requestName}.php";

                    $replacements = [
                        'class' => $requestName,
                        'model' => $modelName,
                        'modelVariable' => Str::camel($modelName),
                        'type' => $type,
                        'validationRules' => $this->formatValidationRules($validationRules, $type),
                        'customMessages' => $this->formatCustomMessages($validationRules),
                        'customAttributes' => $this->formatCustomAttributes($migrationData['fillable'] ?? []),
                    ];

                    $stub = !empty($validationRules) ? 'request.enhanced' : "request.{$type}";
                    $this->generator->generateFile($requestFilePath, $stub, $replacements, $customStub, $modelName, $customPath, $module, $preset);
                    
                    if (!$isAiMode) {
                        $this->line("  ✓ Created form request: {$requestName}");
                    }

                    $generatedFiles[] = [
                        'type' => 'request_' . $type,
                        'name' => $requestName,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $requestFilePath),
                        'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath($stub, $customStub)),
                    ];
                }

                // 9. Generate optional components (Policy, DTO, Observer)
                if ($withPolicy) {
                    $this->callSilent('easy-dev:policy', [
                        'model' => $modelName,
                        '--path' => $customPath,
                        '--stub' => $customStub,
                        '--module' => $module,
                        '--preset' => $preset,
                        '--ai' => true,
                    ]);
                    $generatedFiles[] = [
                        'type' => 'policy',
                        'name' => "{$modelName}Policy",
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->resolveOutputPath('policies', "{$modelName}Policy.php", $customPath, $module, $preset)),
                        'stub_used' => 'easy-dev:policy',
                    ];
                }

                if ($withDto) {
                    $this->callSilent('easy-dev:dto', [
                        'model' => $modelName,
                        '--path' => $customPath,
                        '--stub' => $customStub,
                        '--module' => $module,
                        '--preset' => $preset,
                        '--ai' => true,
                    ]);
                    $generatedFiles[] = [
                        'type' => 'dto',
                        'name' => "{$modelName}Data",
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->resolveOutputPath('dtos', "{$modelName}Data.php", $customPath, $module, $preset)),
                        'stub_used' => 'easy-dev:dto',
                    ];
                }

                if ($withObserver) {
                    $this->callSilent('easy-dev:observer', [
                        'model' => $modelName,
                        '--path' => $customPath,
                        '--stub' => $customStub,
                        '--module' => $module,
                        '--preset' => $preset,
                        '--ai' => true,
                    ]);
                    $generatedFiles[] = [
                        'type' => 'observer',
                        'name' => "{$modelName}Observer",
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->resolveOutputPath('observers', "{$modelName}Observer.php", $customPath, $module, $preset)),
                        'stub_used' => 'easy-dev:observer',
                    ];
                }

                // 10. Update bindings if RepositoryServiceProvider exists
                if ($withRepository || $withService) {
                    if (!$withoutInterface) {
                        $this->updateServiceProviderBindings($modelName, $withRepository, $withService, !$withoutInterface);
                    }
                }

                 // Clean backups
                 $this->context->cleanupBackups();

                 // Clean empty directories under module if using clean preset
                 if ($module && $preset === 'clean') {
                     $modulesRoot = config('easy-dev.modules.path', 'app/Modules');
                     $modulePath = base_path(rtrim($modulesRoot, '/\\') . DIRECTORY_SEPARATOR . Str::studly($module));
                     $this->scrubEmptyDirectories($modulePath);
                 }

                if ($isAiMode) {
                    $this->output->write(json_encode([
                        'status' => 'success',
                        'command' => 'easy-dev:crud',
                        'model' => $modelName,
                        'generated' => $generatedFiles,
                    ], JSON_PRETTY_PRINT));
                } else {
                    $this->showSuccessMessage($modelName, $withRepository, $withService, $apiOnly, $webOnly, $migrationData);
                }
            } else {
                // Dry run
                $this->showDryRunSummary($modelName, $withRepository, $withService, $withPolicy, $withDto, $withObserver, $apiOnly, $webOnly);
            }

        } catch (\Exception $e) {
            if (!$dryRun) {
                $this->context->rollback();
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        "Verify database table permissions and constraints.",
                        "Check write permissions on target generation directories.",
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->warn('⚠️  Generation failed — all changes have been rolled back.');
                $this->error($e->getMessage());
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Show dry-run summary.
     */
    protected function showDryRunSummary(string $modelName, bool $withRepository, bool $withService, bool $withPolicy, bool $withDto, bool $withObserver, bool $apiOnly, bool $webOnly): void
    {
        $this->line('<info>Files that would be created:</info>');
        $this->newLine();

        $this->line("  📄 app/Models/{$modelName}.php");

        $tableName = Str::snake(Str::plural($modelName));
        $this->line("  📄 database/migrations/*_create_{$tableName}_table.php");

        if ($withRepository) {
            $this->line("  📄 app/Repositories/{$modelName}Repository.php");
            $this->line("  📄 app/Repositories/Contracts/{$modelName}RepositoryInterface.php");
        }

        if ($withService) {
            $this->line("  📄 app/Services/{$modelName}Service.php");
            $this->line("  📄 app/Services/Contracts/{$modelName}ServiceInterface.php");
        }

        if (!$webOnly) {
            $this->line("  📄 app/Http/Controllers/Api/{$modelName}ApiController.php");
            $this->line("  📄 app/Http/Resources/{$modelName}Resource.php");
            $this->line("  📄 app/Http/Resources/{$modelName}Collection.php");
        }
        if (!$apiOnly) {
            $this->line("  📄 app/Http/Controllers/{$modelName}Controller.php");
        }

        $this->line("  📄 app/Http/Requests/Store{$modelName}Request.php");
        $this->line("  📄 app/Http/Requests/Update{$modelName}Request.php");

        if ($withPolicy) {
            $this->line("  📄 app/Policies/{$modelName}Policy.php");
        }
        if ($withDto) {
            $this->line("  📄 app/DTOs/{$modelName}Data.php");
        }
        if ($withObserver) {
            $this->line("  📄 app/Observers/{$modelName}Observer.php");
        }

        $this->newLine();
        $this->info('No files were created or modified (dry-run mode).');
    }

    /**
     * Parse migration data for the model.
     */
    protected function parseMigrationData(string $modelName): array
    {
        if ($this->migrationParser->migrationExists($modelName)) {
            $migrationPath = $this->migrationParser->getMigrationPath($modelName);
            return $this->migrationParser->parseMigration($migrationPath);
        }

        return [
            'columns' => [],
            'fillable' => [],
            'relationships' => []
        ];
    }

    /**
     * Get controller replacements.
     */
    protected function getControllerReplacements(string $modelName, string $controllerName, string $namespace, bool $withService, array $migrationData, ?string $explicitPath = null, ?string $module = null, bool $withoutInterface = false, ?string $preset = null): array
    {
        $modelClass = $this->generator->getNamespaceForType('models', $modelName, $explicitPath, $module, $preset) . '\\' . $modelName;
        $requestNames = $this->generator->getRequestNamesFromModel($modelName);
        
        $relationships = array_merge($migrationData['relationships'] ?? [], $this->migrationParser->findReverseRelationships($modelName));
        $withRelationships = $this->generateWithRelationshipsString($relationships);
        $filterableFields = $this->generateFilterableFieldsString($migrationData['fillable'] ?? []);

        $storeRequestClass = $this->generator->getNamespaceForType('requests', $modelName, $explicitPath, $module, $preset) . '\\' . $requestNames['store'];
        $updateRequestClass = $this->generator->getNamespaceForType('requests', $modelName, $explicitPath, $module, $preset) . '\\' . $requestNames['update'];

        $replacements = [
            'namespace' => $namespace,
            'class' => $controllerName,
            'model' => $modelName,
            'modelClass' => $modelClass,
            'ModelName' => $modelName,
            'modelVariable' => Str::camel($modelName),
            'modelVariablePlural' => Str::camel(Str::plural($modelName)),
            'storeRequest' => $requestNames['store'],
            'updateRequest' => $requestNames['update'],
            'storeRequestClass' => $storeRequestClass,
            'updateRequestClass' => $updateRequestClass,
            'resourceName' => Str::kebab(Str::plural($modelName)),
            'withRelationships' => $withRelationships,
            'filterableFields' => $filterableFields,
        ];

        if ($withService) {
            $serviceInterface = $withoutInterface ? "{$modelName}Service" : "{$modelName}ServiceInterface";
            $serviceNamespace = $this->generator->getNamespaceForType($withoutInterface ? 'services' : 'service_contracts', $modelName, $explicitPath, $module, $preset);
            
            $replacements['ServiceInterfaceUse'] = "use {$serviceNamespace}\\{$serviceInterface};\n";
            $replacements['ServiceDependency'] = "protected {$serviceInterface} \$service";
        }

        return $replacements;
    }

    /**
     * Update service provider bindings.
     */
    protected function updateServiceProviderBindings(string $modelName, bool $withRepository, bool $withService, bool $withInterface): void
    {
        $module = $this->option('module');
        $preset = $this->option('preset');

        try {
            // First reorganize existing providers and module.json under DDD module if clean preset is used
            if ($module && $preset === 'clean') {
                $this->serviceProviderManager->reorganizeModuleProviders($module, $preset);
            }

            if ($withRepository && $withInterface) {
                $this->serviceProviderManager->addRepositoryBinding($modelName, $module, $preset);
            }

            if ($withService && $withInterface) {
                $this->serviceProviderManager->addServiceBinding($modelName, $module, $preset);
            }
        } catch (\Exception $e) {
            // Silence binding errors or alert user, to ensure smooth modular experience
            $this->line("  ⚠️  Could not automatically update Service Provider bindings: " . $e->getMessage());
        }
    }

    /**
     * Show success message with details.
     */
    protected function showSuccessMessage(string $modelName, bool $withRepository, bool $withService, bool $apiOnly, bool $webOnly, array $migrationData): void
    {
        $this->newLine();
        $this->info("✅ CRUD generation completed successfully!");
        
        $this->newLine();
        $this->line('<info>Next steps:</info>');
        $this->line('- Run: <comment>php artisan route:list</comment> to verify routes');
        $this->line('- Review generated validation rules in request classes');
        $this->line('- Customize business logic in service classes');
    }

    protected function generateEagerLoadString(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }
        $methods = array_map(fn($rel) => "'{$rel['method_name']}'", $relationships);
        return implode(', ', $methods);
    }

    protected function generateWithRelationshipsString(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }
        $methods = array_map(fn($rel) => "'{$rel['method_name']}'", $relationships);
        return '->load([' . implode(', ', $methods) . '])';
    }

    protected function generateFilterableFieldsString(array $fillable): string
    {
        if (empty($fillable)) {
            return "'search'";
        }
        $fields = array_map(fn($field) => "'{$field}'", array_slice($fillable, 0, 5));
        $fields[] = "'search'";
        return implode(', ', $fields);
    }

    protected function generateFilterLogic(array $fillable): string
    {
        if (empty($fillable)) {
            return '// Add custom filters here';
        }
        $logic = [];
        foreach (array_slice($fillable, 0, 3) as $field) {
            $logic[] = "        if (!empty(\$filters['{$field}'])) {\n            \$query->where('{$field}', \$filters['{$field}']);\n        }";
        }
        $logic[] = "        if (!empty(\$filters['search'])) {\n            \$query->where('name', 'like', \"%{\$filters['search']}%\");\n        }";
        return implode("\n\n", $logic);
    }

    protected function formatValidationRules(array $rules, string $type): string
    {
        if (empty($rules)) {
            return '';
        }
        $formatted = [];
        foreach ($rules as $field => $fieldRules) {
            if ($type === 'update') {
                $fieldRules = array_map(function ($rule) {
                    if (str_starts_with($rule, 'unique:')) {
                        return $rule . ',{$this->route(\'id\')}';
                    }
                    return $rule;
                }, $fieldRules);
            }
            $rulesString = "['" . implode("', '", $fieldRules) . "']";
            $formatted[] = "            '{$field}' => {$rulesString}";
        }
        return implode(",\n", $formatted);
    }

    protected function formatCustomMessages(array $rules): string
    {
        if (empty($rules)) {
            return '';
        }
        $messages = [];
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $ruleType = explode(':', $rule)[0];
                $key = "{$field}.{$ruleType}";
                $messages[] = "            '{$key}' => 'Please provide a valid {$field}.'";
            }
        }
        return implode(",\n", array_slice($messages, 0, 5));
    }

    protected function formatCustomAttributes(array $fillable): string
    {
        if (empty($fillable)) {
            return '';
        }
        $attributes = [];
        foreach (array_slice($fillable, 0, 5) as $field) {
            $label = Str::title(str_replace('_', ' ', $field));
            $attributes[] = "            '{$field}' => '{$label}'";
        }
        return implode(",\n", $attributes);
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of the model to generate CRUD for.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['api', null, InputOption::VALUE_NONE, 'Generate API controller instead of web controller.'],
            ['with-repository', null, InputOption::VALUE_NONE, 'Generate repository pattern with interfaces.'],
            ['with-service', null, InputOption::VALUE_NONE, 'Generate service layer with business logic.'],
            ['with-policy', null, InputOption::VALUE_NONE, 'Generate authorization policy for the model.'],
            ['with-dto', null, InputOption::VALUE_NONE, 'Generate Data Transfer Object for the model.'],
            ['with-observer', null, InputOption::VALUE_NONE, 'Generate model observer.'],
            ['api-only', null, InputOption::VALUE_NONE, 'Generate only API controllers.'],
            ['web-only', null, InputOption::VALUE_NONE, 'Generate only web controllers.'],
            ['without-interface', null, InputOption::VALUE_NONE, 'Skip interface generation for repositories and services.'],
            ['dry-run', null, InputOption::VALUE_NONE, 'Preview what files would be generated without creating them.'],
            ['stub', null, InputOption::VALUE_OPTIONAL, 'Override model stub template or absolute/relative file path.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Override default output directory path.'],
            ['module', null, InputOption::VALUE_OPTIONAL, 'Nest generated files inside a modular layout.'],
            ['preset', null, InputOption::VALUE_OPTIONAL, 'Use a pre-configured architecture preset (e.g. clean).'],
            ['ai', null, InputOption::VALUE_NONE, 'Silent machine-friendly JSON output for AI integration.'],
        ];
    }

    public function line($string, $style = null, $verbosity = null)
    {
        if ($this->option('ai')) return;
        parent::line($string, $style, $verbosity);
    }

    public function info($string, $verbosity = null)
    {
        if ($this->option('ai')) return;
        parent::info($string, $verbosity);
    }

    public function warn($string, $verbosity = null)
    {
        if ($this->option('ai')) return;
        parent::warn($string, $verbosity);
    }

    public function error($string, $verbosity = null)
    {
        if ($this->option('ai')) return;
        parent::error($string, $verbosity);
    }

    public function comment($string, $verbosity = null)
    {
        if ($this->option('ai')) return;
        parent::comment($string, $verbosity);
    }

    public function newLine($count = 1)
    {
        if ($this->option('ai')) return;
        parent::newLine($count);
    }

    /**
     * Recursively scrubs empty directories under a given path bottom-up.
     */
    protected function scrubEmptyDirectories(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->scrubEmptyDirectories($path);
            }
        }

        // Re-read after potential subfolder cleanups
        $items = scandir($dir);
        $empty = true;
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $empty = false;
                break;
            }
        }

        if ($empty) {
            @rmdir($dir);
        }
    }
}
