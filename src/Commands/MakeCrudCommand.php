<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\FileGenerator;
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
        $withRepository = $this->option('with-repository');
        $withService = $this->option('with-service');
        $apiOnly = $this->option('api-only');
        $webOnly = $this->option('web-only');
        $withoutInterface = $this->option('without-interface');
        
        // Validate options
        if ($apiOnly && $webOnly) {
            $this->error('Cannot specify both --api-only and --web-only options.');
            return self::FAILURE;
        }

        $isApi = $apiOnly || (!$webOnly && $this->option('api'));
        
        try {
            $this->info("Generating enhanced CRUD files for {$modelName}...");

            // Check and parse migration
            $migrationData = $this->parseMigrationData($modelName);

            // Generate or enhance model
            $this->generateOrEnhanceModel($modelName, $migrationData);

            // Generate migration if not exists
            if (!$this->migrationParser->migrationExists($modelName)) {
                $this->generateMigration($modelName);
            } else {
                $this->line("  • Migration for {$modelName} already exists, skipping...");
            }

            // Generate repository if requested
            if ($withRepository) {
                $this->generateRepository($modelName, !$withoutInterface, $migrationData);
            }

            // Generate service if requested
            if ($withService) {
                $this->generateService($modelName, $withRepository, !$withoutInterface);
            }

            // Generate controllers
            $this->generateControllers($modelName, $apiOnly, $webOnly, $withService, $migrationData);

            // Generate API resources for API controllers
            if (!$webOnly) {
                $this->generateApiResources($modelName);
            }

            // Generate form requests
            $this->generateFormRequests($modelName, $migrationData);

            // Generate routes
            $this->generateRoutes($modelName, $apiOnly, $webOnly);

            // Update service provider bindings
            if ($withRepository || $withService) {
                $this->updateServiceProviderBindings($modelName, $withRepository, $withService, !$withoutInterface);
            }

            $this->showSuccessMessage($modelName, $withRepository, $withService, $apiOnly, $webOnly, $migrationData);

        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
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
     * Generate or enhance model.
     */
    protected function generateOrEnhanceModel(string $modelName, array $migrationData): void
    {
        $modelPath = app_path("Models/{$modelName}.php");
        
        if (file_exists($modelPath)) {
            // Enhance existing model
            $this->modelEnhancer->enhanceModel($modelName, $migrationData);
            $this->line("  ✓ Enhanced existing model: {$modelName}");
        } else {
            // Generate new model
            $this->generateModel($modelName);
        }
    }

    /**
     * Generate repository pattern files.
     */
    protected function generateRepository(string $modelName, bool $withInterface, array $migrationData): void
    {
        $repositoryName = "{$modelName}Repository";
        $interfaceName = "{$modelName}RepositoryInterface";
        
        // Generate interface
        if ($withInterface) {
            $this->generateRepositoryInterface($modelName, $interfaceName, $migrationData);
        }

        // Generate repository implementation
        $this->generateRepositoryImplementation($modelName, $repositoryName, $withInterface, $migrationData);
        
        $this->line("  ✓ Generated repository pattern for {$modelName}");
    }

    /**
     * Generate service layer files.
     */
    protected function generateService(string $modelName, bool $withRepository, bool $withInterface): void
    {
        $serviceName = "{$modelName}Service";
        $serviceInterfaceName = "{$modelName}ServiceInterface";
        
        // Generate service interface
        if ($withInterface) {
            $this->generateServiceInterface($modelName, $serviceInterfaceName);
        }

        // Generate service implementation
        $this->generateServiceImplementation($modelName, $serviceName, $withRepository, $withInterface);
        
        $this->line("  ✓ Generated service layer for {$modelName}");
    }

    /**
     * Generate controllers based on options.
     */
    protected function generateControllers(string $modelName, bool $apiOnly, bool $webOnly, bool $withService, array $migrationData): void
    {
        if (!$webOnly) {
            // Generate API controller
            $this->generateApiController($modelName, $withService, $migrationData);
        }

        if (!$apiOnly) {
            // Generate web controller
            $this->generateWebController($modelName, $withService, $migrationData);
        }
    }

    /**
     * Generate repository interface.
     */
    protected function generateRepositoryInterface(string $modelName, string $interfaceName, array $migrationData): void
    {
        $interfacePath = config('easy-dev.paths.repositories', app_path('Repositories')) . "/Contracts/{$interfaceName}.php";
        
        $replacements = [
            'InterfaceName' => $interfaceName,
            'ModelName' => $modelName,
            'modelName' => Str::camel($modelName),
        ];

        $this->generator->generateFile($interfacePath, 'repository.interface.enhanced', $replacements);
    }

    /**
     * Generate repository implementation.
     */
    protected function generateRepositoryImplementation(string $modelName, string $repositoryName, bool $withInterface, array $migrationData): void
    {
        $repositoryPath = config('easy-dev.paths.repositories', app_path('Repositories')) . "/{$repositoryName}.php";
        
        // Generate relationships for eager loading
        $relationships = array_merge($migrationData['relationships'] ?? [], $this->migrationParser->findReverseRelationships($modelName));
        $eagerLoadRelationships = $this->generateEagerLoadString($relationships);
        $filterLogic = $this->generateFilterLogic($migrationData['fillable'] ?? []);

        $replacements = [
            'RepositoryName' => $repositoryName,
            'ModelName' => $modelName,
            'modelName' => Str::camel($modelName),
            'InterfaceUse' => $withInterface ? "use App\\Repositories\\Contracts\\{$modelName}RepositoryInterface;\n" : '',
            'InterfaceImplements' => $withInterface ? " implements {$modelName}RepositoryInterface" : '',
            'eagerLoadRelationships' => $eagerLoadRelationships,
            'filterLogic' => $filterLogic,
        ];

        $this->generator->generateFile($repositoryPath, 'repository.enhanced', $replacements);
    }

    /**
     * Generate service interface.
     */
    protected function generateServiceInterface(string $modelName, string $serviceInterfaceName): void
    {
        $servicePath = config('easy-dev.paths.services', app_path('Services')) . "/Contracts/{$serviceInterfaceName}.php";
        
        $replacements = [
            'ServiceInterfaceName' => $serviceInterfaceName,
            'ModelName' => $modelName,
            'modelName' => Str::camel($modelName),
        ];

        $this->generator->generateFile($servicePath, 'service.interface', $replacements);
    }

    /**
     * Generate service implementation.
     */
    protected function generateServiceImplementation(string $modelName, string $serviceName, bool $withRepository, bool $withInterface): void
    {
        $servicePath = config('easy-dev.paths.services', app_path('Services')) . "/{$serviceName}.php";
        
        $repositoryInterface = $withRepository ? "{$modelName}RepositoryInterface" : null;

        $replacements = [
            'ServiceName' => $serviceName,
            'ModelName' => $modelName,
            'modelName' => Str::camel($modelName),
            'ServiceInterfaceUse' => $withInterface ? "use App\\Services\\Contracts\\{$modelName}ServiceInterface;\n" : '',
            'ServiceInterfaceImplements' => $withInterface ? " implements {$modelName}ServiceInterface" : '',
            'RepositoryInterfaceUse' => $withRepository ? "use App\\Repositories\\Contracts\\{$repositoryInterface};\n" : '',
            'RepositoryDependency' => $withRepository ? "protected {$repositoryInterface} \$repository" : '',
        ];

        $this->generator->generateFile($servicePath, 'service', $replacements);
    }

    /**
     * Generate API controller.
     */
    protected function generateApiController(string $modelName, bool $withService, array $migrationData): void
    {
        $controllerName = "{$modelName}ApiController";
        $controllerPath = app_path("Http/Controllers/Api/{$controllerName}.php");
        
        $stub = $withService ? 'controller.api.service' : 'controller.api';
        $replacements = $this->getControllerReplacements($modelName, $controllerName, 'App\\Http\\Controllers\\Api', $withService, $migrationData);

        $this->generator->generateFile($controllerPath, $stub, $replacements);
        $this->line("  ✓ Created API controller: {$controllerName}");
    }

    /**
     * Generate web controller.
     */
    protected function generateWebController(string $modelName, bool $withService, array $migrationData): void
    {
        $controllerName = "{$modelName}Controller";
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");
        
        $stub = $withService ? 'controller.web.service' : 'controller';
        $replacements = $this->getControllerReplacements($modelName, $controllerName, 'App\\Http\\Controllers', $withService, $migrationData);

        $this->generator->generateFile($controllerPath, $stub, $replacements);
        $this->line("  ✓ Created web controller: {$controllerName}");
    }

    /**
     * Generate API resources for the model.
     */
    protected function generateApiResources(string $modelName): void
    {
        // Generate resource
        $resourceName = "{$modelName}Resource";
        $resourcePath = app_path("Http/Resources/{$resourceName}.php");
        
        $replacements = [
            'ModelName' => $modelName,
            'ResourceName' => $resourceName,
            'modelName' => Str::camel($modelName),
        ];

        $this->generator->generateFile($resourcePath, 'api.resource', $replacements);
        $this->line("  ✓ Created API resource: {$resourceName}");

        // Generate collection
        $collectionName = "{$modelName}Collection";
        $collectionPath = app_path("Http/Resources/{$collectionName}.php");
        
        $collectionReplacements = [
            'ModelName' => $modelName,
            'CollectionName' => $collectionName,
            'ResourceName' => $resourceName,
            'modelName' => Str::camel($modelName),
        ];

        $this->generator->generateFile($collectionPath, 'api.collection', $collectionReplacements);
        $this->line("  ✓ Created API collection: {$collectionName}");
    }

    /**
     * Get controller replacements.
     */
    protected function getControllerReplacements(string $modelName, string $controllerName, string $namespace, bool $withService, array $migrationData): array
    {
        $modelClass = $this->qualifyModel($modelName);
        $requestNames = $this->generator->getRequestNamesFromModel($modelName);
        
        // Generate relationships for with() loading
        $relationships = array_merge($migrationData['relationships'] ?? [], $this->migrationParser->findReverseRelationships($modelName));
        $withRelationships = $this->generateWithRelationshipsString($relationships);
        $filterableFields = $this->generateFilterableFieldsString($migrationData['fillable'] ?? []);

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
            'resourceName' => Str::kebab(Str::plural($modelName)),
            'withRelationships' => $withRelationships,
            'filterableFields' => $filterableFields,
        ];

        if ($withService) {
            $serviceInterface = "{$modelName}ServiceInterface";
            $replacements['ServiceInterfaceUse'] = "use App\\Services\\Contracts\\{$serviceInterface};\n";
            $replacements['ServiceDependency'] = "protected {$serviceInterface} \$service";
        }

        return $replacements;
    }
    protected function generateController(string $modelName, bool $isApi): void
    {
        $controllerName = $this->generator->getControllerNameFromModel($modelName);
        $namespace = $isApi ? 'App\\Http\\Controllers\\Api' : 'App\\Http\\Controllers';
        $controllerPath = $isApi 
            ? app_path("Http/Controllers/Api/{$controllerName}.php")
            : app_path("Http/Controllers/{$controllerName}.php");

        $stub = $isApi ? 'controller.api' : 'controller';
        $modelClass = $this->qualifyModel($modelName);
        $requestNames = $this->generator->getRequestNamesFromModel($modelName);

        $replacements = [
            'namespace' => $namespace,
            'class' => $controllerName,
            'model' => $modelName,
            'modelClass' => $modelClass,
            'modelVariable' => Str::camel($modelName),
            'modelVariablePlural' => Str::camel(Str::plural($modelName)),
            'storeRequest' => $requestNames['store'],
            'updateRequest' => $requestNames['update'],
            'resourceName' => Str::kebab(Str::plural($modelName)),
        ];

        $this->generator->generateFile($controllerPath, $stub, $replacements);
        $this->line("  ✓ Created controller: {$controllerName}");
    }

    /**
     * Generate the model file.
     */
    protected function generateModel(string $modelName): void
    {
        $modelPath = app_path("Models/{$modelName}.php");
        
        // Check if model already exists
        if (file_exists($modelPath)) {
            $this->line("  • Model {$modelName} already exists, skipping...");
            return;
        }

        $replacements = [
            'namespace' => 'App\\Models',
            'class' => $modelName,
            'table' => Str::snake(Str::plural($modelName)),
        ];

        $this->generator->generateFile($modelPath, 'model', $replacements);
        $this->line("  ✓ Created model: {$modelName}");
    }

    /**
     * Generate the migration file.
     */
    protected function generateMigration(string $modelName): void
    {
        $tableName = Str::snake(Str::plural($modelName));
        $migrationName = "create_{$tableName}_table";
        
        // Use Laravel's built-in migration command
        $exitCode = $this->call('make:migration', [
            'name' => $migrationName,
            '--create' => $tableName,
        ]);

        if ($exitCode === 0) {
            $this->line("  ✓ Created migration: {$migrationName}");
        } else {
            $this->warn("  • Migration may already exist or failed to create");
        }
    }

    /**
     * Generate form request files.
     */
    protected function generateFormRequests(string $modelName, array $migrationData): void
    {
        $requestNames = $this->generator->getRequestNamesFromModel($modelName);
        $requestPath = config('easy-dev.paths.requests', app_path('Http/Requests'));

        // Generate validation rules based on migration
        $validationRules = $this->migrationParser->generateValidationRules($migrationData['columns'] ?? []);

        foreach (['store', 'update'] as $type) {
            $requestName = $requestNames[$type];
            $requestFilePath = "{$requestPath}/{$requestName}.php";

            $replacements = [
                'namespace' => 'App\\Http\\Requests',
                'class' => $requestName,
                'model' => $modelName,
                'modelVariable' => Str::camel($modelName),
                'type' => $type,
                'validationRules' => $this->formatValidationRules($validationRules, $type),
                'customMessages' => $this->formatCustomMessages($validationRules),
                'customAttributes' => $this->formatCustomAttributes($migrationData['fillable'] ?? []),
            ];

            $stub = !empty($validationRules) ? 'request.enhanced' : "request.{$type}";
            $this->generator->generateFile($requestFilePath, $stub, $replacements);
            $this->line("  ✓ Created form request: {$requestName}");
        }
    }

    /**
     * Show what files were generated.
     */
    protected function showGeneratedFiles(string $modelName, bool $isApi): void
    {
        $this->info("\nGenerated files:");
        
        $controllerName = $this->generator->getControllerNameFromModel($modelName);
        $requestNames = $this->generator->getRequestNamesFromModel($modelName);
        $resourceName = Str::kebab(Str::plural($modelName));
        $tableName = Str::snake(Str::plural($modelName));

        // Model
        $this->line("  • app/Models/{$modelName}.php");
        
        // Migration
        $this->line("  • database/migrations/*_create_{$tableName}_table.php");

        // Controller
        if ($isApi) {
            $this->line("  • app/Http/Controllers/Api/{$controllerName}.php");
        } else {
            $this->line("  • app/Http/Controllers/{$controllerName}.php");
        }

        // Requests
        $this->line("  • app/Http/Requests/{$requestNames['store']}.php");
        $this->line("  • app/Http/Requests/{$requestNames['update']}.php");
        
        // Routes
        $routeFile = $isApi ? 'routes/api.php' : 'routes/web.php';
        $this->line("  • {$routeFile} (added resource routes)");

        if ($isApi) {
            $this->info("\nAPI Routes added:");
            $this->line("  GET    /api/{$resourceName}");
            $this->line("  POST   /api/{$resourceName}");
            $this->line("  GET    /api/{$resourceName}/{id}");
            $this->line("  PUT    /api/{$resourceName}/{id}");
            $this->line("  DELETE /api/{$resourceName}/{id}");
        } else {
            $this->info("\nWeb Routes added:");
            $this->line("  GET    /{$resourceName}");
            $this->line("  POST   /{$resourceName}");
            $this->line("  GET    /{$resourceName}/create");
            $this->line("  GET    /{$resourceName}/{id}");
            $this->line("  GET    /{$resourceName}/{id}/edit");
            $this->line("  PUT    /{$resourceName}/{id}");
            $this->line("  DELETE /{$resourceName}/{id}");
        }
    }

    /**
     * Get the fully qualified model class name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = Str::studly($model);
        $rootNamespace = app()->getNamespace();
        $modelClass = config('easy-dev.model_namespace', $rootNamespace . 'Models\\') . $model;

        return $modelClass;
    }

    /**
     * Generate eager load relationships string.
     */
    protected function generateEagerLoadString(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }

        $methods = array_map(function ($rel) {
            return "'{$rel['method_name']}'";
        }, $relationships);

        return implode(', ', $methods);
    }

    /**
     * Generate with relationships string for loading.
     */
    protected function generateWithRelationshipsString(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }

        $methods = array_map(function ($rel) {
            return "'{$rel['method_name']}'";
        }, $relationships);

        return '->load([' . implode(', ', $methods) . '])';
    }

    /**
     * Generate filterable fields string.
     */
    protected function generateFilterableFieldsString(array $fillable): string
    {
        if (empty($fillable)) {
            return "'search'";
        }

        $fields = array_map(function ($field) {
            return "'{$field}'";
        }, array_slice($fillable, 0, 5)); // Limit to first 5 fields

        $fields[] = "'search'";
        return implode(', ', $fields);
    }

    /**
     * Generate filter logic for repository.
     */
    protected function generateFilterLogic(array $fillable): string
    {
        if (empty($fillable)) {
            return '// Add custom filters here';
        }

        $logic = [];
        foreach (array_slice($fillable, 0, 3) as $field) { // Limit to first 3 fields
            $logic[] = "        if (!empty(\$filters['{$field}'])) {\n            \$query->where('{$field}', \$filters['{$field}']);\n        }";
        }

        $logic[] = "        if (!empty(\$filters['search'])) {\n            \$query->where('name', 'like', \"%{\$filters['search']}%\");\n        }";

        return implode("\n\n", $logic);
    }

    /**
     * Format validation rules for request.
     */
    protected function formatValidationRules(array $rules, string $type): string
    {
        if (empty($rules)) {
            return '';
        }

        $formatted = [];
        foreach ($rules as $field => $fieldRules) {
            // For update requests, modify unique rules to ignore current record
            if ($type === 'update') {
                $fieldRules = array_map(function ($rule) use ($field) {
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

    /**
     * Format custom messages for validation.
     */
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

        return implode(",\n", array_slice($messages, 0, 5)); // Limit messages
    }

    /**
     * Format custom attributes for validation.
     */
    protected function formatCustomAttributes(array $fillable): string
    {
        if (empty($fillable)) {
            return '';
        }

        $attributes = [];
        foreach (array_slice($fillable, 0, 5) as $field) { // Limit to first 5
            $label = Str::title(str_replace('_', ' ', $field));
            $attributes[] = "            '{$field}' => '{$label}'";
        }

        return implode(",\n", $attributes);
    }

    /**
     * Update service provider bindings.
     */
    protected function updateServiceProviderBindings(string $modelName, bool $withRepository, bool $withService, bool $withInterface): void
    {
        if ($withRepository && $withInterface) {
            $this->serviceProviderManager->addRepositoryBinding($modelName);
        }

        if ($withService && $withInterface) {
            $this->serviceProviderManager->addServiceBinding($modelName);
        }

        $this->line("  ✓ Updated service provider bindings");
    }

    /**
     * Generate routes based on options.
     */
    protected function generateRoutes(string $modelName, bool $apiOnly, bool $webOnly): void
    {
        if (!$webOnly) {
            $this->routeWriter->addResourceRoutes($modelName, true); // API routes
            $this->line("  ✓ Added API routes for {$modelName}");
        }

        if (!$apiOnly) {
            $this->routeWriter->addResourceRoutes($modelName, false); // Web routes
            $this->line("  ✓ Added web routes for {$modelName}");
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
        $this->line('<info>Generated files:</info>');
        
        // Model
        $this->line("├── app/Models/{$modelName}.php " . (!empty($migrationData['fillable']) ? '(enhanced)' : ''));
        
        // Migration
        if (!$this->migrationParser->migrationExists($modelName)) {
            $tableName = Str::snake(Str::plural($modelName));
            $this->line("├── database/migrations/*_create_{$tableName}_table.php");
        }

        // Repository
        if ($withRepository) {
            $this->line("├── app/Repositories/{$modelName}Repository.php");
            $this->line("├── app/Repositories/Contracts/{$modelName}RepositoryInterface.php");
        }

        // Service
        if ($withService) {
            $this->line("├── app/Services/{$modelName}Service.php");
            $this->line("├── app/Services/Contracts/{$modelName}ServiceInterface.php");
        }

        // Controllers
        if (!$webOnly) {
            $this->line("├── app/Http/Controllers/Api/{$modelName}ApiController.php");
        }
        if (!$apiOnly) {
            $this->line("├── app/Http/Controllers/{$modelName}Controller.php");
        }

        // Requests
        $requestNames = $this->generator->getRequestNamesFromModel($modelName);
        $this->line("├── app/Http/Requests/{$requestNames['store']}.php");
        $this->line("└── app/Http/Requests/{$requestNames['update']}.php");

        // Additional info
        if (!empty($migrationData['relationships'])) {
            $relationships = array_column($migrationData['relationships'], 'method_name');
            $this->newLine();
            $this->line('<info>✅ Detected relationships:</info> ' . implode(', ', $relationships));
        }

        if (!empty($migrationData['fillable'])) {
            $this->line('<info>✅ Auto-populated fillable:</info> ' . implode(', ', $migrationData['fillable']));
        }

        if ($withRepository || $withService) {
            $this->line('<info>✅ Service provider bindings updated</info>');
        }

        $this->newLine();
        $this->line('<info>Next steps:</info>');
        $this->line('- Run: <comment>php artisan route:list</comment> to verify routes');
        if ($withRepository || $withService) {
            $this->line('- Run: <comment>php artisan config:cache</comment> to cache configuration');
        }
        $this->line('- Review generated validation rules in request classes');
        $this->line('- Customize business logic in service classes');
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
            ['api-only', null, InputOption::VALUE_NONE, 'Generate only API controllers.'],
            ['web-only', null, InputOption::VALUE_NONE, 'Generate only web controllers.'],
            ['without-interface', null, InputOption::VALUE_NONE, 'Skip interface generation for repositories and services.'],
        ];
    }
}
