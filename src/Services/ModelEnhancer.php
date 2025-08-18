<?php

namespace AnasNashat\EasyDev\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use AnasNashat\EasyDev\Services\MigrationParser;

class ModelEnhancer
{
    public function __construct(
        protected Filesystem $files,
        protected MigrationParser $migrationParser
    ) {
    }

    /**
     * Enhance existing model with fillable and relationships.
     */
    public function enhanceModel(string $modelName, array $migrationData): bool
    {
        $modelPath = app_path("Models/{$modelName}.php");
        
        if (!$this->files->exists($modelPath)) {
            return false;
        }

        $content = $this->files->get($modelPath);
        
        // Add fillable if not already present
        $content = $this->addFillableToModel($content, $migrationData['fillable']);
        
        // Add relationships
        $content = $this->addRelationshipsToModel($content, $migrationData['relationships']);
        
        // Add reverse relationships (hasMany, hasOne)
        $reverseRelationships = $this->migrationParser->findReverseRelationships($modelName);
        $content = $this->addRelationshipsToModel($content, $reverseRelationships);

        $this->files->put($modelPath, $content);
        
        return true;
    }

    /**
     * Add fillable property to model if not present or update existing one.
     */
    protected function addFillableToModel(string $content, array $fillable): string
    {
        if (empty($fillable)) {
            return $content;
        }

        // Check if fillable already exists
        if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            // Update existing fillable
            $existingFillable = $this->parseArrayFromString($matches[1]);
            $newFillable = array_unique(array_merge($existingFillable, $fillable));
            
            $fillableString = $this->formatArrayForModel($newFillable);
            $replacement = "protected \$fillable = [\n{$fillableString}\n    ];";
            
            return preg_replace('/protected\s+\$fillable\s*=\s*\[(.*?)\];/s', $replacement, $content);
        } else {
            // Add new fillable property
            $fillableString = $this->formatArrayForModel($fillable);
            $fillableProperty = "\n    /**\n     * The attributes that are mass assignable.\n     */\n    protected \$fillable = [\n{$fillableString}\n    ];\n";
            
            // Find the position to insert fillable (after class declaration)
            if (preg_match('/(class\s+\w+.*?\{)/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $matches[1][1] + strlen($matches[1][0]);
                return substr_replace($content, $fillableProperty, $insertPosition, 0);
            }
        }

        return $content;
    }

    /**
     * Add relationship methods to model.
     */
    protected function addRelationshipsToModel(string $content, array $relationships): string
    {
        if (empty($relationships)) {
            return $content;
        }

        $relationshipMethods = [];

        foreach ($relationships as $relationship) {
            $methodName = $relationship['method_name'];
            
            // Check if method already exists
            if (strpos($content, "function {$methodName}(") !== false) {
                continue;
            }

            $relationshipMethods[] = $this->generateRelationshipMethod($relationship);
        }

        if (!empty($relationshipMethods)) {
            // Add methods before the closing class brace
            $methodsString = "\n" . implode("\n", $relationshipMethods) . "\n";
            $content = preg_replace('/\n}$/', $methodsString . '}', $content);
        }

        return $content;
    }

    /**
     * Generate relationship method code.
     */
    protected function generateRelationshipMethod(array $relationship): string
    {
        $methodName = $relationship['method_name'];
        $relatedModel = $relationship['related_model'];
        $relationType = $relationship['type'];

        $returnType = match ($relationType) {
            'belongsTo' => 'BelongsTo',
            'hasMany' => 'HasMany',
            'hasOne' => 'HasOne',
            'belongsToMany' => 'BelongsToMany',
            default => 'Relation'
        };

        $method = "    /**\n";
        $method .= "     * Get the associated {$relatedModel}.\n";
        $method .= "     */\n";
        $method .= "    public function {$methodName}(): {$returnType}\n";
        $method .= "    {\n";
        $method .= "        return \$this->{$relationType}({$relatedModel}::class);\n";
        $method .= "    }";

        return $method;
    }

    /**
     * Parse array string to actual array.
     */
    protected function parseArrayFromString(string $arrayString): array
    {
        $items = [];
        
        // Remove extra whitespace and split by commas
        $arrayString = preg_replace('/\s+/', ' ', $arrayString);
        $parts = explode(',', $arrayString);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/[\'"]([^\'"]+)[\'"]/', $part, $matches)) {
                $items[] = $matches[1];
            }
        }

        return array_filter($items);
    }

    /**
     * Format array for model file.
     */
    protected function formatArrayForModel(array $items): string
    {
        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = "        '{$item}'";
        }
        
        return implode(",\n", $formatted);
    }

    /**
     * Add necessary use statements to model.
     */
    public function addUseStatements(string $modelName, array $relationships): bool
    {
        $modelPath = app_path("Models/{$modelName}.php");
        
        if (!$this->files->exists($modelPath)) {
            return false;
        }

        $content = $this->files->get($modelPath);
        $useStatements = [];

        // Check what relationship types we need and add corresponding use statements
        $relationshipTypes = array_unique(array_column($relationships, 'type'));
        
        foreach ($relationshipTypes as $type) {
            switch ($type) {
                case 'belongsTo':
                    $useStatements[] = 'use Illuminate\Database\Eloquent\Relations\BelongsTo;';
                    break;
                case 'hasMany':
                    $useStatements[] = 'use Illuminate\Database\Eloquent\Relations\HasMany;';
                    break;
                case 'hasOne':
                    $useStatements[] = 'use Illuminate\Database\Eloquent\Relations\HasOne;';
                    break;
                case 'belongsToMany':
                    $useStatements[] = 'use Illuminate\Database\Eloquent\Relations\BelongsToMany;';
                    break;
            }
        }

        // Add use statements if they don't already exist
        foreach ($useStatements as $useStatement) {
            if (strpos($content, $useStatement) === false) {
                // Find the last use statement and add after it
                if (preg_match('/^use\s+[^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $lastUsePosition = $matches[0][1] + strlen($matches[0][0]);
                    $content = substr_replace($content, "\n" . $useStatement, $lastUsePosition, 0);
                } else {
                    // Add after <?php tag
                    $content = preg_replace('/(<\?php\s*\n)/', '$1' . $useStatement . "\n\n", $content);
                }
            }
        }

        $this->files->put($modelPath, $content);
        
        return true;
    }
}
