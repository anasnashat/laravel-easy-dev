<?php

namespace AnasNashat\EasyDev\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use AnasNashat\EasyDev\Services\RelationDetector;
use AnasNashat\EasyDev\Contracts\SchemaParser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct(
        protected RelationDetector $relationDetector,
        protected SchemaParser $schemaParser
    ) {
    }

    /**
     * Renders the single-page dashboard visual hub.
     */
    public function index()
    {
        return view('easy-dev::dashboard');
    }

    /**
     * Returns a JSON representation of all models, their fields, and relationships.
     */
    public function getModels()
    {
        try {
            $modelsList = $this->relationDetector->getAllModels();
            $modelsData = [];

            foreach ($modelsList as $modelName) {
                $modelClass = $this->qualifyModel($modelName);
                if (!class_exists($modelClass)) {
                    continue;
                }

                $instance = new $modelClass();
                $tableName = $instance->getTable();

                // Get fields
                $columns = [];
                try {
                    $dbColumns = $this->schemaParser->getTableColumns($tableName);
                    $fillables = $instance->getFillable();
                    $casts = $instance->getCasts();

                    foreach ($dbColumns as $col) {
                        $columns[] = [
                            'name' => $col->column_name,
                            'type' => $col->data_type,
                            'nullable' => $col->is_nullable === 'YES',
                            'fillable' => in_array($col->column_name, $fillables) || empty($fillables),
                            'cast' => $casts[$col->column_name] ?? 'string',
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip schema if missing
                }

                // Get relations
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
                    // Skip relations if error
                }

                $modelsData[] = [
                    'name' => $modelName,
                    'table' => $tableName,
                    'columns' => $columns,
                    'relations' => $relations,
                ];
            }

            return response()->json([
                'status' => 'success',
                'models' => $modelsData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoints to compile and run scaffolding tasks dynamically.
     */
    public function scaffold(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:dream,crud',
            'prompt' => 'required_if:action,dream|string',
            'model' => 'required_if:action,crud|string',
            'options' => 'nullable|array',
        ]);

        $action = $request->input('action');

        try {
            if ($action === 'dream') {
                $prompt = $request->input('prompt');
                
                // Call Artisan EasyDevDreamCommand in silent AI mode to get clean execution response
                Artisan::call('easy-dev:dream', [
                    'prompt' => $prompt,
                    '--ai' => true,
                ]);

                $output = Artisan::output();
                
                // Extract JSON if there is surrounding text/progress outputs
                $jsonStart = strpos($output, '{');
                $jsonEnd = strrpos($output, '}');
                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $jsonResponse = json_decode($jsonString, true);
                } else {
                    $jsonResponse = json_decode($output, true);
                }

                if (json_last_error() === JSON_ERROR_NONE) {
                    return response()->json($jsonResponse);
                }

                return response()->json([
                    'status' => 'success',
                    'raw_output' => $output,
                ]);
            }

            if ($action === 'crud') {
                $model = Str::studly($request->input('model'));
                $options = $request->input('options', []);

                $params = [
                    'model' => $model,
                ];

                if (!empty($options['repository'])) {
                    $params['--with-repository'] = true;
                }
                if (!empty($options['service'])) {
                    $params['--with-service'] = true;
                }
                if (!empty($options['api'])) {
                    $params['--api-only'] = true;
                }

                Artisan::call('easy-dev:crud', $params);
                $output = Artisan::output();

                return response()->json([
                    'status' => 'success',
                    'message' => "CRUD for {$model} created successfully!",
                    'output' => $output,
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the fully qualified model class name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = Str::studly($model);
        $rootNamespace = app()->getNamespace();
        return config('easy-dev.model_namespace', $rootNamespace . 'Models\\') . $model;
    }
}
