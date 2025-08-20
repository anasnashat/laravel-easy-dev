<?php

namespace AnasNashat\EasyDev\Services;

use Illuminate\Filesystem\Filesystem;
use AnasNashat\EasyDev\Exceptions\RelationAlreadyExistsException;

class CodeWriter
{
    public function __construct(
        protected Filesystem $files,
        protected FileGenerator $generator
    ) {
    }

    /**
     * Add a relationship method to a model file.
     */
    public function addRelation(string $modelPath, string $methodName, string $relationType, string $relatedModelClass): void
    {
        if (!$this->files->exists($modelPath)) {
            throw new \Exception("Model file not found at path: {$modelPath}");
        }

        $content = $this->files->get($modelPath);
        $className = class_basename($relatedModelClass);

        if (str_contains($content, "function {$methodName}()")) {
            throw new RelationAlreadyExistsException("Method '{$methodName}' already exists on model.");
        }

        // Get the relation method code from a stub file.
        $relationStub = $this->generator->getStubContent("relations/{$relationType}", [
            'methodName' => $methodName,
            'relatedModel' => $className, // Use just the class name for the stub
            'relatedModelClass' => $relatedModelClass, // Fully qualified for `::class`
        ]);

        // Find the last curly brace to insert the code before it.
        $insertionPoint = strrpos($content, '}');
        if ($insertionPoint === false) {
            // This should not happen on a valid PHP class file
            throw new \Exception("Could not find closing brace in {$modelPath}.");
        }

        // Indent the stub correctly before inserting
        $indentedStub = preg_replace('/^/m', '    ', $relationStub);

        $newContent = substr_replace($content, "\n" . $indentedStub . "\n", $insertionPoint, 0);
        $this->files->put($modelPath, $newContent);
    }

    /**
     * Add use statement to a PHP file if it doesn't already exist.
     */
    public function addUseStatement(string $filePath, string $className): void
    {
        if (!$this->files->exists($filePath)) {
            throw new \Exception("File not found at path: {$filePath}");
        }

        $content = $this->files->get($filePath);

        // Check if use statement already exists
        if (str_contains($content, "use {$className};")) {
            return;
        }

        // Find the position after the last use statement or after namespace
        $lines = explode("\n", $content);
        $insertLine = 0;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ')) {
                $insertLine = $index + 1;
            } elseif (str_starts_with(trim($line), 'namespace ')) {
                $insertLine = $index + 2; // After namespace and empty line
            }
        }

        array_splice($lines, $insertLine, 0, "use {$className};");
        $this->files->put($filePath, implode("\n", $lines));
    }

    /**
     * Check if a method exists in a PHP class file.
     */
    public function methodExists(string $filePath, string $methodName): bool
    {
        if (!$this->files->exists($filePath)) {
            return false;
        }

        $content = $this->files->get($filePath);
        return str_contains($content, "function {$methodName}(");
    }
}
