# 📖 GitHub Wiki Setup Guide

This guide will help you set up the complete GitHub Wiki for Laravel Easy Dev v2.

## 🏠 Wiki Home Page

**Create: `Home.md`**

```markdown
# 🚀 Laravel Easy Dev v2 Wiki

Welcome to the comprehensive documentation for Laravel Easy Dev v2 - the most powerful Laravel CRUD generator with beautiful UI, Repository & Service patterns, and intelligent relationship detection.

## 📚 Documentation Sections

### 🚀 Getting Started
- **[⚡ Quick Start](Quick-Start)** - Get up and running in minutes
- **[📦 Installation](Installation)** - Complete installation guide
- **[⚙️ Configuration](Configuration)** - Customize everything

### 🎯 Core Features
- **[🔧 Command Reference](Command-Reference)** - All commands and options
- **[🔗 Relationship Detection](Relationship-Detection)** - Auto-relationship system
- **[🌐 API Development](API-Development)** - API-first development guide

### 💡 Examples & Guides
- **[📋 Examples & Use Cases](Examples-and-Use-Cases)** - Real-world examples
- **[🏗️ Architecture Patterns](Architecture-Patterns)** - Repository & Service patterns
- **[🧪 Testing Guide](Testing-Guide)** - Comprehensive testing

### 📖 Reference
- **[📚 Complete Documentation](Complete-Documentation)** - Full reference guide
- **[🔧 Troubleshooting](Troubleshooting)** - Common issues and solutions
- **[❓ FAQ](FAQ)** - Frequently asked questions

## 🎯 Quick Navigation

| Topic | Description | Link |
|-------|-------------|------|
| **Quick Start** | Get started in 5 minutes | [⚡ Start Here](Quick-Start) |
| **Commands** | All available commands | [🔧 Commands](Command-Reference) |
| **Examples** | Real-world use cases | [💡 Examples](Examples-and-Use-Cases) |
| **API Guide** | API development | [🌐 API Guide](API-Development) |
| **Configuration** | Customize settings | [⚙️ Config](Configuration) |

## 🌟 Features Overview

✨ **Enhanced CRUD Generation** - Interactive CRUD with Repository and Service patterns  
🎯 **Beautiful UI** - Stunning command-line interface with progress bars and colors  
🔄 **Auto Relationship Detection** - Intelligent schema analysis and relationship generation  
🏗️ **Clean Architecture** - Repository and Service layer with interfaces  
📝 **Smart Form Requests** - Intelligent validation rules with custom error messages  

## 🤝 Community & Support

- **📖 Documentation**: This Wiki
- **🐛 Issues**: [GitHub Issues](https://github.com/anasnashat/laravel-easy-dev/issues)
- **💬 Discussions**: [GitHub Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)
- **⭐ Star us**: [GitHub Repository](https://github.com/anasnashat/laravel-easy-dev)

---

<div align="center">

**Made with ❤️ for the Laravel community**

[⭐ Star us on GitHub](https://github.com/anasnashat/laravel-easy-dev) • [🐛 Report Issues](https://github.com/anasnashat/laravel-easy-dev/issues) • [💬 Join Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)

</div>
```

## 📄 Wiki Pages Structure

### 1. Quick-Start.md
Copy content from: `docs/QUICK_START.md`

### 2. Installation.md
```markdown
# 📦 Installation Guide

## Requirements

- **PHP**: 8.1+
- **Laravel**: 9.0+ | 10.0+ | 11.0+ | 12.0+
- **Database**: MySQL, PostgreSQL, or SQLite

## Installation Steps

### 1. Install via Composer

```bash
composer require anas/easy-dev
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=easy-dev-config
```

### 3. Publish Stubs (Optional)

```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

### 4. Verify Installation

```bash
php artisan easy-dev:help
```

You should see the beautiful help interface confirming successful installation.

## Next Steps

- **[⚡ Quick Start](Quick-Start)** - Get started immediately
- **[🔧 Command Reference](Command-Reference)** - Learn all commands
- **[💡 Examples](Examples-and-Use-Cases)** - See real-world examples
```

### 3. Command-Reference.md
Copy content from: `docs/COMMAND_REFERENCE.md`

### 4. Configuration.md
Copy content from: `docs/CONFIGURATION.md`

### 5. Relationship-Detection.md
Copy content from: `docs/RELATIONSHIP_DETECTION.md`

### 6. API-Development.md
Copy content from: `docs/API_DEVELOPMENT.md`

### 7. Examples-and-Use-Cases.md
Copy content from: `docs/EXAMPLES_USE_CASES.md`

### 8. Complete-Documentation.md
Copy content from: `docs/COMPLETE_DOCUMENTATION.md`

### 9. Architecture-Patterns.md
```markdown
# 🏗️ Architecture Patterns

Laravel Easy Dev v2 promotes clean architecture through Repository and Service patterns.

## 🗄️ Repository Pattern

### What is Repository Pattern?

The Repository pattern encapsulates data access logic and provides a uniform interface for accessing data.

### Benefits

- **Decoupling** - Separates business logic from data access
- **Testability** - Easy to mock for unit testing
- **Maintainability** - Centralized data access logic
- **Flexibility** - Easy to switch data sources

### Generated Structure

```php
// Interface
interface ProductRepositoryInterface
{
    public function getAllProducts(array $filters = []): Collection;
    public function getProductById(int $id): ?Product;
    public function createProduct(array $data): Product;
    public function updateProduct(int $id, array $data): bool;
    public function deleteProduct(int $id): bool;
}

// Implementation
class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private Product $model) {}
    
    public function getAllProducts(array $filters = []): Collection
    {
        $query = $this->model->query();
        
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }
        
        return $query->get();
    }
    
    // ... other methods
}
```

## 🔧 Service Pattern

### What is Service Pattern?

The Service pattern contains business logic and orchestrates operations between different components.

### Benefits

- **Single Responsibility** - Each service has one clear purpose
- **Reusability** - Services can be used across controllers
- **Testability** - Business logic isolated for testing
- **Organization** - Clean separation of concerns

### Generated Structure

```php
// Interface
interface ProductServiceInterface
{
    public function getAllProducts(array $filters = []): Collection;
    public function createProduct(array $data): Product;
    public function updateProduct(int $id, array $data): Product;
    public function deleteProduct(int $id): bool;
}

// Implementation
class ProductService implements ProductServiceInterface
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}
    
    public function createProduct(array $data): Product
    {
        // Business logic here
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = auth()->id();
        
        return $this->repository->createProduct($data);
    }
    
    // ... other methods
}
```

## 🎯 Controller Integration

Controllers use services for business operations:

```php
class ProductController extends Controller
{
    public function __construct(
        private ProductServiceInterface $service
    ) {}
    
    public function store(StoreProductRequest $request)
    {
        $product = $this->service->createProduct($request->validated());
        
        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product created successfully!');
    }
}
```

## 🧪 Testing Benefits

Easy to test with dependency injection:

```php
class ProductServiceTest extends TestCase
{
    public function test_creates_product_with_slug()
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $service = new ProductService($repository);
        
        $repository->shouldReceive('createProduct')
            ->once()
            ->with(Mockery::subset(['slug' => 'test-product']))
            ->andReturn(new Product);
            
        $service->createProduct(['name' => 'Test Product']);
    }
}
```

## 📋 Best Practices

### Repository Guidelines

1. **Keep it Simple** - Don't add business logic
2. **Single Model** - One repository per model
3. **Clear Methods** - Use descriptive method names
4. **Return Types** - Always specify return types

### Service Guidelines

1. **Business Logic** - Put complex logic here
2. **Orchestration** - Coordinate between repositories
3. **Validation** - Additional business validation
4. **Events** - Dispatch domain events

### Dependency Injection

Register in `AppServiceProvider`:

```php
public function register()
{
    $this->app->bind(
        ProductRepositoryInterface::class,
        ProductRepository::class
    );
    
    $this->app->bind(
        ProductServiceInterface::class,
        ProductService::class
    );
}
```

Laravel Easy Dev automatically updates your service provider bindings!
```

### 10. Testing-Guide.md
```markdown
# 🧪 Testing Guide

Laravel Easy Dev v2 generates comprehensive tests for all your CRUD operations.

## 🎯 Generated Test Structure

When you generate CRUD with tests, you get:

```
tests/
├── Feature/
│   ├── ProductControllerTest.php      # Feature tests
│   └── Api/
│       └── ProductApiControllerTest.php # API tests
└── Unit/
    ├── ProductRepositoryTest.php      # Repository tests
    └── ProductServiceTest.php         # Service tests
```

## 🔧 Feature Tests

### Web Controller Tests

```php
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_products_index()
    {
        $products = Product::factory()->count(3)->create();

        $response = $this->get(route('products.index'));

        $response->assertStatus(200)
            ->assertViewIs('products.index')
            ->assertViewHas('products');
    }

    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
        ];

        $response = $this->post(route('products.store'), $productData);

        $response->assertRedirect()
            ->assertSessionHas('success');
            
        $this->assertDatabaseHas('products', $productData);
    }
}
```

### API Controller Tests

```php
class ProductApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products()
    {
        $products = Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'price']
                ]
            ]);
    }

    public function test_can_create_product_via_api()
    {
        $productData = [
            'name' => 'API Product',
            'description' => 'Created via API',
            'price' => 149.99,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment($productData);
    }
}
```

## 🏗️ Unit Tests

### Repository Tests

```php
class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new Product);
    }

    public function test_can_get_all_products()
    {
        Product::factory()->count(3)->create();

        $products = $this->repository->getAllProducts();

        $this->assertCount(3, $products);
    }

    public function test_can_filter_products_by_search()
    {
        Product::factory()->create(['name' => 'iPhone 14']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $products = $this->repository->getAllProducts(['search' => 'iPhone']);

        $this->assertCount(1, $products);
        $this->assertEquals('iPhone 14', $products->first()->name);
    }
}
```

### Service Tests

```php
class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_product_with_slug()
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $service = new ProductService($repository);

        $productData = ['name' => 'Test Product'];
        $expectedData = ['name' => 'Test Product', 'slug' => 'test-product'];

        $repository->shouldReceive('createProduct')
            ->once()
            ->with($expectedData)
            ->andReturn(new Product($expectedData));

        $result = $service->createProduct($productData);

        $this->assertInstanceOf(Product::class, $result);
    }
}
```

## 🚀 Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit

# Specific test file
php artisan test tests/Feature/ProductControllerTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

## 📊 Test Factories

Generated factories for your models:

```php
class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(80),
        ];
    }

    public function active()
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive()
    {
        return $this->state(['is_active' => false]);
    }
}
```

## 🎯 Testing Best Practices

### 1. Use Factories
```php
// Good
$product = Product::factory()->create();

// Avoid
$product = Product::create([...]);
```

### 2. Test Boundaries
```php
public function test_cannot_create_product_with_invalid_price()
{
    $response = $this->postJson('/api/products', [
        'name' => 'Test Product',
        'price' => -10 // Invalid price
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
}
```

### 3. Test Relationships
```php
public function test_product_belongs_to_category()
{
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);

    $this->assertInstanceOf(Category::class, $product->category);
    $this->assertEquals($category->id, $product->category->id);
}
```

### 4. Test API Resources
```php
public function test_product_resource_structure()
{
    $product = Product::factory()->create();

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'description',
            'price',
            'formatted_price',
            'created_at',
            'updated_at'
        ]
    ]);
}
```

## 🔍 Debugging Tests

### Use Test Debugging
```php
public function test_example()
{
    $response = $this->get('/products');
    
    // Debug response
    dump($response->getContent());
    
    // Debug database
    $this->assertDatabaseHas('products', ['name' => 'Test']);
}
```

### Database Assertions
```php
// Assert data exists
$this->assertDatabaseHas('products', ['name' => 'Test Product']);

// Assert data doesn't exist
$this->assertDatabaseMissing('products', ['name' => 'Deleted Product']);

// Count records
$this->assertDatabaseCount('products', 5);
```

Laravel Easy Dev v2 generates production-ready tests that cover all your CRUD operations!
```

### 11. Troubleshooting.md
```markdown
# 🔧 Troubleshooting

Common issues and solutions when using Laravel Easy Dev v2.

## 🚨 Common Issues

### 1. Command Not Found

**Problem**: `Command "easy-dev:make" is not defined`

**Solutions**:

```bash
# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan package:discover

# Check if package is installed
composer show anas/easy-dev

# Reinstall if necessary
composer remove anas/easy-dev
composer require anas/easy-dev
```

### 2. Permission Errors

**Problem**: `Permission denied when creating files`

**Solutions**:

```bash
# Fix storage permissions (Linux/Mac)
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Windows: Run as administrator or check folder permissions
```

### 3. Model Not Found

**Problem**: `Model [App\Models\Product] not found`

**Solutions**:

```bash
# Ensure model exists
php artisan make:model Product

# Check model namespace in config
php artisan vendor:publish --tag=easy-dev-config

# Verify namespace in config/easy-dev.php
'model_namespace' => 'App\\Models\\',
```

### 4. Database Connection Issues

**Problem**: `SQLSTATE[HY000] [2002] Connection refused`

**Solutions**:

```bash
# Check database configuration
php artisan config:show database

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Ensure database server is running
# MySQL: sudo service mysql start
# PostgreSQL: sudo service postgresql start
```

### 5. Route Conflicts

**Problem**: `Route [products.index] already defined`

**Solutions**:

```bash
# Check existing routes
php artisan route:list | grep products

# Clear route cache
php artisan route:clear

# Check for duplicate route definitions in:
# - routes/web.php
# - routes/api.php
```

### 6. Class Not Found Errors

**Problem**: `Class 'App\Repositories\ProductRepository' not found`

**Solutions**:

```bash
# Regenerate autoloader
composer dump-autoload

# Check if file exists
ls -la app/Repositories/ProductRepository.php

# Verify namespace in the file matches expected namespace
```

## 🔍 Debugging Tips

### 1. Enable Debug Mode

In `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### 2. Check Laravel Logs

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Clear logs
> storage/logs/laravel.log
```

### 3. Test Package Commands

```bash
# Test help command
php artisan easy-dev:help

# Test demo UI
php artisan easy-dev:demo-ui

# Test with dry run (if available)
php artisan easy-dev:make Product --dry-run
```

### 4. Verify Configuration

```bash
# Check Easy Dev configuration
php artisan config:show easy-dev

# Check database configuration
php artisan config:show database

# List all configurations
php artisan config:show
```

## 🐛 Package-Specific Issues

### 1. Relationship Detection Not Working

**Problem**: No relationships detected

**Check**:
- Database has proper foreign key constraints
- Migration files exist and are properly named
- Model namespace matches configuration

**Solution**:
```bash
# Run with verbose output
php artisan easy-dev:sync-relations --all -v

# Check specific model
php artisan easy-dev:sync-relations Product -v
```

### 2. Generated Files Have Wrong Namespace

**Problem**: `namespace App\Http\Controllers;` instead of custom namespace

**Solution**:
```bash
# Publish and modify configuration
php artisan vendor:publish --tag=easy-dev-config

# Edit config/easy-dev.php
'namespaces' => [
    'controllers' => 'App\\Http\\Controllers\\',
    'models' => 'App\\Models\\',
    // ... other namespaces
],
```

### 3. Stub Files Not Found

**Problem**: `Stub file not found: controller.stub`

**Solution**:
```bash
# Publish stub files
php artisan vendor:publish --tag=easy-dev-stubs

# Check if stubs exist
ls -la resources/stubs/vendor/easy-dev/

# Verify stub paths in config
```

### 4. Service Provider Binding Issues

**Problem**: Interface not bound to implementation

**Solution**:
```bash
# Check service provider
cat app/Providers/AppServiceProvider.php

# Manually add bindings if missing
public function register()
{
    $this->app->bind(
        \App\Repositories\Contracts\ProductRepositoryInterface::class,
        \App\Repositories\ProductRepository::class
    );
}

# Clear config cache
php artisan config:clear
```

## 🔧 Performance Issues

### 1. Slow Relationship Detection

**Problem**: `easy-dev:sync-relations` takes too long

**Solutions**:
- Process models individually: `php artisan easy-dev:sync-relations Product`
- Check database indexes on foreign key columns
- Verify database connection performance

### 2. Memory Issues

**Problem**: `Fatal error: Allowed memory size exhausted`

**Solutions**:
```bash
# Increase memory limit temporarily
php -d memory_limit=512M artisan easy-dev:make Product

# Or in php.ini
memory_limit = 512M
```

## 📞 Getting Help

### 1. Check Documentation

- **[📚 Complete Documentation](Complete-Documentation)**
- **[⚡ Quick Start](Quick-Start)**
- **[🔧 Command Reference](Command-Reference)**

### 2. Community Support

- **[🐛 GitHub Issues](https://github.com/anasnashat/laravel-easy-dev/issues)**
- **[💬 GitHub Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)**

### 3. Report Bugs

When reporting bugs, include:

```bash
# System information
php --version
php artisan --version
composer show anas/easy-dev

# Laravel information
cat composer.json | grep laravel

# Error messages with stack trace
# Laravel logs from storage/logs/laravel.log
```

### 4. Feature Requests

Use GitHub Discussions for feature requests and include:
- Use case description
- Expected behavior
- Example implementation (if applicable)

---

**Still having issues?** Create a [GitHub Issue](https://github.com/anasnashat/laravel-easy-dev/issues) with detailed information about your problem.
```

### 12. FAQ.md
```markdown
# ❓ Frequently Asked Questions

Common questions about Laravel Easy Dev v2.

## 🚀 General Questions

### What is Laravel Easy Dev v2?

Laravel Easy Dev v2 is a powerful Laravel package that generates complete CRUD operations with Repository and Service patterns, beautiful UI, and intelligent relationship detection.

### What's new in v2?

- ✨ Beautiful command-line interface with progress bars
- 🎯 Interactive mode with guided setup
- 🔄 Enhanced relationship detection
- 🏗️ Repository and Service pattern generation
- 🌐 API and Web controller support
- 📝 Smart form request validation
- 🧪 Comprehensive test generation

### Is it compatible with my Laravel version?

Laravel Easy Dev v2 supports:
- **Laravel**: 9.x, 10.x, 11.x, 12.x
- **PHP**: 8.1+

## 📦 Installation & Setup

### Do I need to publish configuration?

No, it's optional. The package works out of the box. Publish configuration only if you need to customize paths, namespaces, or other settings.

### Can I customize the generated code?

Yes! Publish the stub files:
```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

Then modify the stubs in `resources/stubs/vendor/easy-dev/`.

### Will it overwrite my existing files?

No, the package checks for existing files and either:
- Skips generation with a warning
- Enhances existing files (for models with relationships)
- Asks for confirmation (in interactive mode)

## 🔧 Commands & Usage

### What's the difference between `easy-dev:make` and `easy-dev:crud`?

- **`easy-dev:make`**: Enhanced command with beautiful UI and interactive features
- **`easy-dev:crud`**: Classic command for straightforward CRUD generation

Both generate the same files, but `easy-dev:make` provides a better experience.

### Can I generate only specific components?

Yes! Use these options:
- `--api-only`: Only API controllers and routes
- `--web-only`: Only web controllers and routes
- `--with-repository`: Add repository pattern
- `--with-service`: Add service layer
- `--without-interface`: Skip interface generation

### How do I add custom relationships?

Use the relationship command:
```bash
php artisan easy-dev:add-relation User hasMany Post
php artisan easy-dev:add-relation Product belongsToMany Tag
```

## 🔄 Relationship Detection

### How does auto-relationship detection work?

The package analyzes:
1. **Foreign keys** in database schema
2. **Migration files** for relationship hints
3. **Naming conventions** (e.g., `user_id`, `parent_id`)
4. **Pivot tables** (tables with multiple foreign keys)

### Why aren't my relationships detected?

Common reasons:
- No foreign key constraints in database
- Migration files don't follow Laravel conventions
- Model namespace doesn't match configuration
- Custom naming that doesn't follow conventions

### Can I skip certain relationships?

Currently, no automatic exclusion. But you can:
1. Run detection for specific models only
2. Manually remove unwanted relationships after generation
3. Customize the detection logic by extending the package

## 🏗️ Architecture Patterns

### Should I use Repository pattern?

Repository pattern is beneficial when you have:
- Complex data access logic
- Need for testability
- Multiple data sources
- Team development with clear separation

For simple CRUD, you might not need it.

### What about Service pattern?

Service pattern is recommended when you have:
- Business logic beyond simple CRUD
- Operations involving multiple models
- External API integrations
- Complex validation or processing

### Can I use both patterns together?

Yes! This is actually recommended for complex applications:
```bash
php artisan easy-dev:make Product --with-repository --with-service
```

## 🌐 API Development

### Does it generate API documentation?

The package generates API resources with proper structure, but not automatic documentation. Consider using:
- Laravel API Documentation generators
- Postman collections
- OpenAPI/Swagger specifications

### Can I customize API responses?

Yes! Modify the generated API resources:
```php
// In ProductResource.php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'formatted_price' => '$' . number_format($this->price, 2),
        // ... custom fields
    ];
}
```

### What about API versioning?

The package doesn't generate versioned APIs automatically. You can:
1. Generate in version-specific directories
2. Use Laravel's API versioning techniques
3. Customize the stub files for versioned generation

## 🧪 Testing

### Are the generated tests comprehensive?

The generated tests cover:
- Basic CRUD operations
- Validation rules
- API responses
- Repository methods (if generated)
- Service methods (if generated)

You should add custom tests for your specific business logic.

### Can I customize the test structure?

Yes! Publish and modify the test stub files:
```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

### Do I need factories for testing?

While not generated automatically, factories are recommended:
```bash
php artisan make:factory ProductFactory
```

## 🔧 Customization

### Can I change the generated file structure?

Yes! Modify the paths in config:
```php
// config/easy-dev.php
'paths' => [
    'controllers' => app_path('Http/Controllers'),
    'repositories' => app_path('Repositories'),
    'services' => app_path('Services'),
    // ... other paths
],
```

### How do I customize validation rules?

Modify the generated form request classes:
```php
// In StoreProductRequest.php
public function rules()
{
    return [
        'name' => 'required|string|max:255|unique:products',
        'price' => 'required|numeric|min:0',
        // ... your custom rules
    ];
}
```

### Can I use custom base classes?

Yes! Modify the stub files to extend your custom base classes:
```php
// In controller.stub
class {{ class }} extends YourBaseController
{
    // ...
}
```

## 🚨 Troubleshooting

### The command doesn't work after installation

Try:
```bash
composer dump-autoload
php artisan config:clear
php artisan package:discover
```

### Generated files have wrong namespaces

Check and update the configuration:
```bash
php artisan vendor:publish --tag=easy-dev-config
```

### Relationship detection isn't working

Ensure:
- Database has proper foreign keys
- Migration files exist
- Model namespace is correct
- Run with verbose flag: `php artisan easy-dev:sync-relations -v`

## 🤝 Contributing

### How can I contribute?

- **🐛 Report bugs**: [GitHub Issues](https://github.com/anasnashat/laravel-easy-dev/issues)
- **💡 Suggest features**: [GitHub Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)
- **📝 Improve docs**: Submit PRs for documentation
- **🔧 Submit code**: Follow the contributing guidelines

### Can I create custom extensions?

While the package doesn't have a plugin system, you can:
1. Fork and modify for your needs
2. Create wrapper commands
3. Extend the existing classes
4. Contribute features back to the main package

## 📞 Support

### Where can I get help?

1. **📖 Documentation**: Check this wiki first
2. **🔧 Troubleshooting**: [Troubleshooting Guide](Troubleshooting)
3. **💬 Community**: [GitHub Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)
4. **🐛 Bugs**: [GitHub Issues](https://github.com/anasnashat/laravel-easy-dev/issues)

### How do I report security issues?

For security vulnerabilities, please email the maintainer directly rather than using public issue trackers.

---

**Don't see your question?** Ask in [GitHub Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions) or [create an issue](https://github.com/anasnashat/laravel-easy-dev/issues).
```

## 🚀 Setting Up the Wiki

### 1. Go to your GitHub repository
Navigate to: `https://github.com/anasnashat/laravel-easy-dev`

### 2. Enable Wiki
- Click on "Settings" tab
- Scroll down to "Features" section
- Check "Wikis" if not already enabled

### 3. Create Wiki Pages
- Click on "Wiki" tab
- Click "Create the first page"
- Use the content structure above

### 4. Wiki Navigation Setup
Create these pages in order:
1. `Home` (main landing page)
2. `Quick-Start`
3. `Installation`
4. `Command-Reference`
5. `Configuration`
6. `Relationship-Detection`
7. `API-Development`
8. `Examples-and-Use-Cases`
9. `Complete-Documentation`
10. `Architecture-Patterns`
11. `Testing-Guide`
12. `Troubleshooting`
13. `FAQ`

### 5. Link Structure
Each page should have navigation links to other relevant pages using:
```markdown
[Page Title](Page-Name)
```

## 📱 Social Preview
Create an attractive repository social preview:
- Go to repository Settings
- Scroll to "Social preview"
- Upload an image (1280x640px recommended)
- This appears when sharing the repo link

Your Laravel Easy Dev v2 package is now ready with comprehensive documentation and wiki setup! 🚀
