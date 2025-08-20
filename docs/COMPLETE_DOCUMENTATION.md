# Laravel Easy Dev v2 - Complete Documentation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anas/easy-dev.svg?style=flat-square)](https://packagist.org/packages/anas/easy-dev)
[![GitHub Stars](https://img.shields.io/github/stars/anasnashat/laravel-easy-dev.svg?style=flat-square)](https://github.com/anasnashat/laravel-easy-dev)
[![Total Downloads](https://img.shields.io/packagist/dt/anas/easy-dev.svg?style=flat-square)](https://packagist.org/packages/anas/easy-dev)
[![License](https://img.shields.io/packagist/l/anas/easy-dev.svg?style=flat-square)](https://packagist.org/packages/anas/easy-dev)

## 🌟 Overview

**Laravel Easy Dev v2** is a powerful, feature-rich package that revolutionizes Laravel development by providing:

- 🎯 **Interactive CRUD Generation** with beautiful CLI interfaces
- 🏗️ **Repository & Service Pattern** implementation
- 🔄 **Intelligent Relationship Detection** from database schema
- 🌐 **API & Web Controller** generation
- 🧪 **Automated Test Generation** 
- 🎨 **Customizable Templates** for all generated files
- ⚡ **Performance Optimized** code generation
- 📚 **Comprehensive Documentation** and help system

---

## 📋 Table of Contents

1. [Installation & Setup](#-installation--setup)
2. [Quick Start Guide](#-quick-start-guide)
3. [Available Commands](#-available-commands)
4. [Interactive Mode](#-interactive-mode)
5. [Relationship Detection](#-relationship-detection)
6. [Repository & Service Patterns](#-repository--service-patterns)
7. [API Development](#-api-development)
8. [Testing Features](#-testing-features)
9. [Configuration](#-configuration)
10. [Customization](#-customization)
11. [Examples & Use Cases](#-examples--use-cases)
12. [Best Practices](#-best-practices)
13. [Troubleshooting](#-troubleshooting)
14. [Contributing](#-contributing)

---

## 🚀 Installation & Setup

### System Requirements

- **PHP**: 8.1 or higher
- **Laravel**: 9.0, 10.x, 11.x, or 12.x
- **Database**: MySQL, PostgreSQL, or SQLite
- **Composer**: Latest stable version

### Installation Steps

#### 1. Install via Composer

```bash
composer require anas/easy-dev
```

#### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=easy-dev-config
```

#### 3. Publish Stubs (Optional)

```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

#### 4. Verify Installation

```bash
php artisan easy-dev:help
```

You should see a beautiful help interface confirming successful installation.

---

## ⚡ Quick Start Guide

### Generate Your First CRUD

1. **Create a Model** (if not exists):
```bash
php artisan make:model Product -m
```

2. **Set up your migration**:
```php
// database/migrations/xxxx_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 8, 2);
    $table->integer('stock')->default(0);
    $table->foreignId('category_id')->constrained();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

3. **Run Migration**:
```bash
php artisan migrate
```

4. **Generate Complete CRUD**:
```bash
php artisan easy-dev:make Product
```

5. **Auto-detect Relationships**:
```bash
php artisan easy-dev:sync-relations --all
```

**That's it!** You now have a complete CRUD system with:
- ✅ ProductController with all CRUD methods
- ✅ Form Request validation classes
- ✅ Repository and Service patterns
- ✅ API endpoints
- ✅ Automatic model relationships
- ✅ Test files

---

## 🎮 Available Commands

### Primary Commands

#### `easy-dev:make {model}`
Enhanced CRUD generator with beautiful interactive UI.

```bash
# Basic usage
php artisan easy-dev:make Product

# With all options
php artisan easy-dev:make Product --with-repository --with-service --api-only --interactive
```

**Options:**
- `--with-repository`: Include Repository pattern
- `--with-service`: Include Service layer
- `--without-interface`: Skip interface generation
- `--api-only`: Generate API controller only
- `--web-only`: Generate web controller only
- `--interactive`: Run in interactive mode

#### `easy-dev:crud {model}`
Classic CRUD generator with Repository and Service patterns.

```bash
# Generate complete CRUD with patterns
php artisan easy-dev:crud Product --with-repository --with-service

# API-only CRUD
php artisan easy-dev:crud Product --api-only
```

### Utility Commands

#### `easy-dev:repository {model}`
Generate repository pattern files for existing models.

```bash
# Generate repository with interface
php artisan easy-dev:repository Product

# Repository without interface
php artisan easy-dev:repository Product --without-interface
```

#### `easy-dev:api-resource {model}`
Generate API resource and collection classes.

```bash
php artisan easy-dev:api-resource Product
```

#### `easy-dev:sync-relations {model?}`
Automatically detect and add relationships to models.

```bash
# Sync relations for specific model
php artisan easy-dev:sync-relations Product

# Sync relations for all models
php artisan easy-dev:sync-relations --all

# With polymorphic targets
php artisan easy-dev:sync-relations Comment --morph-targets=Post,Video,Image
```

#### `easy-dev:add-relation {model} {type} {related}`
Manually add specific relationships.

```bash
# Add belongsTo relationship
php artisan easy-dev:add-relation Post belongsTo User

# Add hasMany with custom method name
php artisan easy-dev:add-relation User hasMany Post --method=articles

# Add belongsToMany relationship
php artisan easy-dev:add-relation User belongsToMany Role
```

### Help & Demo Commands

#### `easy-dev:help`
Display beautiful help guide with all available options.

```bash
php artisan easy-dev:help
```

#### `easy-dev:demo-ui`
Demonstrate the package's beautiful UI capabilities.

```bash
php artisan easy-dev:demo-ui
```

---

## 🎯 Interactive Mode

The interactive mode provides a guided, step-by-step experience for generating CRUD operations.

### Starting Interactive Mode

```bash
php artisan easy-dev:make Product --interactive
```

### Interactive Flow

1. **Model Selection**: Choose or create a model
2. **Generation Options**: Select what to generate
3. **Pattern Selection**: Choose Repository/Service patterns
4. **API Configuration**: Configure API endpoints
5. **Validation Setup**: Configure form validation
6. **Relationship Detection**: Auto-detect model relationships
7. **File Generation**: Generate all selected files
8. **Summary Report**: View generated files and next steps

### Interactive Features

- 🎨 **Beautiful Progress Bars**: Visual feedback during generation
- ⚡ **Smart Defaults**: Intelligent suggestions based on your model
- 🔍 **Live Validation**: Real-time validation of inputs
- 📊 **Summary Reports**: Detailed reports of generated files
- 🚀 **Quick Actions**: Common actions with single keypress

---

## 🔄 Relationship Detection

Laravel Easy Dev v2 features intelligent relationship detection that analyzes your database schema and automatically generates appropriate Eloquent relationships.

### Supported Relationships

#### 1. BelongsTo Relationships
**Detected from**: Foreign key columns (e.g., `user_id`, `category_id`)

```php
// Automatically generated in Post model
public function user()
{
    return $this->belongsTo(User::class);
}

public function category()
{
    return $this->belongsTo(Category::class);
}
```

#### 2. HasMany Relationships
**Detected from**: Reverse foreign key relationships

```php
// Automatically generated in User model
public function posts()
{
    return $this->hasMany(Post::class);
}

// Automatically generated in Category model
public function products()
{
    return $this->hasMany(Product::class);
}
```

#### 3. BelongsToMany Relationships
**Detected from**: Pivot tables (tables with multiple foreign keys)

```php
// Automatically generated when post_tag table exists
public function tags()
{
    return $this->belongsToMany(Tag::class);
}
```

#### 4. Polymorphic Relationships
**Detected from**: `_type` and `_id` column patterns

```php
// Morphable columns: commentable_type, commentable_id
public function commentable()
{
    return $this->morphTo();
}

// Reverse relationship
public function comments()
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

#### 5. Self-Referencing Relationships
**Detected from**: `parent_id` columns

```php
// Parent/child relationships
public function parent()
{
    return $this->belongsTo(Category::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Category::class, 'parent_id');
}
```

### Database Schema Examples

#### Example 1: E-commerce Schema
```sql
-- Categories table
CREATE TABLE categories (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    parent_id BIGINT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- Products table
CREATE TABLE products (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    category_id BIGINT,
    user_id BIGINT,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Product reviews (polymorphic)
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY,
    content TEXT,
    reviewable_type VARCHAR(255),
    reviewable_id BIGINT,
    user_id BIGINT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Product tags (many-to-many)
CREATE TABLE product_tag (
    product_id BIGINT,
    tag_id BIGINT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
);
```

#### Generated Relationships
Running `php artisan easy-dev:sync-relations --all` will generate:

**Category Model:**
```php
// Self-referencing
public function parent()
{
    return $this->belongsTo(Category::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Category::class, 'parent_id');
}

// Has many products
public function products()
{
    return $this->hasMany(Product::class);
}
```

**Product Model:**
```php
// Belongs to relationships
public function category()
{
    return $this->belongsTo(Category::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

// Many-to-many
public function tags()
{
    return $this->belongsToMany(Tag::class);
}

// Polymorphic
public function reviews()
{
    return $this->morphMany(Review::class, 'reviewable');
}
```

### Detection Algorithms

#### Foreign Key Detection
1. **Explicit Constraints**: `foreignId()->constrained('table')`
2. **Laravel Conventions**: `foreignId()->constrained()`
3. **Column Naming**: Columns ending in `_id`

#### Pivot Table Detection
1. **Naming Convention**: `table1_table2` format
2. **Multiple Foreign Keys**: Tables with exactly 2 foreign key columns
3. **Composite Keys**: Combined primary key from foreign keys

#### Polymorphic Detection
1. **Type Columns**: Columns ending in `_type`
2. **ID Columns**: Corresponding columns ending in `_id`
3. **Naming Patterns**: `morphable_type`, `commentable_type`, etc.

---

## 🏗️ Repository & Service Patterns

Laravel Easy Dev v2 implements clean architecture patterns to separate concerns and improve code maintainability.

### Repository Pattern

#### Repository Interface
```php
interface ProductRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Product;
    public function create(array $data): Product;
    public function update(int $id, array $data): Product;
    public function delete(int $id): bool;
    public function findBy(string $field, $value): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
```

#### Repository Implementation
```php
class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private Product $model) {}

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    // ... other methods
}
```

### Service Pattern

#### Service Interface
```php
interface ProductServiceInterface
{
    public function getAllProducts(): Collection;
    public function getProductById(int $id): ?Product;
    public function createProduct(array $data): Product;
    public function updateProduct(int $id, array $data): Product;
    public function deleteProduct(int $id): bool;
    public function searchProducts(string $query): Collection;
}
```

#### Service Implementation
```php
class ProductService implements ProductServiceInterface
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private CategoryService $categoryService
    ) {}

    public function createProduct(array $data): Product
    {
        // Business logic here
        if (isset($data['category_id'])) {
            $category = $this->categoryService->getCategoryById($data['category_id']);
            if (!$category || !$category->is_active) {
                throw new InvalidArgumentException('Invalid category selected');
            }
        }

        return $this->repository->create($data);
    }

    // ... other methods with business logic
}
```

### Dependency Injection Setup

#### Service Provider Registration
```php
// Generated in RepositoryServiceProvider
public function register(): void
{
    $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
    $this->app->bind(ProductServiceInterface::class, ProductService::class);
}
```

#### Controller Usage
```php
class ProductController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    public function index()
    {
        $products = $this->productService->getAllProducts();
        return view('products.index', compact('products'));
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->createProduct($request->validated());
        return redirect()->route('products.show', $product);
    }
}
```

---

## 🌐 API Development

Laravel Easy Dev v2 provides comprehensive API development features with automatic resource generation and API-specific controllers.

### API Resource Generation

#### Product Resource
```php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

#### Product Collection
```php
class ProductCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
        ];
    }
}
```

### API Controller

#### Generated API Controller
```php
class ProductApiController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    public function index(Request $request)
    {
        $products = $this->productService->getAllProducts();
        return new ProductCollection($products);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load('category', 'tags'));
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->createProduct($request->validated());
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product = $this->productService->updateProduct($product->id, $request->validated());
        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $this->productService->deleteProduct($product->id);
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
```

### API Routes

#### Generated API Routes
```php
// routes/api.php
Route::apiResource('products', ProductApiController::class);

// Generates the following routes:
// GET    /api/products          - index
// POST   /api/products          - store  
// GET    /api/products/{id}     - show
// PUT    /api/products/{id}     - update
// DELETE /api/products/{id}     - destroy
```

### API Response Examples

#### Product List Response
```json
{
    "data": [
        {
            "id": 1,
            "name": "Gaming Laptop",
            "description": "High-performance gaming laptop",
            "price": "1299.99",
            "stock": 15,
            "is_active": true,
            "category": {
                "id": 2,
                "name": "Electronics"
            },
            "created_at": "2025-01-15T10:30:00.000000Z",
            "updated_at": "2025-01-15T10:30:00.000000Z"
        }
    ],
    "meta": {
        "total": 50,
        "count": 15,
        "per_page": 15,
        "current_page": 1,
        "total_pages": 4
    }
}
```

---

## 🧪 Testing Features

Laravel Easy Dev v2 automatically generates comprehensive test suites for your CRUD operations.

### Generated Test Files

#### Feature Tests
```php
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_view_products_index()
    {
        $products = Product::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('products.index'));

        $response->assertOk()
            ->assertViewIs('products.index')
            ->assertViewHas('products');
    }

    public function test_can_create_product()
    {
        $category = Category::factory()->create();
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $productData);

        $response->assertRedirect(route('products.show', Product::latest()->first()));
        $this->assertDatabaseHas('products', $productData);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create();
        $updateData = ['name' => 'Updated Product Name'];

        $response = $this->actingAs($this->user)
            ->put(route('products.update', $product), $updateData);

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', $updateData);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
```

#### API Tests
```php
class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_products_list()
    {
        Product::factory(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'stock',
                        'is_active',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages'
                ]
            ]);
    }

    public function test_can_create_product_via_api()
    {
        $category = Category::factory()->create();
        $productData = [
            'name' => 'API Test Product',
            'description' => 'Created via API',
            'price' => 149.99,
            'stock' => 25,
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertCreated()
            ->assertJsonFragment($productData);
    }
}
```

#### Unit Tests
```php
class ProductServiceTest extends TestCase
{
    protected ProductService $productService;
    protected MockInterface $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(ProductRepositoryInterface::class);
        $this->app->instance(ProductRepositoryInterface::class, $this->repositoryMock);
        $this->productService = app(ProductService::class);
    }

    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 10,
        ];

        $expectedProduct = new Product($productData);
        $this->repositoryMock->shouldReceive('create')
            ->once()
            ->with($productData)
            ->andReturn($expectedProduct);

        $result = $this->productService->createProduct($productData);

        $this->assertEquals($expectedProduct, $result);
    }
}
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ProductControllerTest.php

# Run with coverage
php artisan test --coverage
```

---

## ⚙️ Configuration

### Configuration File

The configuration file `config/easy-dev.php` allows extensive customization:

```php
<?php

return [
    // Model namespace
    'model_namespace' => 'App\\Models\\',

    // File generation paths
    'paths' => [
        'models' => app_path('Models'),
        'controllers' => app_path('Http/Controllers'),
        'api_controllers' => app_path('Http/Controllers/Api'),
        'requests' => app_path('Http/Requests'),
        'repositories' => app_path('Repositories'),
        'repository_contracts' => app_path('Repositories/Contracts'),
        'services' => app_path('Services'),
        'service_contracts' => app_path('Services/Contracts'),
        'tests' => base_path('tests'),
        'feature_tests' => base_path('tests/Feature'),
        'unit_tests' => base_path('tests/Unit'),
        'factories' => database_path('factories'),
        'seeders' => database_path('seeders'),
        'migrations' => database_path('migrations'),
    ],

    // Route configuration
    'routes' => [
        'api_prefix' => 'api',
        'api_middleware' => ['api'],
        'web_middleware' => ['web'],
        'generate_api_routes' => true,
        'generate_web_routes' => true,
    ],

    // Code generation options
    'generation' => [
        'use_repository_pattern' => true,
        'use_service_pattern' => true,
        'generate_interfaces' => true,
        'generate_tests' => true,
        'generate_factories' => true,
        'generate_form_requests' => true,
        'generate_api_resources' => true,
    ],

    // Database configuration
    'database' => [
        'relationship_detection' => true,
        'foreign_key_detection' => true,
        'polymorphic_detection' => true,
        'pivot_table_detection' => true,
    ],

    // Template customization
    'templates' => [
        'controller' => 'default',
        'model' => 'default',
        'repository' => 'default',
        'service' => 'default',
    ],

    // UI preferences
    'ui' => [
        'use_beautiful_output' => true,
        'show_progress_bars' => true,
        'use_colors' => true,
        'interactive_mode' => true,
    ],
];
```

### Environment Variables

You can override configuration using environment variables:

```env
# .env file
EASY_DEV_MODEL_NAMESPACE="Domain\\Models\\"
EASY_DEV_USE_REPOSITORY_PATTERN=true
EASY_DEV_GENERATE_TESTS=true
EASY_DEV_API_PREFIX="v1"
```

---

## 🎨 Customization

### Custom Stubs

Laravel Easy Dev v2 uses stub files as templates for code generation. You can customize these templates:

#### 1. Publish Stubs
```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

#### 2. Customize Templates
Stubs are published to `resources/stubs/vendor/easy-dev/`:

```
resources/stubs/vendor/easy-dev/
├── controller.stub
├── controller.api.stub
├── controller.repository.stub
├── model.stub
├── repository.stub
├── repository.interface.stub
├── service.stub
├── service.interface.stub
├── request.store.stub
├── request.update.stub
├── factory.stub
├── api.resource.stub
├── api.collection.stub
└── relations/
    ├── belongsTo.stub
    ├── hasMany.stub
    ├── belongsToMany.stub
    ├── morphTo.stub
    ├── morphMany.stub
    └── morphOne.stub
```

#### 3. Template Variables

Available variables in stubs:

- `{{ ModelName }}` - Model class name (e.g., Product)
- `{{ ModelVariable }}` - Model variable name (e.g., product)
- `{{ ModelNamespace }}` - Full model namespace
- `{{ ControllerName }}` - Controller class name
- `{{ RequestNamespace }}` - Request namespace
- `{{ RepositoryName }}` - Repository class name
- `{{ ServiceName }}` - Service class name
- `{{ TableName }}` - Database table name
- `{{ PrimaryKey }}` - Primary key column name
- `{{ FillableFields }}` - Array of fillable fields
- `{{ RelationshipMethods }}` - Generated relationship methods

#### 4. Custom Controller Example

```php
// resources/stubs/vendor/easy-dev/controller.stub
<?php

namespace {{ ControllerNamespace }};

use {{ ModelNamespace }};
use {{ RequestNamespace }}\Store{{ ModelName }}Request;
use {{ RequestNamespace }}\Update{{ ModelName }}Request;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class {{ ControllerName }} extends Controller
{
    /**
     * Display a listing of {{ ModelVariable }}s.
     */
    public function index()
    {
        ${{ ModelVariable }}s = {{ ModelName }}::paginate(15);
        
        return view('{{ ViewPath }}.index', compact('{{ ModelVariable }}s'));
    }

    /**
     * Show the form for creating a new {{ ModelVariable }}.
     */
    public function create()
    {
        return view('{{ ViewPath }}.create');
    }

    /**
     * Store a newly created {{ ModelVariable }}.
     */
    public function store(Store{{ ModelName }}Request $request)
    {
        ${{ ModelVariable }} = {{ ModelName }}::create($request->validated());

        return redirect()
            ->route('{{ RoutePrefix }}.show', ${{ ModelVariable }})
            ->with('success', '{{ ModelName }} created successfully.');
    }

    // ... other methods
}
```

### Custom Validation Rules

#### Form Request Customization

```php
// resources/stubs/vendor/easy-dev/request.store.stub
<?php

namespace {{ RequestNamespace }};

use Illuminate\Foundation\Http\FormRequest;

class Store{{ ModelName }}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implement your authorization logic
    }

    public function rules(): array
    {
        return [
            {{ ValidationRules }}
        ];
    }

    public function messages(): array
    {
        return [
            {{ ValidationMessages }}
        ];
    }

    public function attributes(): array
    {
        return [
            {{ AttributeNames }}
        ];
    }
}
```

### Custom Service Provider

You can create a custom service provider to override package behavior:

```php
<?php

namespace App\Providers;

use AnasNashat\EasyDev\Services\CodeWriter;
use AnasNashat\EasyDev\Services\FileGenerator;
use Illuminate\Support\ServiceProvider;

class CustomEasyDevServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override code writer
        $this->app->bind(CodeWriter::class, CustomCodeWriter::class);
        
        // Override file generator
        $this->app->bind(FileGenerator::class, CustomFileGenerator::class);
    }

    public function boot(): void
    {
        // Custom configuration
        config(['easy-dev.custom_option' => true]);
    }
}
```

---

## 💡 Examples & Use Cases

### Use Case 1: E-commerce Platform

Building a complete e-commerce platform with Laravel Easy Dev v2:

#### 1. Create Models and Migrations
```bash
# Create base models
php artisan make:model Category -m
php artisan make:model Product -m  
php artisan make:model Order -m
php artisan make:model OrderItem -m
php artisan make:model Review -m
```

#### 2. Set Up Database Schema
```php
// Create categories migration
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('categories');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Create products migration
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description');
    $table->decimal('price', 10, 2);
    $table->decimal('sale_price', 10, 2)->nullable();
    $table->string('sku')->unique();
    $table->integer('stock')->default(0);
    $table->json('images')->nullable();
    $table->foreignId('category_id')->constrained();
    $table->foreignId('brand_id')->nullable()->constrained();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->timestamps();
});

// Create reviews migration (polymorphic)
Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->text('content');
    $table->integer('rating');
    $table->morphs('reviewable');
    $table->foreignId('user_id')->constrained();
    $table->boolean('is_approved')->default(false);
    $table->timestamps();
});
```

#### 3. Generate Complete CRUD Operations
```bash
# Generate with all patterns
php artisan easy-dev:make Category --with-repository --with-service --interactive
php artisan easy-dev:make Product --with-repository --with-service --interactive
php artisan easy-dev:make Order --with-repository --with-service --api-only
php artisan easy-dev:make Review --with-repository --with-service
```

#### 4. Auto-Detect Relationships
```bash
php artisan easy-dev:sync-relations --all
```

This generates a complete e-commerce foundation with:
- ✅ Category management (CRUD)
- ✅ Product catalog (CRUD)
- ✅ Order management (API)
- ✅ Review system (polymorphic)
- ✅ All relationships automatically detected
- ✅ Repository and Service patterns
- ✅ Form validation
- ✅ API endpoints
- ✅ Test coverage

### Use Case 2: Blog Platform

#### 1. Create Models
```bash
php artisan make:model Post -m
php artisan make:model Comment -m
php artisan make:model Tag -m
```

#### 2. Set Up Schema
```php
// Posts migration
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt');
    $table->longText('content');
    $table->string('featured_image')->nullable();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('category_id')->constrained();
    $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});

// Comments migration (polymorphic)
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->text('content');
    $table->morphs('commentable');
    $table->foreignId('user_id')->constrained();
    $table->foreignId('parent_id')->nullable()->constrained('comments');
    $table->boolean('is_approved')->default(false);
    $table->timestamps();
});

// Post-Tag pivot table
Schema::create('post_tag', function (Blueprint $table) {
    $table->foreignId('post_id')->constrained();
    $table->foreignId('tag_id')->constrained();
    $table->timestamps();
});
```

#### 3. Generate CRUD
```bash
php artisan easy-dev:make Post --with-repository --with-service
php artisan easy-dev:make Comment --with-service
php artisan easy-dev:make Tag --with-repository
```

#### 4. Sync Relationships
```bash
php artisan easy-dev:sync-relations --all
```

### Use Case 3: Project Management System

#### Complete Setup Example
```bash
# Create all models
php artisan make:model Project -m
php artisan make:model Task -m
php artisan make:model Team -m
php artisan make:model TimeLog -m

# Generate CRUD with different patterns
php artisan easy-dev:make Project --with-repository --with-service --interactive
php artisan easy-dev:make Task --with-service --api-only
php artisan easy-dev:make Team --with-repository
php artisan easy-dev:make TimeLog --api-only

# Auto-detect all relationships
php artisan easy-dev:sync-relations --all

# Generate additional relationships manually
php artisan easy-dev:add-relation Project belongsToMany User --method=members
php artisan easy-dev:add-relation User belongsToMany Project --method=projects
```

---

## ✅ Best Practices

### 1. Database Design

#### Foreign Key Naming
```php
// ✅ Good - Laravel conventions
$table->foreignId('user_id')->constrained();
$table->foreignId('category_id')->constrained('categories');

// ❌ Avoid - Non-standard naming
$table->integer('userid');
$table->integer('cat_id');
```

#### Polymorphic Relationships
```php
// ✅ Good - Standard naming
$table->morphs('commentable'); // creates commentable_type and commentable_id

// ✅ Good - Custom naming
$table->morphs('taggable'); // creates taggable_type and taggable_id

// ❌ Avoid - Inconsistent naming
$table->string('morph_type');
$table->integer('morph_id');
```

### 2. Model Organization

#### Use Fillable Arrays
```php
// ✅ Good - Explicit fillable fields
class Product extends Model
{
    protected $fillable = [
        'name',
        'description', 
        'price',
        'stock',
        'category_id',
        'is_active'
    ];
}
```

#### Relationship Method Naming
```php
// ✅ Good - Clear relationship names
public function category() // belongsTo
{
    return $this->belongsTo(Category::class);
}

public function reviews() // hasMany
{
    return $this->hasMany(Review::class);
}

public function tags() // belongsToMany
{
    return $this->belongsToMany(Tag::class);
}
```

### 3. Service Layer Design

#### Single Responsibility
```php
// ✅ Good - Focused service
class ProductService
{
    public function createProduct(array $data): Product
    {
        // Only product-related business logic
        $this->validateProductData($data);
        $this->checkInventory($data);
        return $this->productRepository->create($data);
    }
}

// ❌ Avoid - Mixed responsibilities
class ProductService
{
    public function createProduct(array $data): Product
    {
        // Don't mix user management in product service
        $this->userService->updateUserPreferences();
        return $this->productRepository->create($data);
    }
}
```

#### Dependency Injection
```php
// ✅ Good - Inject interfaces
class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}
}

// ❌ Avoid - Direct model usage in services
class ProductService
{
    public function createProduct(array $data): Product
    {
        return Product::create($data); // Avoid direct model access
    }
}
```

### 4. Repository Pattern Usage

#### Interface Contracts
```php
// ✅ Good - Complete interface
interface ProductRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Product;
    public function create(array $data): Product;
    public function update(int $id, array $data): Product;
    public function delete(int $id): bool;
    public function findBy(string $field, $value): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
```

#### Query Optimization
```php
// ✅ Good - Eager loading
public function getProductsWithRelations(): Collection
{
    return $this->model->with(['category', 'tags', 'reviews'])
        ->where('is_active', true)
        ->get();
}

// ❌ Avoid - N+1 queries
public function getProductsWithRelations(): Collection
{
    $products = $this->model->where('is_active', true)->get();
    // This will cause N+1 queries when accessing relationships
    return $products;
}
```

### 5. API Design

#### Consistent Resource Structure
```php
// ✅ Good - Consistent API resources
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

#### HTTP Status Codes
```php
// ✅ Good - Proper status codes
public function store(StoreProductRequest $request)
{
    $product = $this->productService->createProduct($request->validated());
    return new ProductResource($product); // 201 Created (implicit)
}

public function destroy(Product $product)
{
    $this->productService->deleteProduct($product->id);
    return response()->json(['message' => 'Deleted'], 204); // 204 No Content
}
```

### 6. Testing Strategy

#### Comprehensive Test Coverage
```php
// ✅ Good - Test all CRUD operations
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products() { /* ... */ }
    public function test_can_create_product() { /* ... */ }
    public function test_can_show_product() { /* ... */ }
    public function test_can_update_product() { /* ... */ }
    public function test_can_delete_product() { /* ... */ }
    public function test_cannot_create_product_with_invalid_data() { /* ... */ }
}
```

#### Use Factories
```php
// ✅ Good - Use factories in tests
public function test_can_create_product()
{
    $category = Category::factory()->create();
    $productData = Product::factory()->make(['category_id' => $category->id])->toArray();
    
    $response = $this->postJson('/api/products', $productData);
    $response->assertCreated();
}
```

---

## 🔧 Troubleshooting

### Common Issues and Solutions

#### 1. Command Not Found
**Problem**: `easy-dev:make` command not recognized

**Solutions**:
```bash
# Clear application cache
php artisan config:clear
php artisan cache:clear

# Rebuild package discovery
composer dump-autoload
php artisan package:discover

# Verify installation
composer show anas/easy-dev
```

#### 2. Relationship Detection Issues
**Problem**: Relationships not being detected properly

**Solutions**:
```bash
# Check database connection
php artisan db:show

# Verify foreign key constraints exist
php artisan db:table products

# Run with debug information
php artisan easy-dev:sync-relations Product --verbose

# Check migration files
ls database/migrations/*create*table*
```

#### 3. File Generation Errors
**Problem**: Files not being generated or overwritten

**Solutions**:
```bash
# Check file permissions
chmod -R 755 app/
chmod -R 755 database/

# Verify directory structure
php artisan about

# Use force flag to overwrite
php artisan easy-dev:make Product --force

# Check configuration
php artisan config:show easy-dev
```

#### 4. Repository Pattern Issues
**Problem**: Dependency injection not working

**Solutions**:
```php
// Check service provider registration
// In config/app.php
'providers' => [
    // ...
    App\Providers\RepositoryServiceProvider::class,
],

// Verify bindings in RepositoryServiceProvider
public function register(): void
{
    $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
}

// Clear config cache
php artisan config:clear
```

#### 5. API Resource Issues
**Problem**: API resources not working properly

**Solutions**:
```bash
# Verify resource exists
ls app/Http/Resources/

# Check resource registration
php artisan route:list | grep api

# Test API endpoint
curl -X GET http://localhost:8000/api/products \
  -H "Accept: application/json"
```

#### 6. Test Generation Issues
**Problem**: Tests failing or not generated

**Solutions**:
```bash
# Run specific test
php artisan test tests/Feature/ProductControllerTest.php

# Check test database configuration
# In phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>

# Regenerate test files
php artisan easy-dev:make Product --tests-only
```

### Debug Mode

Enable debug mode for detailed output:

```bash
# Run commands with verbose output
php artisan easy-dev:make Product --verbose

# Enable application debug mode
# In .env
APP_DEBUG=true

# Check logs
tail -f storage/logs/laravel.log
```

### Performance Issues

#### Optimize Generated Code
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache views
php artisan view:cache
```

### Getting Help

1. **Documentation**: Check the complete documentation
2. **GitHub Issues**: [Report bugs](https://github.com/anasnashat/laravel-easy-dev/issues)
3. **Discussions**: [Join discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)
4. **Stack Overflow**: Tag questions with `laravel-easy-dev`

---

## 🤝 Contributing

We welcome contributions to Laravel Easy Dev v2! Here's how you can help:

### Development Setup

1. **Fork the Repository**
```bash
git clone https://github.com/yourusername/laravel-easy-dev.git
cd laravel-easy-dev
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Set Up Testing Environment**
```bash
cp .env.example .env.testing
php artisan key:generate --env=testing
```

4. **Run Tests**
```bash
vendor/bin/phpunit
```

### Contribution Guidelines

#### Code Style
- Follow PSR-12 coding standards
- Use Laravel conventions
- Write descriptive commit messages
- Add tests for new features

#### Pull Request Process
1. Create a feature branch
2. Write tests for your changes
3. Ensure all tests pass
4. Update documentation
5. Submit a pull request

#### Areas for Contribution
- 🐛 **Bug Fixes**: Fix reported issues
- ✨ **New Features**: Add new generators or commands
- 📚 **Documentation**: Improve docs and examples
- 🧪 **Testing**: Add more test coverage
- 🎨 **UI/UX**: Enhance command-line interfaces
- 🔧 **Performance**: Optimize code generation

### Development Commands

```bash
# Run code style checks
vendor/bin/pint

# Run static analysis
vendor/bin/phpstan analyse

# Run full test suite
vendor/bin/phpunit

# Generate test coverage
vendor/bin/phpunit --coverage-html coverage
```

---

## 📄 License

Laravel Easy Dev v2 is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🙏 Acknowledgments

- **Laravel Community**: For the amazing framework
- **Contributors**: All the developers who have contributed
- **Package Inspirations**: Laravel Generators, Spatie packages
- **Testing**: Orchestra Testbench for package testing

---

## 📞 Support

- **Documentation**: [GitHub Wiki](https://github.com/anasnashat/laravel-easy-dev/wiki)
- **Issues**: [GitHub Issues](https://github.com/anasnashat/laravel-easy-dev/issues)
- **Discussions**: [GitHub Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)
- **Email**: anas.nashat.ahmed@gmail.com

---

**Made with ❤️ by [Anas Nashaat](https://github.com/anasnashat)**

*Supercharge your Laravel development workflow with Laravel Easy Dev v2!*
