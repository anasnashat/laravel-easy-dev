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
    public function addRepositoryBinding(string $modelName): void
    {
        $this->ensureRepositoryServiceProvider();
        
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
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
        
        // Add use statements
        $content = $this->addUseStatements($content, [
            "App\\Repositories\\Contracts\\{$interfaceName}",
            "App\\Repositories\\{$implementationName}"
        ]);

        $this->files->put($providerPath, $content);
    }

    /**
     * Add service binding to repository service provider.
     */
    public function addServiceBinding(string $modelName): void
    {
        $this->ensureRepositoryServiceProvider();
        
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
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
        
        // Add use statements
        $content = $this->addUseStatements($content, [
            "App\\Services\\Contracts\\{$interfaceName}",
            "App\\Services\\{$implementationName}"
        ]);

        $this->files->put($providerPath, $content);
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
        // Find the register method
        if (preg_match('/(public function register\(\): void\s*\{)(.*?)(\n\s*\})/s', $content, $matches)) {
            $methodBody = $matches[2];
            
            // Check if method is empty (only contains whitespace or comment)
            if (trim(str_replace(['//'], '', $methodBody)) === '') {
                $newMethodBody = "\n{$binding}\n    ";
            } else {
                $newMethodBody = $methodBody . "\n{$binding}";
            }
            
            $replacement = $matches[1] . $newMethodBody . $matches[3];
            $content = str_replace($matches[0], $replacement, $content);
        }

        return $content;
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
