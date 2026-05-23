<?php

namespace AnasNashat\EasyDev\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ServiceProviderManager
{
    public function __construct(protected Filesystem $files)
    {
    }

    /**
     * Create or update repository service provider.
     */
    public function ensureRepositoryServiceProvider(): void
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        
        if (!$this->files->exists($providerPath)) {
            $this->createRepositoryServiceProvider($providerPath);
            $this->registerProviderInBootstrap();
        }
    }

    /**
     * Add binding to repository service provider.
     */
    public function addRepositoryBinding(string $modelName, ?string $module = null, ?string $preset = null): void
    {
        $providerPath = $this->ensureServiceProvider($module, $preset);
        $content = $this->files->get($providerPath);
        
        $interfaceName = "{$modelName}RepositoryInterface";
        $implementationName = "{$modelName}Repository";
        
        $binding = "        \$this->app->bind({$interfaceName}::class, {$implementationName}::class);";
        
        // Check if binding already exists
        if (strpos($content, $binding) !== false) {
            return;
        }

        // Add binding to register method
        $content = $this->addBindingToRegisterMethod($content, $binding);
        
        // Resolve namespaces dynamically
        $generator = app(\AnasNashat\EasyDev\Services\FileGenerator::class);
        $interfaceNamespace = $generator->getNamespaceForType('repository_contracts', $modelName, null, $module, $preset);
        $implementationNamespace = $generator->getNamespaceForType('repositories', $modelName, null, $module, $preset);

        // Add use statements
        $content = $this->addUseStatements($content, [
            "{$interfaceNamespace}\\{$interfaceName}",
            "{$implementationNamespace}\\{$implementationName}"
        ]);

        $this->files->put($providerPath, $content);
    }

    /**
     * Add service binding to repository service provider.
     */
    public function addServiceBinding(string $modelName, ?string $module = null, ?string $preset = null): void
    {
        $providerPath = $this->ensureServiceProvider($module, $preset);
        $content = $this->files->get($providerPath);
        
        $interfaceName = "{$modelName}ServiceInterface";
        $implementationName = "{$modelName}Service";
        
        $binding = "        \$this->app->bind({$interfaceName}::class, {$implementationName}::class);";
        
        // Check if binding already exists
        if (strpos($content, $binding) !== false) {
            return;
        }

        // Add binding to register method
        $content = $this->addBindingToRegisterMethod($content, $binding);
        
        // Resolve namespaces dynamically
        $generator = app(\AnasNashat\EasyDev\Services\FileGenerator::class);
        $interfaceNamespace = $generator->getNamespaceForType('service_contracts', $modelName, null, $module, $preset);
        $implementationNamespace = $generator->getNamespaceForType('services', $modelName, null, $module, $preset);

        // Add use statements
        $content = $this->addUseStatements($content, [
            "{$interfaceNamespace}\\{$interfaceName}",
            "{$implementationNamespace}\\{$implementationName}"
        ]);

        $this->files->put($providerPath, $content);
    }

    /**
     * Get the service provider file path.
     */
    public function getProviderPath(?string $module = null, ?string $preset = null): string
    {
        if ($module) {
            $module = Str::studly($module);
            $modulesRoot = config('easy-dev.modules.path', 'app/Modules');
            
            // In clean architecture preset, check Infrastructure/Providers first
            if ($preset === 'clean') {
                $path = base_path("{$modulesRoot}/{$module}/Infrastructure/Providers/{$module}ServiceProvider.php");
                if ($this->files->exists($path)) {
                    return $path;
                }
            }
            
            // Standard modular provider path
            $path = base_path("{$modulesRoot}/{$module}/Providers/{$module}ServiceProvider.php");
            if ($this->files->exists($path)) {
                return $path;
            }
            
            // Check any *ServiceProvider.php in module folder
            $modulePath = base_path("{$modulesRoot}/{$module}");
            if ($this->files->isDirectory($modulePath)) {
                $files = $this->files->allFiles($modulePath);
                foreach ($files as $file) {
                    if (str_ends_with($file->getFilename(), 'ServiceProvider.php')) {
                        return $file->getRealPath();
                    }
                }
            }
            
            // If none exists, we will scaffold it in the module folder!
            $subFolder = ($preset === 'clean') ? 'Infrastructure/Providers' : 'Providers';
            return base_path("{$modulesRoot}/{$module}/{$subFolder}/{$module}ServiceProvider.php");
        }
        
        return app_path('Providers/RepositoryServiceProvider.php');
    }

    /**
     * Ensure a service provider exists.
     */
    public function ensureServiceProvider(?string $module = null, ?string $preset = null): string
    {
        $providerPath = $this->getProviderPath($module, $preset);
        
        if (!$this->files->exists($providerPath)) {
            $this->createModuleServiceProvider($providerPath, $module, $preset);
            
            // If it is a global provider, register in bootstrap/providers.php
            if (!$module) {
                $this->registerProviderInBootstrap();
            }
        }
        
        return $providerPath;
    }

    /**
     * Create modular service provider.
     */
    protected function createModuleServiceProvider(string $providerPath, ?string $module = null, ?string $preset = null): void
    {
        if (!$module) {
            $this->createRepositoryServiceProvider($providerPath);
            return;
        }

        $namespace = $this->getNamespaceFromPath($providerPath);
        $className = basename($providerPath, '.php');

        $content = "<?php\n\nnamespace {$namespace};\n\nuse Illuminate\Support\ServiceProvider;\n\nclass {$className} extends ServiceProvider\n{\n    /**\n     * Register services.\n     */\n    public function register(): void\n    {\n        //\n    }\n\n    /**\n     * Bootstrap services.\n     */\n    public function boot(): void\n    {\n        //\n    }\n}\n";

        $directory = dirname($providerPath);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($providerPath, $content);
    }

    /**
     * Reorganize module providers and update module.json if --preset=clean is used.
     */
    public function reorganizeModuleProviders(string $module, ?string $preset = null): void
    {
        if ($preset !== 'clean') {
            return;
        }

        $module = Str::studly($module);
        $modulesRoot = config('easy-dev.modules.path', 'app/Modules');
        $modulePath = base_path("{$modulesRoot}/{$module}");

        if (!$this->files->isDirectory($modulePath)) {
            return;
        }

        $oldProvidersPath = "{$modulePath}/Providers";
        $newProvidersPath = "{$modulePath}/Infrastructure/Providers";

        if ($this->files->isDirectory($oldProvidersPath)) {
            if (!$this->files->isDirectory($newProvidersPath)) {
                $this->files->makeDirectory($newProvidersPath, 0755, true);
            }

            $providerFiles = $this->files->files($oldProvidersPath);
            foreach ($providerFiles as $file) {
                $fileName = $file->getFilename();
                $targetFile = "{$newProvidersPath}/{$fileName}";

                // Move file
                $this->files->move($file->getRealPath(), $targetFile);

                // Update namespace inside file
                if ($this->files->exists($targetFile)) {
                    $content = $this->files->get($targetFile);
                    
                    // Replace namespaces
                    $oldNamespace1 = "App\\Modules\\{$module}\\Providers";
                    $newNamespace1 = "App\\Modules\\{$module}\\Infrastructure\\Providers";
                    $oldNamespace2 = "Modules\\{$module}\\Providers";
                    $newNamespace2 = "Modules\\{$module}\\Infrastructure\\Providers";

                    $content = str_replace($oldNamespace1, $newNamespace1, $content);
                    $content = str_replace($oldNamespace2, $newNamespace2, $content);

                    $this->files->put($targetFile, $content);
                }
            }

            // Remove old directory if empty
            if (empty($this->files->files($oldProvidersPath)) && empty($this->files->directories($oldProvidersPath))) {
                $this->files->deleteDirectory($oldProvidersPath);
            }
        }

        // Update module.json
        $moduleJsonPath = "{$modulePath}/module.json";
        if ($this->files->exists($moduleJsonPath)) {
            $jsonContent = $this->files->get($moduleJsonPath);
            
            $oldProvider1 = "App\\Modules\\{$module}\\Providers\\{$module}ServiceProvider";
            $newProvider1 = "App\\Modules\\{$module}\\Infrastructure\\Providers\\{$module}ServiceProvider";
            $oldProvider2 = "Modules\\{$module}\\Providers\\{$module}ServiceProvider";
            $newProvider2 = "Modules\\{$module}\\Infrastructure\\Providers\\{$module}ServiceProvider";

            // Support double-escaped backslashes in JSON (App\\Modules\\...)
            $jsonContent = str_replace(
                str_replace('\\', '\\\\', $oldProvider1),
                str_replace('\\', '\\\\', $newProvider1),
                $jsonContent
            );
            $jsonContent = str_replace(
                str_replace('\\', '\\\\', $oldProvider2),
                str_replace('\\', '\\\\', $newProvider2),
                $jsonContent
            );

            // Also support single-escaped or literal backslashes just in case
            $jsonContent = str_replace($oldProvider1, $newProvider1, $jsonContent);
            $jsonContent = str_replace($oldProvider2, $newProvider2, $jsonContent);

            $this->files->put($moduleJsonPath, $jsonContent);
        }
    }

    /**
     * Get PSR-4 namespace from file path.
     */
    public function getNamespaceFromPath(string $filePath): string
    {
        $dir = dirname($filePath);
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $dir);
        $namespace = str_replace([DIRECTORY_SEPARATOR, '/'], '\\', $relativePath);
        if (str_starts_with(strtolower($namespace), 'app\\')) {
            $namespace = 'App\\' . substr($namespace, 4);
        } elseif (strtolower($namespace) === 'app') {
            $namespace = 'App';
        }
        return rtrim($namespace, '\\');
    }


    /**
     * Create repository service provider.
     */
    protected function createRepositoryServiceProvider(string $providerPath): void
    {
        $content = '<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
';
        
        // Ensure directory exists
        $directory = dirname($providerPath);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($providerPath, $content);
    }

    /**
     * Register provider in bootstrap/providers.php.
     */
    protected function registerProviderInBootstrap(): void
    {
        $bootstrapPath = base_path('bootstrap/providers.php');
        
        if (!$this->files->exists($bootstrapPath)) {
            return;
        }

        $content = $this->files->get($bootstrapPath);
        $providerClass = 'App\\Providers\\RepositoryServiceProvider::class';
        
        // Check if provider is already registered
        if (strpos($content, $providerClass) !== false) {
            return;
        }

        // Add provider to the array
        if (preg_match('/return\s*\[(.*?)\];/s', $content, $matches)) {
            $existingProviders = trim($matches[1]);
            
            if (!empty($existingProviders) && !str_ends_with($existingProviders, ',')) {
                $existingProviders .= ',';
            }
            
            $newProviders = $existingProviders . "\n    " . $providerClass . ',';
            $replacement = "return [\n    " . trim($newProviders, ', ') . "\n];";
            
            $content = preg_replace('/return\s*\[(.*?)\];/s', $replacement, $content);
            $this->files->put($bootstrapPath, $content);
        }
    }

    /**
     * Add binding to register method.
     */
    protected function addBindingToRegisterMethod(string $content, string $binding): string
    {
        // Matches "public function register()" or "public function register(): void" with any spaces/newlines
        $pattern = '/(public\s+function\s+register\s*\(\s*\)\s*(?::\s*void)?\s*\{)(.*?)(\n\s*\})/s';
        
        $replaced = preg_replace_callback($pattern, function ($matches) use ($binding) {
            $methodBody = $matches[2];
            
            // Check if method is empty (only contains whitespace, comments or is empty)
            $cleanBody = trim(str_replace(['//', '/*', '*/', '*'], '', $methodBody));
            if ($cleanBody === '') {
                return $matches[1] . "\n" . $binding . "\n    " . $matches[3];
            }
            
            // Append binding to the end of the method body
            return $matches[1] . $methodBody . "\n" . $binding . $matches[3];
        }, $content, 1);

        return $replaced ?? $content;
    }

    /**
     * Add use statements to provider.
     */
    protected function addUseStatements(string $content, array $useStatements): string
    {
        foreach ($useStatements as $useStatement) {
            $fullUseStatement = "use {$useStatement};";
            
            // Check if use statement already exists
            if (strpos($content, $fullUseStatement) !== false) {
                continue;
            }

            // Find the last use statement and add after it
            if (preg_match_all('/^use\s+[^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lastMatch = end($matches[0]);
                $lastUsePosition = $lastMatch[1] + strlen($lastMatch[0]);
                $content = substr_replace($content, "\n" . $fullUseStatement, $lastUsePosition, 0);
            } else {
                // Add after namespace
                if (preg_match('/^namespace\s+[^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $namespacePosition = $matches[0][1] + strlen($matches[0][0]);
                    $content = substr_replace($content, "\n\n" . $fullUseStatement, $namespacePosition, 0);
                }
            }
        }

        return $content;
    }
}
