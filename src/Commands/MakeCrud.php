<?php

namespace PackgeTest\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeCrud extends Command
{
    protected $signature = 'make:crud {name} {--api : Generate API controller} {--routes : Add routes to routes file} {--force : Overwrite existing files} {--relations= : Define relationships (format: "model:type,model:type")}';
    protected $description = 'Generate model, controller with dependency injection, and form requests with smart validation';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $modelVar = Str::camel($name);
        $pluralName = Str::plural($modelVar);
        $this->info("🚀 Creating CRUD for: $name");

        // Step 1: Create model and migration
        if (!$this->option('force') && class_exists("App\\Models\\$name")) {
            if (!$this->confirm("Model $name already exists. Do you want to overwrite it?", false)) {
                $this->info("✅ Skipping model creation.");
            } else {
                Artisan::call("make:model $name -m");
                $this->info("✅ Model and migration created.");
            }
        } else {
            Artisan::call("make:model $name -m");
            $this->info("✅ Model and migration created.");
        }

        // Step 2: Load model & get fillable
        $modelClass = "App\\Models\\$name";
        $modelFile = app_path("Models/{$name}.php");
        if (!class_exists($modelClass) && file_exists($modelFile)) {
            require_once $modelFile;
        }

        // Try to infer fillable fields from the migration if model doesn't have them
        $fillable = $this->getModelFillableFields($name);

        if (empty($fillable)) {
            $this->warn("⚠️ No fillable fields found. Looking for migrations to infer fields...");
            $fillable = $this->inferFieldsFromMigration($name);
        }

        if (empty($fillable)) {
            $this->warn("⚠️ No fields could be determined. Form requests will have empty validation rules.");
            $fillable = [];
        } else {
            $this->info("📋 Found fields: " . implode(", ", $fillable));
        }

        // Detect relationships from database schema
        $relationships = $this->detectRelationships($name);
        if (!empty($relationships)) {
            $this->info("🔄 Detected relationships: " . count($relationships));
            $this->updateModelWithRelationships($name, $relationships);
        }

        // Step 3: Generate request files with smart validation
        $this->generateRequestFile("Store{$name}Request", $fillable);
        $this->generateRequestFile("Update{$name}Request", $fillable, true);

        // Step 4: Generate repository
        if ($this->shouldGenerateRepository()) {
            $this->generateRepository($name, $fillable, $relationships);
        }

        // Step 5: Generate controller with dependency injection
        $controllerPath = app_path("Http/Controllers/{$name}Controller.php");
        file_put_contents($controllerPath, $this->generateController($name, $modelVar, $relationships));
        $this->info("✅ Controller created at: $controllerPath");

        // Step 6: Generate routes if requested
        if ($this->option('routes')) {
            $this->addRoutes($name, $pluralName);
        }

        $this->info("🎉 Done! Your CRUD for '$name' is ready.");
        return 0;
    }

    /**
     * Determine if a repository should be generated
     */
    protected function shouldGenerateRepository()
    {
        return $this->confirm("Would you like to generate a repository for better separation of concerns?", true);
    }

    /**
     * Get model fillable fields if the class exists and is loaded
     */
    protected function getModelFillableFields($name)
    {
        $modelClass = "App\\Models\\$name";

        if (class_exists($modelClass)) {
            $model = new $modelClass();
            return $model->getFillable();
        }

        return [];
    }

    /**
     * Try to infer fields from the migration file
     */
    protected function inferFieldsFromMigration($name)
    {
        $fields = [];
        $migrationDir = database_path('migrations');
        
        if (!is_dir($migrationDir)) {
            $this->warn("⚠️ Migrations directory not found at: $migrationDir");
            return $fields;
        }

        $files = scandir($migrationDir);
        $snakeName = Str::snake(Str::plural($name));
        $singularSnakeName = Str::snake($name);
        $tableName = $snakeName;

        // Try to find migration file for this model
        $migrationFile = null;
        
        foreach ($files as $file) {
            if (
                strpos($file, 'create_' . $snakeName . '_table') !== false || 
                strpos($file, 'create_' . $singularSnakeName . '_table') !== false
            ) {
                $migrationFile = $file;
                break;
            }
        }

        if (!$migrationFile) {
            $this->warn("⚠️ No migration file found for model $name. Looking for other related migrations...");
            
            // Try a more flexible search pattern
            foreach ($files as $file) {
                if (
                    strpos(strtolower($file), strtolower($snakeName)) !== false || 
                    strpos(strtolower($file), strtolower($singularSnakeName)) !== false
                ) {
                    $migrationFile = $file;
                    $this->info("Found potential migration file: $file");
                    break;
                }
            }
            
            if (!$migrationFile) {
                return $fields;
            }
        }

        $content = file_get_contents($migrationDir . '/' . $migrationFile);

        // Extract field definitions using a more comprehensive regex pattern
        // This will match various column types: string, integer, text, boolean, etc.
        $patterns = [
            // Standard column definitions: $table->string('name')
            '/\$table->([a-zA-Z]+)\([\'\"]([a-zA-Z0-9_]+)[\'\"](,|\))/',
            
            // Foreign ID columns: $table->foreignId('user_id')
            '/\$table->foreignId\([\'\"]([a-zA-Z0-9_]+)[\'\"]\)/',
            
            // JSON columns: $table->json('metadata')
            '/\$table->json\([\'\"]([a-zA-Z0-9_]+)[\'\"]\)/',
            
            // Enum columns: $table->enum('status', ['active', 'inactive'])
            '/\$table->enum\([\'\"]([a-zA-Z0-9_]+)[\'\"]\s*,/',
            
            // UUID columns
            '/\$table->uuid\([\'\"]([a-zA-Z0-9_]+)[\'\"]\)/',
            
            // Numeric types with precision: $table->decimal('amount', 8, 2)
            '/\$table->(decimal|double|float)\([\'\"]([a-zA-Z0-9_]+)[\'\"]\s*,/'
        ];

        // Fields to ignore (typically system fields)
        $ignoredFields = [
            'id', 'created_at', 'updated_at', 'deleted_at', 
            'created_by', 'updated_by', 'deleted_by',
            'remember_token', 'email_verified_at'
        ];

        foreach ($patterns as $index => $pattern) {
            preg_match_all($pattern, $content, $matches);
            
            if ($index === 0 && isset($matches[2]) && !empty($matches[2])) {
                // Standard column definitions
                foreach ($matches[2] as $field) {
                    if (!in_array($field, $ignoredFields) && !in_array($field, $fields)) {
                        $fields[] = $field;
                    }
                }
            } elseif ($index === 5 && isset($matches[2]) && !empty($matches[2])) {
                // Decimal/float/double with precision
                foreach ($matches[2] as $field) {
                    if (!in_array($field, $ignoredFields) && !in_array($field, $fields)) {
                        $fields[] = $field;
                    }
                }
            } elseif (isset($matches[1]) && !empty($matches[1])) {
                // All other patterns
                foreach ($matches[1] as $field) {
                    if (!in_array($field, $ignoredFields) && !in_array($field, $fields)) {
                        $fields[] = $field;
                    }
                }
            }
        }

        // If we found any fields, update the model with fillable property
        if (!empty($fields)) {
            $this->updateModelWithFillable($name, $fields);
        }

        return $fields;
    }

    /**
     * Update the model file with fillable property
     */
    protected function updateModelWithFillable($name, $fields)
    {
        $modelPath = app_path("Models/{$name}.php");
        
        if (!file_exists($modelPath)) {
            $this->error("🛑 Model file not found at: $modelPath");
            return;
        }
        
        $content = file_get_contents($modelPath);
        
        // Check if model already has fillable property
        if (strpos($content, 'protected $fillable') !== false) {
            // Update existing fillable property using regex
            $pattern = '/protected\s+\$fillable\s*=\s*\[(.*?)\]\s*;/s';
            $replacement = "protected \$fillable = [\n        '" . implode("',\n        '", $fields) . "'\n    ];";
            $updatedContent = preg_replace($pattern, $replacement, $content);
            
            if ($updatedContent !== $content) {
                file_put_contents($modelPath, $updatedContent);
                $this->info("✅ Updated fillable property in {$name} model with " . count($fields) . " fields");
            }
        } else {
            // Add fillable property
            $fillableProperty = "\n    /**\n     * The attributes that are mass assignable.\n     *\n     * @var array\n     */\n    protected \$fillable = [\n        '" . implode("',\n        '", $fields) . "'\n    ];\n";
            
            // Find the class declaration and add fillable after it
            $pattern = '/(class\s+' . $name . '\s+extends\s+Model\s*(?:{|$))/';
            if (preg_match($pattern, $content)) {
                $updatedContent = preg_replace($pattern, '$1' . $fillableProperty, $content);
                file_put_contents($modelPath, $updatedContent);
                $this->info("✅ Added fillable property to {$name} model with " . count($fields) . " fields");
            } else {
                $this->warn("⚠️ Could not add fillable property to model. Manual update required.");
            }
        }
    }

    /**
     * Detect relationships from database schema
     */
    protected function detectRelationships($name)
    {
        $relationships = [];
        
        // Try to get relationships from the database schema
        try {
            $tableName = Str::snake(Str::plural($name));
            $schema = \DB::select("SHOW CREATE TABLE {$tableName}");
            
            if ($schema && isset($schema[0])) {
                $createTable = $schema[0]->{'Create Table'} ?? null;
                
                if ($createTable) {
                    // Find foreign key constraints
                    preg_match_all("/CONSTRAINT\s+`[^`]+`\s+FOREIGN KEY\s+\(`([^`]+)`\)\s+REFERENCES\s+`([^`]+)`\s+\(`([^`]+)`\)/", 
                        $createTable, $matches, PREG_SET_ORDER);
                        
                    foreach ($matches as $match) {
                        $foreignKey = $match[1] ?? null;
                        $referencedTable = $match[2] ?? null;
                        $referencedColumn = $match[3] ?? null;
                        
                        if ($foreignKey && $referencedTable && $referencedColumn) {
                            $relationships[] = [
                                'field' => $foreignKey,
                                'references' => $referencedColumn,
                                'on' => $referencedTable,
                                'relation_type' => 'belongsTo'
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("Couldn't access database schema: " . $e->getMessage());
        }
        
        // Fallback to migration files if database connection fails
        if (empty($relationships)) {
            $migrationDir = database_path('migrations');
            $files = scandir($migrationDir);
            $snakeName = Str::snake(Str::plural($name));
            
            foreach ($files as $file) {
                if (strpos($file, 'create_' . $snakeName . '_table') !== false) {
                    $content = file_get_contents($migrationDir . '/' . $file);
                    
                    // Look for foreign key definitions in the migration file
                    preg_match_all('/\$table->foreignId\([\'"]([a-zA-Z_]+)[\'"]\)(->constrained\([\'"]([a-zA-Z_]+)[\'"]\))?/', 
                        $content, $matches, PREG_SET_ORDER);
                    
                    foreach ($matches as $match) {
                        $field = $match[1];
                        $referencedTable = isset($match[3]) ? $match[3] : Str::plural(str_replace('_id', '', $field));
                        
                        $relationships[] = [
                            'field' => $field,
                            'references' => 'id',
                            'on' => $referencedTable,
                            'relation_type' => 'belongsTo'
                        ];
                    }
                    
                    // Also look for the older foreign syntax
                    preg_match_all('/\$table->foreign\([\'"]([a-zA-Z_]+)[\'"]\)->references\([\'"]([a-zA-Z_]+)[\'"]\)->on\([\'"]([a-zA-Z_]+)[\'"]/', 
                        $content, $matches, PREG_SET_ORDER);
                    
                    foreach ($matches as $match) {
                        $field = $match[1];
                        $referencedColumn = $match[2];
                        $referencedTable = $match[3];
                        
                        $relationships[] = [
                            'field' => $field,
                            'references' => $referencedColumn,
                            'on' => $referencedTable,
                            'relation_type' => 'belongsTo'
                        ];
                    }
                    
                    break;
                }
            }
        }
        
        // Try to detect reverse relationships (hasMany, hasOne)
        try {
            $tableName = Str::snake(Str::plural($name));
            $tables = \DB::select("SHOW TABLES");
            $tableKey = "Tables_in_" . env('DB_DATABASE');
            
            foreach ($tables as $tableObj) {
                $table = $tableObj->$tableKey;
                
                // Skip if same table
                if ($table === $tableName) continue;
                
                $schema = \DB::select("SHOW CREATE TABLE {$table}");
                
                if ($schema && isset($schema[0])) {
                    $createTable = $schema[0]->{'Create Table'} ?? null;
                    
                    if ($createTable) {
                        // Find foreign keys referencing our table
                        $pattern = "/CONSTRAINT\s+`[^`]+`\s+FOREIGN KEY\s+\(`([^`]+)`\)\s+REFERENCES\s+`{$tableName}`\s+\(`([^`]+)`\)/";
                        preg_match_all($pattern, $createTable, $matches, PREG_SET_ORDER);
                        
                        foreach ($matches as $match) {
                            $foreignKey = $match[1] ?? null;
                            $referencedColumn = $match[2] ?? null;
                            
                            // This table has a foreign key to our model, so our model has a hasMany relationship
                            $relationships[] = [
                                'field' => $referencedColumn,
                                'references' => $foreignKey,
                                'on' => $table,
                                'relation_type' => 'hasMany'
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently ignore errors in reverse relationship detection
        }
        
        return $relationships;
    }

    /**
     * Update model with relationships
     */
    protected function updateModelWithRelationships($name, $relationships)
    {
        $modelPath = app_path("Models/{$name}.php");
        $content = file_get_contents($modelPath);
        
        $relationsCode = "";
        $addedRelations = [];
        
        foreach ($relationships as $relationship) {
            $relationType = $relationship['relation_type'] ?? 'belongsTo';
            
            if ($relationType === 'belongsTo') {
                // For belongsTo: user() belongs to users table through user_id
                $methodName = Str::camel(Str::singular($relationship['on']));
                $relatedModel = Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} that owns this {$name}.
     */
    public function {$methodName}()
    {
        return \$this->belongsTo({$relatedModel}::class, '{$relationship['field']}');
    }
PHP;
            } else {
                // For hasMany/hasOne: users() has many user through user_id
                $methodName = Str::camel(Str::plural($relationship['on']));
                $relatedModel = Str::studly(Str::singular($relationship['on']));
                
                if (in_array($methodName, $addedRelations)) continue;
                $addedRelations[] = $methodName;
                
                $relationMethod = <<<PHP

    /**
     * Get the {$methodName} for this {$name}.
     */
    public function {$methodName}()
    {
        return \$this->hasMany({$relatedModel}::class, '{$relationship['references']}', '{$relationship['field']}');
    }
PHP;
            }
            
            // Only add relationship if it doesn't already exist
            if (strpos($content, "public function $methodName()") === false) {
                $relationsCode .= $relationMethod;
            }
        }
        
        // Add relationships to model before the closing bracket
        if (!empty($relationsCode)) {
            $content = preg_replace('/}(\s*)$/', $relationsCode . "\n}$1", $content);
            file_put_contents($modelPath, $content);
            $this->info("✅ Added " . count($addedRelations) . " relationships to model");
        }
    }

    /**
     * Generate repository class
     */
    protected function generateRepository($name, $fields, $relationships = [])
    {
        // Create repositories directory if it doesn't exist
        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'));
        }

        // Create interfaces directory if it doesn't exist
        if (!File::exists(app_path('Repositories/Interfaces'))) {
            File::makeDirectory(app_path('Repositories/Interfaces'));
        }

        // Generate repository interface
        $interfacePath = app_path("Repositories/Interfaces/{$name}RepositoryInterface.php");
        file_put_contents($interfacePath, $this->generateRepositoryInterface($name));
        $this->info("✅ Repository interface created at: $interfacePath");

        // Generate repository implementation
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        file_put_contents($repositoryPath, $this->generateRepositoryClass($name, $relationships));
        $this->info("✅ Repository implementation created at: $repositoryPath");

        // Register repository binding in AppServiceProvider
        $this->registerRepositoryBinding($name);
    }

    /**
     * Generate repository interface
     */
    protected function generateRepositoryInterface($name)
    {
        $modelVar = Str::camel($name);

        return <<<PHP
<?php

namespace App\Repositories\Interfaces;

use App\Models\\$name;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface {$name}RepositoryInterface
{

    /**
     * Get all records with relations
     *
     * @param array \$relations
     * @param int|null \$perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getWithRelations(array \$relations = [], ?int \$perPage = null);

    /**
     * Get all records
     *
     * @return Collection
     */
    public function getAll() : Collection;

    /**
     * Find a record by ID
     *
     * @param int \$id
     * @return $name|null
     */
    public function findById(\$id): ?$name;

    /**
     * Create a new record
     *
     * @param array \$data
     * @return $name
     */
    public function create(array \$data): $name;

    /**
     * Update an existing record
     *
     * @param $name \${$modelVar}
     * @param array \$data
     * @return $name
     */
    public function update($name \${$modelVar}, array \$data): $name;

    /**
     * Delete a record
     *
     * @param $name \${$modelVar}
     * @return bool
     */
    public function delete($name \${$modelVar}): bool;
}
PHP;
    }

    /**
     * Generate repository class
     */
    protected function generateRepositoryClass($name, $relationships): string
    {
        $modelVar = Str::camel($name);
        
        // Extract relation names for the repository implementation
        $relationNames = [];
        foreach ($relationships as $relationship) {
            $relationType = $relationship['relation_type'] ?? 'belongsTo';
            
            if ($relationType === 'belongsTo') {
                $relationNames[] = Str::camel(Str::singular($relationship['on']));
            } else {
                $relationNames[] = Str::camel(Str::plural($relationship['on']));
            }
        }
        
        // Convert relations to PHP array syntax
        $relationsArray = empty($relationNames) ? '[]' : "['".implode("', '", $relationNames)."']";

        return <<<PHP
<?php

namespace App\Repositories;

use App\Models\\$name;
use App\Repositories\Interfaces\\{$name}RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class {$name}Repository implements {$name}RepositoryInterface
{
    /**
     * @var $name
     */
    protected \$model;

    /**
     * Constructor
     *
     * @param $name \$model
     */
    public function __construct($name \$model)
    {
        \$this->model = \$model;
    }

    /**
     * Get all records with relations
     *
     * @param array \$relations
     * @param int|null \$perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getWithRelations(array \$relations = [], ?int \$perPage = null)
    {
        \$query = \$this->model->query();
        
        // Apply relations
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        } else {
            // Default relations if none specified
            \$query->with($relationsArray);
        }
        
        // Apply pagination if perPage is specified
        if (\$perPage !== null) {
            return \$query->paginate(\$perPage);
        }
        
        return \$query->get();
    }

    /**
     * Get all records
     *
     * @return Collection
     */
    public function getAll() : Collection
    {
        return \$this->model->all();
    }

    /**
     * Find a record by ID
     *
     * @param int \$id
     * @return $name|null
     */
    public function findById(\$id) : ?$name
    {
        return \$this->model->find(\$id);
    }

    /**
     * Create a new record
     *
     * @param array \$data
     * @return $name
     */
    public function create(array \$data): $name
    {
        return DB::transaction(function () use (\$data) {
            return \$this->model->create(\$data);
        });
    }

    /**
     * Update an existing record
     *
     * @param $name \${$modelVar}
     * @param array \$data
     * @return $name
     */
    public function update($name \${$modelVar}, array \$data) : $name
    {
        return DB::transaction(function () use (\${$modelVar}, \$data) {
            \${$modelVar}->update(\$data);
            return \${$modelVar};
        });
    }

    /**
     * Delete a record
     *
     * @param $name \${$modelVar})
     * @return bool
     */
    public function delete($name \${$modelVar}) : bool
    {
        return DB::transaction(function () use (\${$modelVar}) {
            return \${$modelVar}->delete();
        });
    }
}
PHP;
    }

    /**
     * Register repository binding in AppServiceProvider
     */
    protected function registerRepositoryBinding($name)
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        $content = file_get_contents($providerPath);

        // Check if binding already exists
        if (strpos($content, "{$name}RepositoryInterface::class") !== false) {
            $this->info("✓ Repository binding already exists in AppServiceProvider");
            return;
        }

        // Add repository binding to register method
        $registerMethod = "public function register(): void\n    {";
        $binding = "\n        \$this->app->bind(\\App\\Repositories\\Interfaces\\{$name}RepositoryInterface::class, \\App\\Repositories\\{$name}Repository::class);";

        $content = str_replace($registerMethod, $registerMethod . $binding, $content);
        file_put_contents($providerPath, $content);

        $this->info("✅ Repository binding added to AppServiceProvider");
    }

    /**
     * Add routes to appropriate routes file
     */
    protected function addRoutes($name, $pluralName)
    {
        $routeFile = $this->option('api') ? 'api.php' : 'web.php';
        $routePath = base_path("routes/$routeFile");

        if (file_exists($routePath)) {
            $kebabPlural = Str::kebab(Str::plural($name));
            $routeCode = "Route::resource('$kebabPlural', App\Http\Controllers\\{$name}Controller::class);";
            $fileContent = file_get_contents($routePath);

            // Check if route already exists
            if (strpos($fileContent, $routeCode) !== false) {
                $this->warn("⚠️ Route for '$kebabPlural' already exists in $routeFile");
                return;
            }

            // Add a newline at the beginning if content doesn't end with one
            if (substr($fileContent, -1) !== "\n") {
                $routeCode = PHP_EOL . $routeCode;
            }

            file_put_contents($routePath, $fileContent . PHP_EOL . $routeCode);
            $this->info("✅ Routes added to $routeFile");
        } else {
            $this->error("⚠️ Route file $routeFile not found");
        }
    }

    /**
     * Generate request file with intelligent validation rules
     */
    protected function generateRequestFile($className, $fields, $isUpdate = false)
    {
        $namespace = "App\\Http\\Requests";
        $requestsDir = app_path("Http/Requests");
        $classPath = $requestsDir . "/{$className}.php";

        // Create Requests directory if it doesn't exist
        if (!File::exists($requestsDir)) {
            File::makeDirectory($requestsDir, 0755, true);
            $this->info("📁 Created Requests directory: $requestsDir");
        }

        try {
            $rules = '';
            foreach ($fields as $field) {
                // Determine validation rules based on field name
                $rule = $this->getValidationRules($field, $isUpdate);
                $rules .= "            '$field' => '$rule',\n";
            }

            $content = <<<PHP
<?php

namespace $namespace;

use Illuminate\Foundation\Http\FormRequest;

class $className extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
$rules
        ];
    }
}
PHP;

            file_put_contents($classPath, $content);
            $this->info("📝 Generated: $classPath");
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate request class: {$e->getMessage()}");
        }
    }

    /**
     * Get intelligent validation rules based on field name
     */
    protected function getValidationRules($field, $isUpdate = false)
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        // Email fields
        if (strpos($field, 'email') !== false) {
            return "$required|email|max:255";
        }

        // Password fields
        if (strpos($field, 'password') !== false) {
            return "$required|string|min:8";
        }

        // URL fields
        if (strpos($field, 'url') !== false || strpos($field, 'link') !== false) {
            return "$required|url|max:255";
        }

        // Date fields
        if (strpos($field, 'date') !== false || strpos($field, 'time') !== false) {
            return "$required|date";
        }

        // Boolean fields
        if (strpos($field, 'is_') === 0 || strpos($field, 'has_') === 0 || $field === 'active' || $field === 'status') {
            return "$required|boolean";
        }

        // Numeric fields
        if (
            strpos($field, 'price') !== false ||
            strpos($field, 'amount') !== false ||
            strpos($field, 'cost') !== false ||
            strpos($field, 'quantity') !== false ||
            strpos($field, 'number') !== false
        ) {
            return "$required|numeric";
        }

        // Integer fields
        if (
            strpos($field, 'count') !== false ||
            strpos($field, 'id') !== false ||
            strpos($field, '_id') !== false
        ) {
            return "$required|integer";
        }

        // Default to string
        return "$required|string|max:255";
    }

    /**
     * Generate controller with dependency injection
     */
    protected function generateController($name, $modelVar, $relationships = [])
    {
        $storeRequest = "Store{$name}Request";
        $updateRequest = "Update{$name}Request";
        $pluralName = Str::plural($modelVar);
        $useRepository = $this->shouldGenerateRepository();
        
        // Extract relation names for use in the getWithRelations method
        $relationNames = [];
        foreach ($relationships as $relationship) {
            $relationType = $relationship['relation_type'] ?? 'belongsTo';
            
            if ($relationType === 'belongsTo') {
                $relationNames[] = Str::camel(Str::singular($relationship['on']));
            } else {
                $relationNames[] = Str::camel(Str::plural($relationship['on']));
            }
        }
        
        // Convert relations to PHP array syntax
        $relationsArray = empty($relationNames) ? '[]' : "['".implode("', '", $relationNames)."']";

        $repositoryImport = $useRepository ?
            "use App\\Repositories\\Interfaces\\{$name}RepositoryInterface;" : '';

        $repositoryProperty = $useRepository ?
            "    /**\n     * The {$name} repository instance.\n     */\n    protected \${$modelVar}Repository;\n\n" : '';

        $constructorParams = $useRepository ?
            "{$name}RepositoryInterface \${$modelVar}Repository" : "{$name} \$model";

        $constructorAssignment = $useRepository ?
            "        \$this->{$modelVar}Repository = \${$modelVar}Repository;" : "        \$this->model = \$model;";

        $modelProperty = $useRepository ? '' :
            "    /**\n     * The {$name} model instance.\n     */\n    protected \$model;\n\n";

        // Methods vary based on repository or direct model usage
        if ($useRepository) {
            $indexMethod = "        return \$this->{$modelVar}Repository->getAll();";
            $storeMethod = "        \$validated = \$request->validated();\n        return \$this->{$modelVar}Repository->create(\$validated);";
            $showMethod = "        return \${$modelVar};";
            $updateMethod = "        \$validated = \$request->validated();\n        return \$this->{$modelVar}Repository->update(\${$modelVar}, \$validated);";
            $destroyMethod = "        \$this->{$modelVar}Repository->delete(\${$modelVar});\n        return response()->noContent();";
            $withRelationsMethod = "        \$data = \$this->{$modelVar}Repository->getWithRelations($relationsArray, 15);\n\n        return response()->json(['data' => \$data]);";
        } else {
            $indexMethod = "        return \$this->model->all();";
            $storeMethod = "        \$validated = \$request->validated();\n
        return DB::transaction(function () use (\$validated) {
            return \$this->model->create(\$validated);
        });";
            $showMethod = "        return \${$modelVar};";
            $updateMethod = "        \$validated = \$request->validated();\n
        return DB::transaction(function () use (\${$modelVar}, \$validated) {
            \${$modelVar}->update(\$validated);
            return \${$modelVar};
        });";
            $destroyMethod = "        return DB::transaction(function () use (\${$modelVar}) {
            \${$modelVar}->delete();
            return response()->noContent();
        });";
            $withRelationsMethod = "        \$data = \$this->model->with($relationsArray)->paginate(15);\n\n        return response()->json(['data' => \$data]);";
        }

        // Determine the response format based on the API option
        if ($this->option('api')) {
            $indexMethod = "        \$data = " . str_replace('return', '', $indexMethod) . ";\n        return response()->json(['data' => \$data]);";
            $storeMethod = "        \$validated = \$request->validated();\n
        \$created = " . ($useRepository ? "\$this->{$modelVar}Repository->create(\$validated)" : "DB::transaction(function () use (\$validated) {
            return \$this->model->create(\$validated);
        })") . ";\n
        return response()->json([
            'message' => '{$name} created successfully',
            'data' => \$created
        ], 201);";
            $showMethod = "        return response()->json(['data' => \${$modelVar}]);";
            $updateMethod = "        \$validated = \$request->validated();\n
        \$updated = " . ($useRepository ?
                    "\$this->{$modelVar}Repository->update(\${$modelVar}, \$validated)" :
                    "DB::transaction(function () use (\${$modelVar}, \$validated) {
            \${$modelVar}->update(\$validated);
            return \${$modelVar};
        })") . ";\n
        return response()->json([
            'message' => '{$name} updated successfully',
            'data' => \$updated
        ]);";
            $destroyMethod = "        " . ($useRepository ?
                    "\$this->{$modelVar}Repository->delete(\${$modelVar});" :
                    "DB::transaction(function () use (\${$modelVar}) {
            \${$modelVar}->delete();
        });") . "\n
        return response()->json([
            'message' => '{$name} deleted successfully'
        ]);";
            // No need to modify withRelationsMethod as it already returns JSON
        }

        // Addition of route for getWithRelations
        $additionalRoutesComment = "// Add this route to your routes file:\n";
        $additionalRoutesComment .= "// Route::get('/" . Str::kebab(Str::plural($name)) . "/with-relations', [App\\Http\\Controllers\\{$name}Controller::class, 'getWithRelations']);";

        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\$name;
use App\Http\Requests\\$storeRequest;
use App\Http\Requests\\$updateRequest;
$repositoryImport
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// $additionalRoutesComment

class {$name}Controller extends Controller
{
$modelProperty$repositoryProperty    /**
     * Create a new controller instance.
     */
    public function __construct($constructorParams)
    {
$constructorAssignment
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
$indexMethod
    }

    /**
     * Get resources with relations
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWithRelations(): JsonResponse
    {
$withRelationsMethod
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \\$storeRequest  \$request
     * @return \Illuminate\Http\Response
     */
    public function store($storeRequest \$request)
    {
$storeMethod
    }

    /**
     * Display the specified resource.
     *
     * @param  \\App\\Models\\$name  \${$modelVar}
     * @return \Illuminate\Http\Response
     */
    public function show($name \${$modelVar})
    {
$showMethod
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \\$updateRequest  \$request
     * @param  \\App\\Models\\$name  \${$modelVar}
     * @return \Illuminate\Http\Response
     */
    public function update($updateRequest \$request, $name \${$modelVar})
    {
$updateMethod
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \\App\\Models\\$name  \${$modelVar}
     * @return \Illuminate\Http\Response
     */
    public function destroy($name \${$modelVar})
    {
$destroyMethod
    }
}
PHP;
    }
}