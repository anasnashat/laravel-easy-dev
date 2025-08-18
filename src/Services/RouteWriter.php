<?php

namespace AnasNashat\EasyDev\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class RouteWriter
{
    public function __construct(protected Filesystem $files)
    {
    }

    /**
     * Add resource routes to web.php or api.php.
     */
    public function addResourceRoutes(string $modelName, bool $isApi = false): void
    {
        $routeFile = $isApi ? base_path('routes/api.php') : base_path('routes/web.php');
        
        if (!$this->files->exists($routeFile)) {
            // Create the file if it doesn't exist
            $this->createRouteFile($routeFile, $isApi);
        }

        $content = $this->files->get($routeFile);
        $controllerName = $isApi ? $modelName . 'ApiController' : $modelName . 'Controller';
        $resourceName = Str::kebab(Str::plural($modelName));
        
        // Add use statement for controller (always ensure import exists)
        $controllerNamespace = $isApi 
            ? "App\\Http\\Controllers\\Api\\{$controllerName}"
            : "App\\Http\\Controllers\\{$controllerName}";
            
        $this->addUseStatement($routeFile, $controllerNamespace);
        
        // Check if route already exists
        if (str_contains($content, "Route::resource('{$resourceName}'")) {
            return; // Route already exists, but import was ensured above
        }

        // Add route
        $routeLine = $isApi 
            ? "Route::apiResource('{$resourceName}', {$controllerName}::class);"
            : "Route::resource('{$resourceName}', {$controllerName}::class);";

        // Find insertion point (before closing PHP tag or at end)
        $insertionPoint = strrpos($content, '<?php');
        if ($insertionPoint !== false) {
            // Find the end of the file or before closing PHP tag
            $content = rtrim($content);
            $content .= "\n\n" . $routeLine . "\n";
        } else {
            $content .= "\n" . $routeLine . "\n";
        }

        $this->files->put($routeFile, $content);
    }

    /**
     * Add use statement to route file.
     */
    protected function addUseStatement(string $filePath, string $className): void
    {
        $content = $this->files->get($filePath);

        // Check if use statement already exists
        if (str_contains($content, "use {$className};")) {
            return;
        }

        // Find the position after opening PHP tag
        $lines = explode("\n", $content);
        $insertLine = 1; // After <?php

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ')) {
                $insertLine = $index + 1;
            }
        }

        array_splice($lines, $insertLine, 0, "use {$className};");
        $this->files->put($filePath, implode("\n", $lines));
    }

    /**
     * Add custom route to route file.
     */
    public function addCustomRoute(string $method, string $uri, string $controller, string $action, bool $isApi = false): void
    {
        $routeFile = $isApi ? base_path('routes/api.php') : base_path('routes/web.php');
        
        if (!$this->files->exists($routeFile)) {
            throw new \Exception("Route file not found: {$routeFile}");
        }

        $content = $this->files->get($routeFile);
        $routeLine = "Route::{$method}('{$uri}', [{$controller}::class, '{$action}']);";

        // Check if route already exists
        if (str_contains($content, $routeLine)) {
            return; // Route already exists
        }

        $content = rtrim($content);
        $content .= "\n" . $routeLine . "\n";

        $this->files->put($routeFile, $content);
    }

    /**
     * Create a route file if it doesn't exist.
     */
    protected function createRouteFile(string $routeFile, bool $isApi): void
    {
        $directory = dirname($routeFile);
        
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        if ($isApi) {
            $content = "<?php\n\nuse Illuminate\Http\Request;\nuse Illuminate\Support\Facades\Route;\n\n";
        } else {
            $content = "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n";
        }

        $this->files->put($routeFile, $content);
    }
}
