<?php

namespace AnasNashat\EasyDev\Commands;

use AnasNashat\EasyDev\Services\RelationDetector;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class EasyDevAnalyzeCommand extends Command
{
    protected $signature = 'easy-dev:analyze
                            {--model= : Analyze only one model}
                            {--fix : Apply safe generated-file fixes}
                            {--json : Output JSON}
                            {--ai : Output machine-friendly JSON format}';

    protected $description = 'Analyze a Laravel project for missing generated layers and common maintainability risks.';

    public function __construct(
        protected Filesystem $files,
        protected RelationDetector $relationDetector
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $models = $this->option('model')
            ? [Str::studly($this->option('model'))]
            : $this->relationDetector->getAllModels();

        $findings = [];

        foreach ($models as $modelName) {
            $findings = array_merge($findings, $this->analyzeModel($modelName));
        }

        if ($this->option('fix')) {
            $findings = $this->applyFixes($findings);
        }

        if ($this->option('json') || $this->option('ai')) {
            $this->output->write(json_encode([
                'status' => 'success',
                'command' => 'easy-dev:analyze',
                'findings' => $findings,
            ], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        if (empty($findings)) {
            $this->info('No Easy Dev findings detected.');
            return self::SUCCESS;
        }

        foreach ($findings as $finding) {
            $this->line("[{$finding['severity']}] {$finding['title']} ({$finding['model']})");
            $this->line("  {$finding['path']}");
        }

        return self::SUCCESS;
    }

    protected function analyzeModel(string $modelName): array
    {
        $modelName = Str::studly($modelName);
        $findings = [];

        $checks = [
            [
                'type' => 'missing_policy',
                'title' => "Missing policy for {$modelName}",
                'path' => app_path("Policies/{$modelName}Policy.php"),
                'severity' => 'medium',
                'fix_command' => ['easy-dev:policy', ['model' => $modelName, '--ai' => true]],
            ],
            [
                'type' => 'missing_store_request',
                'title' => "Missing store request for {$modelName}",
                'path' => app_path("Http/Requests/Store{$modelName}Request.php"),
                'severity' => 'medium',
            ],
            [
                'type' => 'missing_update_request',
                'title' => "Missing update request for {$modelName}",
                'path' => app_path("Http/Requests/Update{$modelName}Request.php"),
                'severity' => 'medium',
            ],
            [
                'type' => 'missing_api_resource',
                'title' => "Missing API resource for {$modelName}",
                'path' => app_path("Http/Resources/{$modelName}Resource.php"),
                'severity' => 'medium',
                'fix_command' => ['easy-dev:api-resource', ['model' => $modelName, '--ai' => true]],
            ],
        ];

        foreach ($checks as $check) {
            if (!$this->files->exists($check['path'])) {
                $findings[] = $this->finding($modelName, $check);
            }
        }

        $controllerPath = app_path("Http/Controllers/{$modelName}Controller.php");
        $apiControllerPath = app_path("Http/Controllers/Api/{$modelName}ApiController.php");

        foreach ([$controllerPath, $apiControllerPath] as $path) {
            if (!$this->files->exists($path)) {
                continue;
            }

            $content = $this->files->get($path);
            $lineCount = substr_count($content, "\n") + 1;

            if ($lineCount > 250) {
                $findings[] = $this->finding($modelName, [
                    'type' => 'large_controller',
                    'title' => "Large controller detected for {$modelName}",
                    'path' => $path,
                    'severity' => 'low',
                ]);
            }

            if (preg_match('/DB::|->save\(|->create\(|->update\(|dispatch\(|event\(/', $content)) {
                $findings[] = $this->finding($modelName, [
                    'type' => 'controller_business_logic',
                    'title' => "Business logic may exist inside {$modelName} controller",
                    'path' => $path,
                    'severity' => 'medium',
                ]);
            }
        }

        $repositoryPath = app_path("Repositories/{$modelName}Repository.php");
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        if ($this->files->exists($repositoryPath)) {
            $providerContent = $this->files->exists($providerPath) ? $this->files->get($providerPath) : '';
            if (!str_contains($providerContent, "{$modelName}Repository")) {
                $findings[] = $this->finding($modelName, [
                    'type' => 'unused_repository',
                    'title' => "Repository may be unbound for {$modelName}",
                    'path' => $repositoryPath,
                    'severity' => 'low',
                ]);
            }
        }

        return $findings;
    }

    protected function applyFixes(array $findings): array
    {
        foreach ($findings as &$finding) {
            if (empty($finding['fix_command'])) {
                $finding['fixed'] = false;
                continue;
            }

            [$command, $arguments] = $finding['fix_command'];
            $exitCode = $this->callSilent($command, $arguments);
            $finding['fixed'] = $exitCode === self::SUCCESS;
        }

        return $findings;
    }

    protected function finding(string $modelName, array $data): array
    {
        return [
            'type' => $data['type'],
            'severity' => $data['severity'],
            'model' => $modelName,
            'title' => $data['title'],
            'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $data['path']),
            'fix_command' => $data['fix_command'] ?? null,
        ];
    }
}
