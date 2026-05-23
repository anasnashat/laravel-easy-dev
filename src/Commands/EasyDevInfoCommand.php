<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use AnasNashat\EasyDev\Services\RelationDetector;
use AnasNashat\EasyDev\Contracts\SchemaParser;
use Illuminate\Support\Str;

class EasyDevInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'easy-dev:info {model : The name of the model to inspect}
                            {--ai : Output machine-friendly JSON format for AI integration}';

    /**
     * The console command description.
     */
    protected $description = 'Output structured Markdown/JSON data detailing a model\'s schema, fields, validation, and relationships.';

    public function __construct(
        protected RelationDetector $relationDetector,
        protected SchemaParser $schemaParser
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = Str::studly($this->argument('model'));
        $isAiMode = $this->option('ai');
        $modelClass = $this->qualifyModel($modelName);

        try {
            if (!class_exists($modelClass)) {
                throw new \Exception("Model class '{$modelClass}' not found. Please ensure the model has been created.");
            }

            $instance = new $modelClass();
            $tableName = $instance->getTable();

            // 1. Column Schemas
            $columns = [];
            $dbColumns = $this->schemaParser->getTableColumns($tableName);
            
            $fillables = $instance->getFillable();
            $hiddens = $instance->getHidden();
            $casts = $instance->getCasts();

            foreach ($dbColumns as $col) {
                $name = $col->column_name;
                $isFillable = in_array($name, $fillables) || empty($fillables); // simplified fillable check
                $isHidden = in_array($name, $hiddens);
                $castType = $casts[$name] ?? 'none';

                $columns[] = [
                    'field' => $name,
                    'type' => $col->data_type,
                    'nullable' => $col->is_nullable === 'YES',
                    'default' => $col->column_default,
                    'fillable' => $isFillable,
                    'hidden' => $isHidden,
                    'cast' => $castType,
                ];
            }

            // 2. Eloquent Relationships
            $relations = [];
            $discovered = $this->relationDetector->discoverRelations($modelName);
            $allRelations = array_merge($discovered['direct'] ?? [], $discovered['inverse'] ?? []);
            foreach ($allRelations as $rel) {
                $relations[] = [
                    'relation' => $rel['method_name'],
                    'type' => $rel['type'],
                    'related_model' => $rel['related_model_class'] ? class_basename($rel['related_model_class']) : 'Morph',
                    'foreign_key' => $rel['foreign_key'] ?? 'default',
                ];
            }

            // 3. Validation Form Requests Check
            $requests = [];
            $storeRequest = "App\\Http\\Requests\\Store{$modelName}Request";
            $updateRequest = "App\\Http\\Requests\\Update{$modelName}Request";

            if (class_exists($storeRequest)) {
                $requests[] = [
                    'class' => $storeRequest,
                    'type' => 'Store',
                    'exists' => true,
                ];
            }
            if (class_exists($updateRequest)) {
                $requests[] = [
                    'class' => $updateRequest,
                    'type' => 'Update',
                    'exists' => true,
                ];
            }

            $infoPayload = [
                'model' => $modelName,
                'table' => $tableName,
                'class' => $modelClass,
                'columns' => $columns,
                'relations' => $relations,
                'requests' => $requests,
            ];

            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'success',
                    'data' => $infoPayload,
                ], JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            $this->displayMarkdownInfo($infoPayload);
            return self::SUCCESS;

        } catch (\Exception $e) {
            if ($isAiMode) {
                $this->output->write(json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'suggestions' => [
                        "Check if the model name is spelled correctly (e.g. User, Post, Product).",
                        "Verify if the model exists in app/Models/.",
                        "If you haven't created the model yet, run: php artisan easy-dev:crud {$modelName}",
                    ]
                ], JSON_PRETTY_PRINT));
            } else {
                $this->newLine();
                $this->error("⚠️ Error: " . $e->getMessage());
                $this->line('<fg=yellow>💡 Suggestions for Self-Correction:</>');
                $this->line("  1. Verify the model file exists in: <comment>app/Models/{$modelName}.php</comment>");
                $this->line("  2. To scaffold a complete new CRUD and Model, run: <comment>php artisan easy-dev:crud {$modelName}</comment>");
                $this->newLine();
            }

            return self::FAILURE;
        }
    }

    /**
     * Display the model details in a gorgeous Markdown table format in the terminal.
     */
    protected function displayMarkdownInfo(array $data): void
    {
        $this->newLine();
        $this->line('╭─────────────────────────────────────────────────────────────╮');
        $this->line("│   ⚡ <fg=magenta;options=bold>Laravel Easy Dev v3 - Model Audit for {$data['model']}</>   │");
        $this->line('╰─────────────────────────────────────────────────────────────╯');
        $this->newLine();

        $this->line("⚙️  <fg=cyan;options=bold>Class:</> {$data['class']}");
        $this->line("🗄️  <fg=cyan;options=bold>Table:</> {$data['table']}");
        $this->newLine();

        // Schema Table Markdown
        $this->line('<fg=yellow;options=bold>### 📊 Schema & Database Column Details</>');
        $this->newLine();
        $this->line('| Field | Type | Nullable | Default | Fillable | Hidden | Cast |');
        $this->line('| :--- | :--- | :--- | :--- | :--- | :--- | :--- |');

        foreach ($data['columns'] as $col) {
            $nullable = $col['nullable'] ? '✅ Yes' : '❌ No';
            $default = $col['default'] !== null ? "`{$col['default']}`" : '*null*';
            $fillable = $col['fillable'] ? '✅' : '❌';
            $hidden = $col['hidden'] ? '👀 Hidden' : 'Visible';
            $cast = $col['cast'] !== 'none' ? "`{$col['cast']}`" : '-';

            $this->line("| **{$col['field']}** | `{$col['type']}` | {$nullable} | {$default} | {$fillable} | {$hidden} | {$cast} |");
        }
        $this->newLine();

        // Relations Markdown Table
        $this->line('<fg=yellow;options=bold>### 🔗 Eloquent Relationships</>');
        $this->newLine();
        if (empty($data['relations'])) {
            $this->line('*No active relationships detected in schema.*');
        } else {
            $this->line('| Relation Method | Relation Type | Target Model | Foreign Key |');
            $this->line('| :--- | :--- | :--- | :--- |');
            foreach ($data['relations'] as $rel) {
                $this->line("| `{$rel['relation']}()` | **{$rel['type']}** | {$rel['related_model']} | `{$rel['foreign_key']}` |");
            }
        }
        $this->newLine();

        // Form Requests Markdown
        $this->line('<fg=yellow;options=bold>### 🛡️ Form Requests & Authorization Policy</>');
        $this->newLine();
        if (empty($data['requests'])) {
            $this->line('*No specific Form Request validation classes found.*');
            $this->line('💡 Run <comment>php artisan easy-dev:crud ' . $data['model'] . '</comment> to auto-generate standard Form Requests.');
        } else {
            foreach ($data['requests'] as $req) {
                $this->line("- **{$req['type']} Request:** `{$req['class']}`");
            }
        }
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
