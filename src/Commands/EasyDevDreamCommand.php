<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class EasyDevDreamCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'easy-dev:dream {prompt : The natural language specification for the entity and schema}
                            {--ai : Output machine-friendly JSON format for AI integration}
                            {--dry-run : Only show plans without making changes}
                            {--module= : Nest generated files inside a modular layout}
                            {--preset= : Use a pre-configured architecture preset (e.g. clean)}';

    /**
     * The console command description.
     */
    protected $description = 'Scaffold full feature models, migrations, CRUD, and relationship connections from a natural language prompt.';

    public function __construct(
        protected Filesystem $files,
        protected \AnasNashat\EasyDev\Services\FileGenerator $generator
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $prompt = $this->argument('prompt');
        $isAiMode = $this->option('ai');
        $dryRun = $this->option('dry-run');

        try {
            $parsed = $this->parsePrompt($prompt);

            if ($isAiMode) {
                if ($dryRun) {
                    $this->output->write(json_encode([
                        'status' => 'success',
                        'dry_run' => true,
                        'plans' => $parsed,
                    ], JSON_PRETTY_PRINT));
                    return self::SUCCESS;
                }

                $executed = $this->executeScaffolding($parsed);
                $this->output->write(json_encode([
                    'status' => 'success',
                    'executed' => true,
                    'details' => $executed,
                ], JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            // Regular interactive CLI output
            $this->displayVisualBanner();
            $this->displayPlans($parsed);

            if ($dryRun) {
                $this->info("🔍 Dry run completed successfully. No changes were made.");
                return self::SUCCESS;
            }

            if ($this->confirm('🚀 Would you like to compile and execute this scaffolding plan now?', true)) {
                $executed = $this->executeScaffolding($parsed);
                $this->newLine();
                $this->info("🎉 Scaffolding compiled and built successfully!");
                $this->newLine();
                
                $this->line('<fg=yellow;options=bold>Created/Modified Files:</>');
                foreach ($executed['files'] as $file) {
                    $this->line("  ✓ {$file}");
                }

                $this->newLine();
                $this->line("💡 Next steps: run <comment>php artisan migrate</comment> to apply changes to database.");
            } else {
                $this->warn('Scaffolding aborted.');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        "Rephrase your prompt to highlight the model noun clearly.",
                        "E.g., 'Create customer subscriptions with price:decimal connected to users and products'",
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->error('⚠️ Scaffolding plan parsing failed: ' . $e->getMessage());
                $this->line('<fg=yellow>💡 Suggestions for Self-Correction:</>');
                $this->line("  1. Rephrase your prompt to explicitly state the noun entity (e.g. 'Create customer subscriptions...')");
                $this->line("  2. Specify fields clearly using format 'name:type' (e.g. status:string, count:integer)");
                $this->newLine();
            }

            return self::FAILURE;
        }
    }

    /**
     * Parses the natural language prompt into structured scaffolding parameters.
     */
    protected function parsePrompt(string $prompt): array
    {
        // Trim and remove leading articles (a, an, the) at the start of the prompt
        $cleanPrompt = preg_replace('/^(?:a|an|the)\s+/i', '', trim($prompt));

        // 1. Identify Model Name
        // Match: create/add/new [a/an/new] [entity_name]
        $modelName = 'DreamModel';
        if (preg_match('/(?:create|add|generate|make|new)\s+(?:a\s+|an\s+|new\s+)*([a-zA-Z\s_]+)/i', $cleanPrompt, $matches)) {
            // Take the words until "connected" or "with" or other keywords
            $entityStr = trim(preg_split('/(?:connected|belongs|related|with|fields|has|and\s+)/i', $matches[1])[0]);
            $modelName = Str::studly(Str::singular($entityStr));
        } else {
            // Fallback to first word if no verb matches
            $words = explode(' ', trim($cleanPrompt));
            if (!empty($words)) {
                $modelName = Str::studly(Str::singular($words[0]));
            }
        }

        // 2. Identify Related Models
        $relations = [];
        if (preg_match('/(?:connected to|belongs to|related to|linked to|associated with)\s+([a-zA-Z_,\s]+)/i', $prompt, $matches)) {
            // Split by "and" or commas
            $relParts = preg_split('/(?:and|,)/i', $matches[1]);
            foreach ($relParts as $part) {
                $cleanPart = trim($part);
                if (!empty($cleanPart)) {
                    $relations[] = Str::studly(Str::singular($cleanPart));
                }
            }
        }

        // 3. Identify Fields & Types
        // Match patterns like: name:type or "name as type"
        $fields = [];
        
        // Match field:type patterns (e.g. status:string, price:integer)
        if (preg_match_all('/([a-zA-Z_0-9]+):([a-zA-Z]+)/', $prompt, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fields[trim($match[1])] = trim($match[2]);
            }
        }

        // Match "field as type" patterns (e.g. status as string)
        if (preg_match_all('/([a-zA-Z_0-9]+)\s+as\s+([a-zA-Z]+)/i', $prompt, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fields[trim($match[1])] = trim($match[2]);
            }
        }

        return [
            'model' => $modelName,
            'relations' => $relations,
            'fields' => $fields,
        ];
    }

    /**
     * Executes the scaffolding steps.
     */
    protected function executeScaffolding(array $plan): array
    {
        $model = $plan['model'];
        $fields = $plan['fields'];
        $relations = $plan['relations'];

        $module = $this->option('module');
        $preset = $this->option('preset');

        $createdFiles = [];

        $crudOptions = [
            'model' => $model,
            '--api' => true,
            '--with-repository' => true,
            '--with-service' => true,
        ];

        if ($module) {
            $crudOptions['--module'] = $module;
        }

        if ($preset) {
            $crudOptions['--preset'] = $preset;
        }

        // 1. Call easy-dev:crud to generate scaffold classes
        $this->callSilent('easy-dev:crud', $crudOptions);

        $modelPath = $this->generator->resolveOutputPath('models', "{$model}.php", null, $module, $preset);
        $repoPath = $this->generator->resolveOutputPath('repositories', "{$model}Repository.php", null, $module, $preset);
        $servicePath = $this->generator->resolveOutputPath('services', "{$model}Service.php", null, $module, $preset);
        $controllerPath = $this->generator->resolveOutputPath('api_controllers', "{$model}ApiController.php", null, $module, $preset);

        $createdFiles[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $modelPath);
        $createdFiles[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $repoPath);
        $createdFiles[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $servicePath);
        $createdFiles[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $controllerPath);

        // Also track interface files if preset is clean
        if ($preset === 'clean') {
            $repoInterfacePath = $this->generator->resolveOutputPath('repository_contracts', "{$model}RepositoryInterface.php", null, $module, $preset);
            $serviceInterfacePath = $this->generator->resolveOutputPath('service_contracts', "{$model}ServiceInterface.php", null, $module, $preset);
            $createdFiles[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $repoInterfacePath);
            $createdFiles[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $serviceInterfacePath);
        }

        // 2. Enhance migration file with columns and foreign keys
        $tableName = Str::snake(Str::plural($model));
        $migrationsPath = database_path('migrations');
        if ($this->files->exists($migrationsPath)) {
            $files = $this->files->files($migrationsPath);
            $targetMigrationFile = null;
            
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), "create_{$tableName}_table")) {
                    $targetMigrationFile = $file->getRealPath();
                    break;
                }
            }

            if ($targetMigrationFile) {
                $migrationContent = $this->files->get($targetMigrationFile);

                // Build migration columns string
                $columnLines = [];
                foreach ($fields as $fieldName => $fieldType) {
                    $laravelType = $this->mapLaravelType($fieldType);
                    $line = "            \$table->{$laravelType}('{$fieldName}')";
                    if (in_array($laravelType, ['string', 'text'])) {
                        $line .= '->nullable()';
                    }
                    $columnLines[] = $line . ';';
                }

                // Add foreign keys
                foreach ($relations as $relModel) {
                    $fkName = Str::snake(Str::singular($relModel)) . '_id';
                    $fkTable = Str::snake(Str::plural($relModel));
                    $columnLines[] = "            \$table->foreignId('{$fkName}')->constrained('{$fkTable}')->cascadeOnDelete();";
                }

                if (!empty($columnLines)) {
                    $columnsCode = implode("\n", $columnLines);
                    // Insert right after $table->id();
                    $newContent = str_replace(
                        '$table->id();',
                        "\$table->id();\n{$columnsCode}",
                        $migrationContent
                    );

                    $this->files->put($targetMigrationFile, $newContent);
                    $createdFiles[] = "database/migrations/" . basename($targetMigrationFile) . " (enhanced columns)";
                }
            }
        }

        // 3. Try to synchronize relationships
        try {
            $this->callSilent('easy-dev:sync-relations', [
                'model' => $model,
            ]);
            $createdFiles[] = "app/Models/{$model}.php (synced relationship models)";
        } catch (\Exception $e) {
            // Relational db schema might not be written yet, skips gracefully
        }

        return [
            'model' => $model,
            'files' => array_unique($createdFiles),
        ];
    }

    /**
     * Map common English phrases or types to Laravel migration types.
     */
    protected function mapLaravelType(string $type): string
    {
        $type = strtolower($type);
        $map = [
            'int' => 'integer',
            'integer' => 'integer',
            'number' => 'integer',
            'float' => 'float',
            'double' => 'double',
            'decimal' => 'decimal',
            'price' => 'decimal',
            'amount' => 'decimal',
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'active' => 'boolean',
            'text' => 'text',
            'string' => 'string',
            'varchar' => 'string',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'dateTime',
            'timestamp' => 'timestamp',
            'json' => 'json',
        ];

        return $map[$type] ?? 'string';
    }

    /**
     * Displays a gorgeous ASCII art welcome banner.
     */
    protected function displayVisualBanner(): void
    {
        $this->newLine();
        $this->line('╭─────────────────────────────────────────────────────────────╮');
        $this->line('│   ✨ <fg=magenta;options=bold>Laravel Easy Dev v3 - AI Natural Language Dream</>    │');
        $this->line('╰─────────────────────────────────────────────────────────────╯');
        $this->newLine();
    }

    /**
     * Displays the compiled blueprint plans to the user before running them.
     */
    protected function displayPlans(array $parsed): void
    {
        $this->info("🎯 Compiled Scaffolding Blueprint Plan:");
        $this->newLine();
        
        $this->line("📦 <fg=cyan;options=bold>Entity (Model Name):</> {$parsed['model']}");
        $this->line("🗄️  <fg=cyan;options=bold>Database Table:</> " . Str::snake(Str::plural($parsed['model'])));
        
        $this->newLine();
        $this->line("<fg=yellow;options=bold>⚡ Schema Fields:</>");
        if (empty($parsed['fields'])) {
            $this->line("  <fg=gray>No standard fields parsed (default id/timestamps only)</>");
        } else {
            foreach ($parsed['fields'] as $field => $type) {
                $laravelType = $this->mapLaravelType($type);
                $this->line("  ├── <fg=green>{$field}</> : <fg=blue>{$laravelType}</>");
            }
        }

        $this->newLine();
        $this->line("<fg=magenta;options=bold>🔗 Eloquent Connections (Foreign Keys):</>");
        if (empty($parsed['relations'])) {
            $this->line("  <fg=gray>No foreign key connections specified</>");
        } else {
            foreach ($parsed['relations'] as $rel) {
                $fkName = Str::snake(Str::singular($rel)) . '_id';
                $fkTable = Str::snake(Str::plural($rel));
                $this->line("  ├── BelongsTo <fg=magenta>{$rel}</> (adds foreign key <fg=white>`{$fkName}`</> references <fg=white>`{$fkTable}`</>)");
            }
        }
        $this->newLine();
    }
}
