<?php

namespace AnasNashat\EasyDev\Commands;

use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeTestCommand extends Command
{
    protected $name = 'easy-dev:test';
    protected $description = 'Generate feature and unit test starter files for a model.';

    public function __construct(
        protected FileGenerator $generator,
        protected Filesystem $files
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = Str::studly($this->argument('model'));
        $isAiMode = $this->option('ai');
        $preset = $this->resolveArchitecturePreset();
        $generateFeature = $this->option('feature') || (!$this->option('unit') && !$this->option('service') && !$this->option('repository'));
        $generateUnit = $this->option('unit') || $this->option('service') || $this->option('repository') || (!$this->option('feature'));

        $generatedFiles = [];

        try {
            if ($generateFeature) {
                $path = $this->resolveTestPath('feature_tests', "{$modelName}ControllerTest.php", $preset);
                $this->writeTestFile($path, 'test.feature.controller', $modelName, [
                    'ModelName' => $modelName,
                    'testName' => Str::snake($modelName),
                    'resourceName' => Str::kebab(Str::plural($modelName)),
                    'apiPrefix' => $this->option('api') ? 'api/' : '',
                ]);

                $generatedFiles[] = $this->fileMetadata('feature_test', "{$modelName}ControllerTest", $path, 'test.feature.controller');
            }

            if ($generateUnit) {
                if ($this->option('repository')) {
                    $name = "{$modelName}RepositoryTest";
                    $path = $this->resolveTestPath('unit_tests', "{$name}.php", $preset);
                    $this->writeTestFile($path, 'test.unit.repository', $modelName, ['ModelName' => $modelName, 'testName' => Str::snake($modelName)]);
                    $generatedFiles[] = $this->fileMetadata('repository_unit_test', $name, $path, 'test.unit.repository');
                }

                if ($this->option('service')) {
                    $name = "{$modelName}ServiceTest";
                    $path = $this->resolveTestPath('unit_tests', "{$name}.php", $preset);
                    $this->writeTestFile($path, 'test.unit.service', $modelName, ['ModelName' => $modelName, 'testName' => Str::snake($modelName)]);
                    $generatedFiles[] = $this->fileMetadata('service_unit_test', $name, $path, 'test.unit.service');
                }

                if (!$this->option('repository') && !$this->option('service')) {
                    $name = "{$modelName}Test";
                    $path = $this->resolveTestPath('unit_tests', "{$name}.php", $preset);
                    $this->writeTestFile($path, 'test.unit.model', $modelName, ['ModelName' => $modelName, 'testName' => Str::snake($modelName)]);
                    $generatedFiles[] = $this->fileMetadata('model_unit_test', $name, $path, 'test.unit.model');
                }
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'command' => 'easy-dev:test',
                    'generated' => $generatedFiles,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->info("Generated test files for {$modelName}.");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("Error generating tests: {$e->getMessage()}");
            }

            return self::FAILURE;
        }
    }

    protected function resolveTestPath(string $type, string $filename, ?string $preset): string
    {
        return $this->generator->resolveOutputPath(
            $type,
            $filename,
            $this->option('path'),
            $this->option('module'),
            $preset
        );
    }

    protected function writeTestFile(string $path, string $stub, string $modelName, array $replacements): void
    {
        $content = $this->generator->getStubContent($stub, $replacements, $this->option('stub'));
        $directory = dirname($path);

        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($path, $content);
    }

    protected function fileMetadata(string $type, string $name, string $path, string $stub): array
    {
        return [
            'type' => $type,
            'name' => $name,
            'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path),
            'stub_used' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $this->generator->getStubPath($stub, $this->option('stub'))),
        ];
    }

    protected function resolveArchitecturePreset(): ?string
    {
        $architecture = $this->option('architecture');

        if ($architecture === 'laravel') {
            return null;
        }

        if ($architecture === 'ddd') {
            return 'ddd';
        }

        return $architecture ?: $this->option('preset');
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model to generate tests for.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['feature', null, InputOption::VALUE_NONE, 'Generate feature/controller test.'],
            ['unit', null, InputOption::VALUE_NONE, 'Generate unit tests.'],
            ['api', null, InputOption::VALUE_NONE, 'Target API resource routes in feature tests.'],
            ['service', null, InputOption::VALUE_NONE, 'Generate a service unit test.'],
            ['repository', null, InputOption::VALUE_NONE, 'Generate a repository unit test.'],
            ['stub', null, InputOption::VALUE_OPTIONAL, 'Override stub template name or absolute/relative file path.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Override the output directory path.'],
            ['module', null, InputOption::VALUE_OPTIONAL, 'Generate inside a domain module directory.'],
            ['preset', null, InputOption::VALUE_OPTIONAL, 'Use a pre-configured architecture preset (e.g. clean).'],
            ['architecture', null, InputOption::VALUE_OPTIONAL, 'Use an architecture layout: laravel, clean, or ddd. Alias for --preset where applicable.'],
            ['ai', null, InputOption::VALUE_NONE, 'Output machine-friendly JSON format for AI integration.'],
        ];
    }
}
