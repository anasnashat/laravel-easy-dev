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
    public function getStubContent(string $stub, array $replacements = []): string
    {
        $stubPath = $this->getStubPath($stub);
        
        if (!$this->files->exists($stubPath)) {
            throw new \Exception("Stub file not found: {$stubPath}");
        }

        $content = $this->files->get($stubPath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace('{{ ' . $search . ' }}', $replace, $content);
        }

        return $content;
    }

    /**
     * Generate a file from a stub.
     */
    public function generateFile(string $filePath, string $stub, array $replacements = []): void
    {
        $content = $this->getStubContent($stub, $replacements);
        
        // Ensure directory exists
        $directory = dirname($filePath);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($filePath, $content);
    }

    /**
     * Get the path to a stub file.
     */
    protected function getStubPath(string $stub): string
    {
        // First check if user has published custom stubs
        $customStubPath = resource_path("stubs/vendor/easy-dev/{$stub}.stub");
        
        if ($this->files->exists($customStubPath)) {
            return $customStubPath;
        }

        // Fall back to package stubs
        return __DIR__ . "/../../resources/stubs/{$stub}.stub";
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
