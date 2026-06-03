<?php

namespace AnasNashat\EasyDev\Commands;

use AnasNashat\EasyDev\Services\RelationDetector;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeSwaggerCommand extends Command
{
    protected $signature = 'easy-dev:swagger {model? : Optional model name to document}
                            {--output= : Output file path}
                            {--format=json : Output format: json or yaml}
                            {--ai : Output machine-friendly JSON format}';

    protected $description = 'Generate a basic OpenAPI specification for Easy Dev API resources.';

    public function __construct(
        protected Filesystem $files,
        protected RelationDetector $relationDetector
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isAiMode = $this->option('ai');
        $format = strtolower($this->option('format') ?: 'json');
        $output = $this->option('output') ?: storage_path('app/easy-dev/openapi.' . ($format === 'yaml' ? 'yaml' : 'json'));

        try {
            $models = $this->argument('model')
                ? [Str::studly($this->argument('model'))]
                : $this->relationDetector->getAllModels();

            $spec = $this->buildSpec($models);
            $content = $format === 'yaml' ? $this->toYaml($spec) : json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $directory = dirname($output);
            if (!$this->files->isDirectory($directory)) {
                $this->files->makeDirectory($directory, 0755, true);
            }

            $this->files->put($output, $content . PHP_EOL);

            $result = [
                'status' => 'success',
                'command' => 'easy-dev:swagger',
                'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $output),
                'models' => array_values($models),
            ];

            if ($isAiMode) {
                $this->output->write(json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->info('Generated OpenAPI spec: ' . $result['path']);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error("Error generating OpenAPI spec: {$e->getMessage()}");
            }

            return self::FAILURE;
        }
    }

    protected function buildSpec(array $models): array
    {
        $paths = [];
        $schemas = [];

        foreach ($models as $modelName) {
            $modelName = Str::studly($modelName);
            $resource = Str::kebab(Str::plural($modelName));
            $schemaRef = "#/components/schemas/{$modelName}";

            $paths["/api/{$resource}"] = [
                'get' => [
                    'summary' => "List {$resource}",
                    'tags' => [$modelName],
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'array',
                                        'items' => ['$ref' => $schemaRef],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'summary' => "Create {$modelName}",
                    'tags' => [$modelName],
                    'requestBody' => $this->requestBody($schemaRef),
                    'responses' => ['201' => ['description' => 'Created']],
                ],
            ];

            $paths["/api/{$resource}/{id}"] = [
                'get' => [
                    'summary' => "Show {$modelName}",
                    'tags' => [$modelName],
                    'parameters' => [$this->idParameter()],
                    'responses' => ['200' => ['description' => 'Successful response']],
                ],
                'put' => [
                    'summary' => "Update {$modelName}",
                    'tags' => [$modelName],
                    'parameters' => [$this->idParameter()],
                    'requestBody' => $this->requestBody($schemaRef),
                    'responses' => ['200' => ['description' => 'Updated']],
                ],
                'delete' => [
                    'summary' => "Delete {$modelName}",
                    'tags' => [$modelName],
                    'parameters' => [$this->idParameter()],
                    'responses' => ['204' => ['description' => 'Deleted']],
                ],
            ];

            $schemas[$modelName] = [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ];
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name', 'Laravel') . ' API',
                'version' => '1.0.0',
            ],
            'paths' => $paths,
            'components' => [
                'schemas' => $schemas,
            ],
        ];
    }

    protected function idParameter(): array
    {
        return [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'integer'],
        ];
    }

    protected function requestBody(string $schemaRef): array
    {
        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => $schemaRef],
                ],
            ],
        ];
    }

    protected function toYaml(array $data, int $indent = 0): string
    {
        $lines = [];
        $prefix = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $lines[] = "{$prefix}{$key}:";
                $lines[] = $this->toYaml($value, $indent + 1);
            } else {
                $lines[] = "{$prefix}{$key}: " . json_encode($value, JSON_UNESCAPED_SLASHES);
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
