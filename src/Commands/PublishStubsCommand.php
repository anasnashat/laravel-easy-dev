<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

class PublishStubsCommand extends Command
{
    protected $name = 'easy-dev:publish-stubs';
    protected $description = 'Publish all package stub templates to your application for dynamic customization.';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $only = $this->option('only');
        $listOnly = $this->option('list');
        $isAiMode = $this->option('ai');
        $force = $this->option('force');

        $stubsDir = __DIR__ . '/../../resources/stubs';
        $targetDir = resource_path('stubs/vendor/easy-dev');

        try {
            if (!$this->files->exists($stubsDir)) {
                throw new \Exception("Package stubs directory does not exist: {$stubsDir}");
            }

            $allStubs = $this->files->allFiles($stubsDir);
            $availableStubs = [];

            foreach ($allStubs as $file) {
                $relativePath = str_replace('\\', '/', $file->getRelativePathname());
                $stubName = substr($relativePath, 0, -5);
                $availableStubs[$stubName] = $file->getRealPath();
            }

            // Filter if --only provided
            $toPublish = $availableStubs;
            if (!empty($only)) {
                $filters = array_map(fn($value) => str_replace('.', '/', trim($value)), explode(',', $only));
                $toPublish = array_filter($availableStubs, function ($key) use ($filters) {
                    return in_array($key, $filters);
                }, ARRAY_FILTER_USE_KEY);
            }

            // 1. List Only Mode
            if ($listOnly) {
                if ($isAiMode) {
                    $this->line(json_encode([
                        'status' => 'success',
                        'stubs' => array_keys($availableStubs),
                    ], JSON_PRETTY_PRINT));
                } else {
                    $this->info("📚 Available stubs to publish/customize:");
                    foreach (array_keys($availableStubs) as $stub) {
                        $this->line("  - {$stub}");
                    }
                }
                return self::SUCCESS;
            }

            // 2. Publish Mode
            if (!$this->files->isDirectory($targetDir)) {
                $this->files->makeDirectory($targetDir, 0755, true);
            }

            $published = [];
            $skipped = [];
            foreach ($toPublish as $name => $path) {
                $targetFile = "{$targetDir}/{$name}.stub";
                $targetFolder = dirname($targetFile);

                if (!$this->files->isDirectory($targetFolder)) {
                    $this->files->makeDirectory($targetFolder, 0755, true);
                }

                if ($this->files->exists($targetFile) && !$force) {
                    $skipped[] = [
                        'name' => $name,
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetFile),
                        'reason' => 'exists',
                    ];
                    continue;
                }

                $this->files->copy($path, $targetFile);
                $published[] = [
                    'name' => $name,
                    'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetFile),
                ];
            }

            // Publish the SKILL.md file to project root as easy-dev-ai.md
            $skillSource = __DIR__ . '/../../SKILL.md';
            $skillTarget = base_path('easy-dev-ai.md');
            if ($this->files->exists($skillSource)) {
                $this->files->copy($skillSource, $skillTarget);
                $published[] = [
                    'name' => 'ai_guidance_skill',
                    'path' => 'easy-dev-ai.md',
                ];
            }

            if ($isAiMode) {
                $this->line(json_encode([
                    'status' => 'success',
                    'published' => $published,
                    'skipped' => $skipped,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->info("🎉 Stubs published successfully!");
                foreach ($published as $pub) {
                    $this->line("  ✓ Published: {$pub['name']} to <comment>{$pub['path']}</comment>");
                }
            }

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->line(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("Error publishing stubs: {$e->getMessage()}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function getOptions(): array
    {
        return [
            ['only', null, InputOption::VALUE_OPTIONAL, 'Publish only specific stubs (comma-separated, e.g., --only=model,controller).'],
            ['list', null, InputOption::VALUE_NONE, 'List all available stubs without publishing.'],
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing published stubs.'],
            ['ai', null, InputOption::VALUE_NONE, 'Output machine-friendly JSON format for AI integration.'],
        ];
    }
}
