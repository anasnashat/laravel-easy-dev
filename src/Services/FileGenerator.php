<?php

namespace AnasNashat\EasyDev\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class FileGenerator
{
    public function __construct(protected Filesystem $files)
    {
    }

    /**
     * Get the content of a stub file with replacements.
     */
    public function getStubContent(string $stub, array $replacements = [], ?string $explicitStub = null): string
    {
        $stubPath = $this->getStubPath($stub, $explicitStub);
        
        if (!$this->files->exists($stubPath)) {
            throw new \Exception("Stub file not found: {$stubPath}");
        }

        $content = $this->files->get($stubPath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace('{{ ' . $search . ' }}', $replace, $content);
            $content = str_replace('{{' . $search . '}}', $replace, $content);
        }

        return $content;
    }

    /**
     * Convert a file path into its PSR-4 class namespace.
     */
    public function getNamespaceFromPath(string $filePath): string
    {
        $dir = dirname($filePath);
        
        // Remove base path to get relative directory
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $dir);
        
        // Convert directory separators to backslashes
        $namespace = str_replace([DIRECTORY_SEPARATOR, '/'], '\\', $relativePath);
        
        // Normalize the root namespace (e.g. app -> App)
        if (str_starts_with(strtolower($namespace), 'app\\')) {
            $namespace = 'App\\' . substr($namespace, 4);
        } elseif (strtolower($namespace) === 'app') {
            $namespace = 'App';
        }
        
        return rtrim($namespace, '\\');
    }

    /**
     * Resolve the FQCN namespace for a specific type and model.
     */
    public function getNamespaceForType(string $type, string $modelName, ?string $explicitPath = null, ?string $module = null, ?string $preset = null): string
    {
        $filename = "Dummy.php";
        
        // Special subfolders for interfaces
        if ($type === 'repository_contracts' && $explicitPath) {
            $path = rtrim($explicitPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts';
            $resolved = $this->resolveOutputPath('repository_contracts', $filename, $path, $module, $preset);
        } elseif ($type === 'service_contracts' && $explicitPath) {
            $path = rtrim($explicitPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts';
            $resolved = $this->resolveOutputPath('service_contracts', $filename, $path, $module, $preset);
        } else {
            $resolved = $this->resolveOutputPath($type, $filename, $explicitPath, $module, $preset);
        }
        
        return $this->getNamespaceFromPath($resolved);
    }

    /**
     * Generate a file from a stub with namespace and import translation.
     */
    public function generateFile(string $filePath, string $stub, array $replacements = [], ?string $explicitStub = null, ?string $modelName = null, ?string $explicitPath = null, ?string $module = null, ?string $preset = null): void
    {
        $targetNamespace = $this->getNamespaceFromPath($filePath);
        
        // Ensure default namespace replacements are set
        if (!isset($replacements['namespace'])) {
            $replacements['namespace'] = $targetNamespace;
        }
        if (!isset($replacements['Namespace'])) {
            $replacements['Namespace'] = $targetNamespace;
        }
        if (!empty($modelName)) {
            $replacements['ModelNamespace'] = $replacements['ModelNamespace'] ?? $this->getNamespaceForType('models', $modelName, $explicitPath, $module, $preset);
            $replacements['RepositoryNamespace'] = $replacements['RepositoryNamespace'] ?? $this->getNamespaceForType('repositories', $modelName, $explicitPath, $module, $preset);
            $replacements['RepositoryContractNamespace'] = $replacements['RepositoryContractNamespace'] ?? $this->getNamespaceForType('repository_contracts', $modelName, $explicitPath, $module, $preset);
            $replacements['ServiceNamespace'] = $replacements['ServiceNamespace'] ?? $this->getNamespaceForType('services', $modelName, $explicitPath, $module, $preset);
            $replacements['ServiceContractNamespace'] = $replacements['ServiceContractNamespace'] ?? $this->getNamespaceForType('service_contracts', $modelName, $explicitPath, $module, $preset);
            $replacements['RequestNamespace'] = $replacements['RequestNamespace'] ?? $this->getNamespaceForType('requests', $modelName, $explicitPath, $module, $preset);
            $replacements['ResourceNamespace'] = $replacements['ResourceNamespace'] ?? $this->getNamespaceForType('resources', $modelName, $explicitPath, $module, $preset);
        }

        $content = $this->getStubContent($stub, $replacements, $explicitStub);
        
        // 1. Dynamic namespace translation (overrides hardcoded namespaces in stubs)
        $content = preg_replace('/namespace\s+[A-Za-z0-9_\\\\]+;/', "namespace {$targetNamespace};", $content);

        // 2. Import translations: If modelName is provided, rewrite defaults to custom/modular locations
        if (!empty($modelName)) {
            $defaultModels = 'App\\Models';
            $defaultRepos = 'App\\Repositories';
            $defaultRepoContracts = 'App\\Repositories\\Contracts';
            $defaultServices = 'App\\Services';
            $defaultServiceContracts = 'App\\Services\\Contracts';
            $defaultRequests = 'App\\Http\\Requests';

            $targetModels = $this->getNamespaceForType('models', $modelName, $explicitPath, $module, $preset);
            $targetRepos = $this->getNamespaceForType('repositories', $modelName, $explicitPath, $module, $preset);
            
            // Interfaces are placed in Contracts subdirectory
            $targetRepoContracts = $this->getNamespaceForType(
                'repository_contracts', 
                $modelName, 
                $explicitPath ? rtrim($explicitPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null, 
                $module,
                $preset
            );
            $targetServices = $this->getNamespaceForType('services', $modelName, $explicitPath, $module, $preset);
            $targetServiceContracts = $this->getNamespaceForType(
                'service_contracts', 
                $modelName, 
                $explicitPath ? rtrim($explicitPath, '/\\') . DIRECTORY_SEPARATOR . 'Contracts' : null, 
                $module,
                $preset
            );
            $targetRequests = $this->getNamespaceForType('requests', $modelName, $explicitPath, $module, $preset);

            // Replace standard imports in content
            $translations = [
                "use {$defaultModels}\\" => "use {$targetModels}\\",
                "use {$defaultRepos}\\" => "use {$targetRepos}\\",
                "use {$defaultRepoContracts}\\" => "use {$targetRepoContracts}\\",
                "use {$defaultServices}\\" => "use {$targetServices}\\",
                "use {$defaultServiceContracts}\\" => "use {$targetServiceContracts}\\",
                "use {$defaultRequests}\\" => "use {$targetRequests}\\",
            ];

            foreach ($translations as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }
        }

        // Ensure directory exists
        $directory = dirname($filePath);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($filePath, $content);
    }

    /**
     * Resolve a stub path using a 4-layer resolution chain.
     */
    public function getStubPath(string $stub, ?string $explicitStub = null): string
    {
        // Layer 1: Explicit stub path or name passed via CLI
        if (!empty($explicitStub)) {
            if ($this->files->exists($explicitStub)) {
                return realpath($explicitStub);
            }
            if ($this->files->exists(base_path($explicitStub))) {
                return base_path($explicitStub);
            }

            $stubName = Str::finish($explicitStub, '.stub');
            $customPath = resource_path("stubs/vendor/easy-dev/{$stubName}");
            if ($this->files->exists($customPath)) {
                return $customPath;
            }

            $packagePath = __DIR__ . "/../../resources/stubs/{$stubName}";
            if ($this->files->exists($packagePath)) {
                return $packagePath;
            }
        }

        // Layer 2: Project-level published custom stub
        $publishedStub = Str::finish($stub, '.stub');
        $customStubPath = resource_path("stubs/vendor/easy-dev/{$publishedStub}");
        if ($this->files->exists($customStubPath)) {
            return $customStubPath;
        }

        // Layer 3: Config-mapped stub name
        $configKey = "easy-dev.stubs.{$stub}";
        $configMappedStub = config($configKey);
        if (!empty($configMappedStub)) {
            $configStubName = Str::finish($configMappedStub, '.stub');
            
            $configCustomPath = resource_path("stubs/vendor/easy-dev/{$configStubName}");
            if ($this->files->exists($configCustomPath)) {
                return $configCustomPath;
            }
            
            $configPackagePath = __DIR__ . "/../../resources/stubs/{$configStubName}";
            if ($this->files->exists($configPackagePath)) {
                return $configPackagePath;
            }
        }

        // Layer 4: Default package stub
        return __DIR__ . "/../../resources/stubs/{$publishedStub}";
    }

    /**
     * Get target relative path under a module for a given preset.
     */
    public function getPresetNamespaces(string $preset, string $type): string
    {
        $presets = [
            'clean' => [
                'models'               => 'Domain/Models',
                'enums'                => 'Domain/Enums',
                
                'controllers'          => 'Presentation/Http/Controllers',
                'api_controllers'      => 'Presentation/Http/Controllers/Api',
                'requests'             => 'Presentation/Http/Requests',
                'resources'            => 'Presentation/Http/Resources',
                
                'repositories'         => 'Infrastructure/Repositories',
                'repository_contracts' => 'Domain/Repositories',
                
                'services'             => 'Application/Services',
                'service_contracts'    => 'Application/Services',
                
                'policies'             => 'Infrastructure/Policies',
                'dtos'                 => 'Application/DTOs',
                'observers'            => 'Infrastructure/Observers',
                'filters'              => 'Infrastructure/Filters',
                'tests'                => 'Tests/Feature',
                'feature_tests'        => 'Tests/Feature',
                'unit_tests'           => 'Tests/Unit',
            ],
            'ddd' => [
                'models'               => 'Domain/Models',
                'enums'                => 'Domain/Enums',

                'controllers'          => 'Presentation/Http/Controllers',
                'api_controllers'      => 'Presentation/Http/Controllers/Api',
                'requests'             => 'Presentation/Http/Requests',
                'resources'            => 'Presentation/Http/Resources',

                'repositories'         => 'Infrastructure/Persistence/Repositories',
                'repository_contracts' => 'Domain/Repositories',

                'services'             => 'Application/Services',
                'service_contracts'    => 'Application/Contracts',

                'policies'             => 'Application/Policies',
                'dtos'                 => 'Application/DTOs',
                'observers'            => 'Infrastructure/Observers',
                'filters'              => 'Application/Filters',
                'tests'                => 'Tests/Feature',
                'feature_tests'        => 'Tests/Feature',
                'unit_tests'           => 'Tests/Unit',
            ],
        ];

        return $presets[strtolower($preset)][$type] ?? Str::studly($type);
    }

    /**
     * Dynamically resolve output path based on CLI paths, modules, or config maps.
     */
    public function resolveOutputPath(string $type, string $filename, ?string $explicitPath = null, ?string $module = null, ?string $preset = null): string
    {
        // 1. Explicit path override via CLI
        if (!empty($explicitPath)) {
            $base = (str_starts_with($explicitPath, '/') || str_contains($explicitPath, ':\\')) 
                ? $explicitPath 
                : base_path($explicitPath);
            return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $filename;
        }

        // 2. Module Architecture Placement
        if (!empty($module)) {
            $module = Str::studly($module);
            $modulesRoot = config('easy-dev.modules.path', 'app/Modules');
            
            // Find namespace mapping under module based on preset or config
            if (!empty($preset)) {
                $subFolder = $this->getPresetNamespaces($preset, $type);
            } else {
                $subFolder = config("easy-dev.modules.namespaces.{$type}", Str::studly($type));
            }
            
            return base_path(
                rtrim($modulesRoot, '/\\') . DIRECTORY_SEPARATOR . 
                $module . DIRECTORY_SEPARATOR . 
                rtrim($subFolder, '/\\') . DIRECTORY_SEPARATOR . 
                $filename
            );
        }

        // 3. Config-mapped path
        $configPath = config("easy-dev.paths.{$type}");
        if (!empty($configPath)) {
            return rtrim($configPath, '/\\') . DIRECTORY_SEPARATOR . $filename;
        }

        // 4. Default fallback paths based on standard Laravel conventions
        $fallbackPath = match ($type) {
            'models' => app_path('Models'),
            'controllers' => app_path('Http/Controllers'),
            'api_controllers' => app_path('Http/Controllers/Api'),
            'requests' => app_path('Http/Requests'),
            'repositories' => app_path('Repositories'),
            'repository_contracts' => app_path('Repositories/Contracts'),
            'services' => app_path('Services'),
            'service_contracts' => app_path('Services/Contracts'),
            'policies' => app_path('Policies'),
            'dtos' => app_path('DTOs'),
            'observers' => app_path('Observers'),
            'filters' => app_path('Filters'),
            'enums' => app_path('Enums'),
            'resources' => app_path('Http/Resources'),
            'tests', 'feature_tests' => base_path('tests/Feature'),
            'unit_tests' => base_path('tests/Unit'),
            'factories' => database_path('factories'),
            'seeders' => database_path('seeders'),
            'migrations' => database_path('migrations'),
            default => app_path(),
        };

        return rtrim($fallbackPath, '/\\') . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Get detailed resolution metadata for AI audits and audit logs.
     */
    public function getResolutionMetadata(string $type, string $filename, ?string $explicitStub = null, ?string $explicitPath = null, ?string $module = null): array
    {
        $resolvedStub = $this->getStubPath($type, $explicitStub);
        $resolvedPath = $this->resolveOutputPath($type, $filename, $explicitPath, $module);
        
        return [
            'type' => $type,
            'filename' => $filename,
            'resolved_stub' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $resolvedStub),
            'resolved_path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $resolvedPath),
            'stub_override' => !empty($explicitStub),
            'path_override' => !empty($explicitPath),
            'module' => $module,
        ];
    }

    /**
     * Generate a model name from table name.
     */
    public function getModelNameFromTable(string $table): string
    {
        return Str::studly(Str::singular($table));
    }

    /**
     * Generate a controller name from model name.
     */
    public function getControllerNameFromModel(string $model): string
    {
        return $model . 'Controller';
    }

    /**
     * Generate request class names from model name.
     */
    public function getRequestNamesFromModel(string $model): array
    {
        return [
            'store' => "Store{$model}Request",
            'update' => "Update{$model}Request",
        ];
    }
}
