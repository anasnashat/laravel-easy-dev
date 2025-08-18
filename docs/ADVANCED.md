# Laravel Easy Dev - Advanced Usage Guide

This guide covers advanced features and use cases for Laravel Easy Dev package.

## 🏗️ Architecture Patterns

### Repository Pattern

The Repository pattern provides a layer of abstraction between your business logic and data access logic.

#### Basic Repository Generation

```bash
php artisan easy-dev:repository User
```

Generated files:
- `app/Repositories/UserRepository.php`
- `app/Repositories/Contracts/UserRepositoryInterface.php`

#### Repository Structure

```php
<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {}

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
```

### Service Layer Pattern

The Service layer contains business logic and coordinates between controllers and repositories.

#### Service Generation

```bash
php artisan easy-dev:crud Product --with-service --with-repository
```

#### Service Structure

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\ProductServiceInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        protected ProductRepositoryInterface $repository
    ) {}

    public function createProduct(array $data): Product
    {
        // Business logic validation
        $this->validateBusinessRules($data);
        
        // Transform data if needed
        $transformedData = $this->transformData($data);
        
        return $this->repository->create($transformedData);
    }

    protected function validateBusinessRules(array $data): void
    {
        // Custom business validation
        if (isset($data['price']) && $data['price'] < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }
}
```

## 🔄 Relationship Detection

### Automatic Detection

The package automatically detects relationships from:
1. Foreign key constraints in database
2. Migration file analysis
3. Field naming conventions

```bash
# Auto-detect all relationships
php artisan easy-dev:sync-relations

# Detect for specific model
php artisan easy-dev:sync-relations User
```

### Supported Relationship Types

#### belongsTo Relationships

Detected from:
- `user_id` field → `belongsTo(User::class)`
- `category_id` field → `belongsTo(Category::class)`
- Foreign key constraints

#### hasMany Relationships

Detected by reverse lookup:
- If `posts` table has `user_id` → `User hasMany Post`

#### Polymorphic Relationships

Detected from:
- `commentable_id` + `commentable_type` → `morphTo()`
- Migration `morphs()` method

```php
// Generated polymorphic relationships
public function commentable()
{
    return $this->morphTo();
}

public function comments()
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

## 🎨 Customization

### Stub Templates

Publish and customize stub templates:

```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

Available stubs:
- `model.enhanced.stub` - Enhanced model template
- `controller.api.stub` - API controller template
- `controller.web.stub` - Web controller template
- `repository.stub` - Repository template
- `service.stub` - Service template

### Configuration

Publish configuration:

```bash
php artisan vendor:publish --tag=easy-dev-config
```

Configuration options:

```php
<?php

return [
    // File generation paths
    'paths' => [
        'models' => app_path('Models'),
        'controllers' => app_path('Http/Controllers'),
        'repositories' => app_path('Repositories'),
        'services' => app_path('Services'),
    ],
    
    // Default options
    'defaults' => [
        'with_repository' => false,
        'with_service' => false,
        'generate_tests' => false,
    ],
    
    // Template customization
    'stubs' => [
        'model' => 'model.enhanced',
        'controller' => 'controller.api',
    ],
];
```

## 🧪 Testing Integration

### Test Generation

Generate tests along with CRUD:

```bash
php artisan easy-dev:crud Product --with-tests
```

Generated test files:
- `tests/Feature/ProductControllerTest.php`
- `tests/Unit/ProductRepositoryTest.php`
- `tests/Unit/ProductServiceTest.php`

### Test Structure

```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    /** @test */
    public function it_can_list_products()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
                ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_a_product()
    {
        $data = [
            'name' => 'Test Product',
            'price' => 99.99,
        ];

        $response = $this->postJson('/api/products', $data);

        $response->assertCreated()
                ->assertJsonFragment($data);
    }
}
```

## 🌐 API Development

### API-Only Generation

```bash
php artisan easy-dev:crud Product --api-only
```

Features:
- JSON responses
- API routes only
- Resource collections
- Proper HTTP status codes

### API Response Structure

```php
// Success responses
{
    "data": {
        "id": 1,
        "name": "Product Name",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}

// Error responses
{
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."]
    }
}
```

## 🔧 Advanced Features

### Interactive Mode

Use interactive mode for guided setup:

```bash
php artisan easy-dev:make --interactive
```

Features:
- Step-by-step wizard
- Architecture pattern selection
- Feature selection
- Visual progress tracking

### Batch Operations

Generate multiple CRUDs:

```bash
# Using a loop
for model in Product Order Customer; do
    php artisan easy-dev:crud $model --with-repository --with-service
done
```

### Custom Validation Rules

The package generates intelligent validation rules:

```php
// Generated validation in StoreProductRequest
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'price' => 'required|numeric|min:0',
        'is_active' => 'required|boolean',
        'category_id' => 'required|integer|exists:categories,id',
    ];
}
```

## 🐛 Troubleshooting

### Common Issues

#### Migration Not Found

**Problem:** Package can't find migration file
**Solution:** Ensure migration follows Laravel naming convention

```bash
# Correct naming
create_products_table

# Incorrect naming
products_migration
```

#### Model Not Enhanced

**Problem:** Existing model not updated with relationships
**Solution:** Use sync-relations command

```bash
php artisan easy-dev:sync-relations Product
```

#### Route Conflicts

**Problem:** Routes not generated or conflicting
**Solution:** Check existing routes and use specific options

```bash
# Generate only API routes
php artisan easy-dev:crud Product --api-only
```

### Debug Mode

Enable debug output:

```bash
php artisan easy-dev:crud Product --verbose
```

## 📊 Performance Tips

### Large Applications

For large applications with many models:

1. **Use specific model sync**:
   ```bash
   php artisan easy-dev:sync-relations User
   ```

2. **Generate in batches**:
   ```bash
   # Core models first
   php artisan easy-dev:crud User --with-repository --with-service
   
   # Related models
   php artisan easy-dev:crud Post --with-repository
   ```

3. **Use without-interface for simpler structure**:
   ```bash
   php artisan easy-dev:crud Product --with-repository --without-interface
   ```

## 🚀 Best Practices

### 1. Architecture Decisions

**Use Repository Pattern When:**
- Large applications
- Complex data access logic
- Multiple data sources
- Testing is priority

**Use Service Layer When:**
- Complex business logic
- Multiple repositories needed
- External API integrations
- Domain-driven design

### 2. Command Usage

**Interactive Mode For:**
- New team members
- Complex configurations
- Learning the package

**Direct Commands For:**
- CI/CD pipelines
- Scripted generation
- Known configurations

### 3. Customization

**Publish Stubs When:**
- Consistent code style needed
- Company-specific templates
- Additional features required

---

This guide covers the advanced features of Laravel Easy Dev. For basic usage, see the main [README](README.md).
