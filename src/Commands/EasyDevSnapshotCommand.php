<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\RelationDetector;
use AnasNashat\EasyDev\Contracts\SchemaParser;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class EasyDevSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'easy-dev:snapshot {--ai : Output machine-friendly JSON format for AI integration}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a high-density, token-efficient snapshot of the project models, schemas, and relations.';

    public function __construct(
        protected RelationDetector $relationDetector,
        protected SchemaParser $schemaParser,
        protected Filesystem $files
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isAiMode = $this->option('ai');

        try {
            $models = $this->relationDetector->getAllModels();
            $snapshotData = [];

            foreach ($models as $modelName) {
                $modelClass = $this->qualifyModel($modelName);
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
                            'default' => $col->column_default,
                        ];
                    }
                } catch (\Exception $e) {
                    // Database or table might not exist yet
                }

                // Relations
                $relations = [];
                try {
                    $discovered = $this->relationDetector->discoverRelations($modelName);
                    // Merge direct and inverse
                    $allRelations = array_merge($discovered['direct'] ?? [], $discovered['inverse'] ?? []);
                    foreach ($allRelations as $rel) {
                        $relations[] = [
                            'name' => $rel['method_name'],
                            'type' => $rel['type'],
                            'related' => $rel['related_model_class'] ? class_basename($rel['related_model_class']) : 'Morph',
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip or log relation parsing errors
                }

                $snapshotData[$modelName] = [
                    'table' => $tableName,
                    'columns' => $columns,
                    'relations' => $relations,
                ];
            }

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'project' => basename(base_path()),
                    'models' => $snapshotData,
                ], JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            $this->displayVisualSnapshot($snapshotData);
            return self::SUCCESS;

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        'Ensure your database migrations are fully run: php artisan migrate',
                        'Verify that your .env connection details are correctly configured.',
                        'Try syncing model relations: php artisan easy-dev:sync-relations --all',
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->error('⚠️ Snapshot generation failed: ' . $e->getMessage());
                $this->line('<fg=yellow>💡 Suggestions for Self-Correction:</>');
                $this->line('  1. Ensure your database is migrated: <comment>php artisan migrate</comment>');
                $this->line('  2. Verify your database connection in your <comment>.env</comment> file.');
                $this->line('  3. Run relation syncing to rebuild structure: <comment>php artisan easy-dev:sync-relations --all</comment>');
                $this->newLine();
            }

            return self::FAILURE;
        }
    }

    /**
     * Display a high-density, beautifully styled visual snapshot in the terminal.
     */
    protected function displayVisualSnapshot(array $snapshotData): void
    {
        $this->newLine();
        $this->line('╭─────────────────────────────────────────────────────────────╮');
        $this->line('│   ⚡ <fg=magenta;options=bold>Laravel Easy Dev v3 - AI-Native Model Snapshot</>   │');
        $this->line('╰─────────────────────────────────────────────────────────────╯');
        $this->newLine();

        if (empty($snapshotData)) {
            $this->warn('No models found in app/Models/ directory.');
            $this->line('💡 Tip: Create models using <comment>php artisan easy-dev:crud ModelName</comment> or use <comment>php artisan easy-dev:dream "..."</comment>');
            $this->newLine();
            return;
        }

        foreach ($snapshotData as $model => $info) {
            $this->line("<fg=cyan;options=bold>📦 Model: {$model}</> <fg=gray>(table: {$info['table']})</>");
            
            // Format columns dense list
            $colStrings = [];
            foreach ($info['columns'] as $col) {
                $colStr = "<fg=green>{$col['name']}</>:" . strtolower($col['type']);
                if ($col['nullable']) {
                    $colStr .= '?';
                }
                if ($col['default'] !== null) {
                    $colStr .= "=(" . $col['default'] . ")";
                }
                $colStrings[] = $colStr;
            }
            
            $this->line("  <fg=yellow>Columns:</> " . (empty($colStrings) ? '<fg=gray>none</>' : implode(', ', $colStrings)));

            // Format relations
            $relStrings = [];
            foreach ($info['relations'] as $rel) {
                $relStrings[] = "<fg=magenta>{$rel['name']}</>:<fg=blue>{$rel['type']}</>(<fg=white>{$rel['related']}</>)";
            }
            $this->line("  <fg=yellow>Relations:</> " . (empty($relStrings) ? '<fg=gray>none</>' : implode(', ', $relStrings)));
            $this->newLine();
        }

        $this->line('───────────────────────────────────────────────────────────────');
        $this->line('<fg=gray>💡 LLM Prompting Tip: Copy the output above directly into your prompt context</>');
        $this->newLine();
    }

    /**
     * Get the fully qualified model class name.
     */
    protected function qualifyModel(string $model): string
    {
        return $this->relationDetector->qualifyModel($model);
    }
}
