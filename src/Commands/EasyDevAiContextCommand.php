<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\RelationDetector;
use AnasNashat\EasyDev\Services\FileGenerator;
use AnasNashat\EasyDev\Contracts\SchemaParser;
use Illuminate\Support\Str;

class EasyDevAiContextCommand extends Command
{
    protected $signature = 'easy-dev:ai-context {--pretty : Pretty print the JSON output}';
    protected $description = 'Output a comprehensive high-density context map of your database, models, configurations, and stubs for AI agents.';

    public function __construct(
        protected RelationDetector $relationDetector,
        protected SchemaParser $schemaParser,
        protected FileGenerator $generator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $context = [];

            // 1. Configured Paths
            $context['paths'] = array_map(function ($path) {
                return str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            }, config('easy-dev.paths', []));

            // 2. Modules Configuration
            $context['modules'] = config('easy-dev.modules', [
                'enabled' => false,
                'path' => 'app/Modules',
            ]);

            // 3. Stubs & Custom Overrides Map
            $stubKeys = ['model', 'controller', 'api_controller', 'repository', 'repository_interface', 'service', 'service_interface', 'request', 'policy', 'dto', 'observer', 'filter', 'enum', 'api_resource', 'api_collection', 'test'];
            $stubsMap = [];
            foreach ($stubKeys as $key) {
                $path = $this->generator->getStubPath($key);
                $stubsMap[$key] = [
                    'configured_name' => config("easy-dev.stubs.{$key}"),
                    'resolved_path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path),
                    'is_customized' => str_contains($path, resource_path('stubs/vendor/easy-dev')),
                ];
            }
            $context['stubs'] = $stubsMap;

            // 4. Model & Database Audit
            $models = $this->relationDetector->getAllModels();
            $modelsAudit = [];

            foreach ($models as $modelName) {
                $modelClass = $this->relationDetector->qualifyModel($modelName);

                if (!class_exists($modelClass)) {
                    continue;
                }

                $instance = new $modelClass();
                $tableName = $instance->getTable();

                // Columns
                $columns = [];
                try {
                    $dbColumns = $this->schemaParser->getTableColumns($tableName);
                    foreach ($dbColumns as $col) {
                        $columns[] = [
                            'name' => $col->column_name,
                            'type' => $col->data_type,
                            'nullable' => $col->is_nullable === 'YES',
                        ];
                    }
                } catch (\Exception $e) {
                }

                // Relations
                $relations = [];
                try {
                    $discovered = $this->relationDetector->discoverRelations($modelName);
                    $allRelations = array_merge($discovered['direct'] ?? [], $discovered['inverse'] ?? []);
                    foreach ($allRelations as $rel) {
                        $relations[] = [
                            'name' => $rel['method_name'],
                            'type' => $rel['type'],
                            'related' => $rel['related_model_class'] ? class_basename($rel['related_model_class']) : 'Morph',
                        ];
                    }
                } catch (\Exception $e) {
                }

                $modelsAudit[$modelName] = [
                    'table' => $tableName,
                    'columns' => $columns,
                    'relations' => $relations,
                ];
            }
            $context['models'] = $modelsAudit;

            // 5. Commands Reference
            $context['commands_reference'] = [
                'easy-dev:crud' => [
                    'description' => 'Generate complete CRUD models, migrations, repositories, controllers, form requests, policies, DTOs, and observers.',
                    'args' => ['model (required)'],
                    'options' => [
                        '--with-repository' => 'Generate repository implementation & contracts',
                        '--with-service' => 'Generate service layer implementation & contracts',
                        '--with-policy' => 'Generate authorization policy',
                        '--with-dto' => 'Generate DTO data class',
                        '--with-observer' => 'Generate lifecycle observer',
                        '--api-only' => 'Generate only API resources & controllers',
                        '--web-only' => 'Generate only web views & controllers',
                        '--without-interface' => 'Skip Repository/Service contract generation',
                        '--stub' => 'Override default stub name or pass an absolute path',
                        '--path' => 'Override generation root output directory',
                        '--module' => 'Place all files inside a Domain Module (e.g. Orders)',
                        '--ai' => 'Suppress interactive text, return structured JSON',
                    ]
                ],
                'easy-dev:dream' => [
                    'description' => 'Scaffold an entire entity with schema fields and relationships using natural language prompting.',
                    'args' => ['prompt (required)'],
                    'options' => [
                        '--ai' => 'Silent structured JSON mode',
                        '--dry-run' => 'Show compilation blueprint without making changes',
                    ]
                ],
                'easy-dev:snapshot' => [
                    'description' => 'Return high-density, token-efficient schema snapshot of all models.',
                    'options' => ['--ai' => 'Return JSON']
                ],
                'easy-dev:publish-stubs' => [
                    'description' => 'Publish package stubs to your application for dynamic customization.',
                    'options' => [
                        '--only' => 'Specific stubs (comma-separated)',
                        '--list' => 'List available stubs without copying',
                    ]
                ]
            ];

            // 6. AI Prompts Guidance
            $context['ai_guidance'] = [
                'instructions' => "You are an AI assistant. You can scaffold feature code inside this project. Run: `php artisan easy-dev:crud ModelName` to generate standard structures. Customize output paths using `--path` or nest files inside a Domain Module with `--module=ModuleName`. To use customized stubs, publish them using `php artisan easy-dev:publish-stubs` or specify a custom stub path using `--stub`."
            ];

            $options = $this->option('pretty') ? JSON_PRETTY_PRINT : 0;
            $this->line(json_encode([
                'status' => 'success',
                'context' => $context,
            ], $options));

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->line(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]));
            return self::FAILURE;
        }
    }
}
