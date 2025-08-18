# Laravel Easy Dev

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anasnashat/laravel-easy-dev.svg?style=flat-square)](https://packagist.org/packages/anasnashat/laravel-easy-dev)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/anasnashat/laravel-easy-dev/run-tests?label=tests)](https://github.com/anasnashat/laravel-easy-dev/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/anasnashat/laravel-easy-dev/Check%20&%20fix%20styling?label=code%20style)](https://github.com/anasnashat/laravel-easy-dev/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/anasnashat/laravel-easy-dev.svg?style=flat-square)](https://packagist.org/packages/anasnashat/laravel-easy-dev)

**Laravel Easy Dev** is a powerful package that supercharges your Laravel development workflow by generating complete CRUD operations with Repository and Service patterns, intelligent relationship detection, and beautiful command-line interfaces.

## ✨ Features

- 🚀 **Enhanced CRUD Generation** - Generate complete CRUD with Repository and Service patterns
- 🎯 **Interactive UI** - Beautiful command-line interface with guided setup
- 🔄 **Auto Relationship Detection** - Automatically detect and generate model relationships
- 🏗️ **Clean Architecture** - Repository and Service layer generation
- 📝 **Smart Form Requests** - Intelligent validation rules based on field types
- 🌐 **API & Web Support** - Generate controllers for both API and web routes
- 🎨 **Customizable Templates** - Flexible stub templates for all generated files
- 🧪 **Test Generation** - Generate feature and unit tests automatically
- 📚 **Comprehensive Documentation** - Built-in help and examples

## Installation

Install the package via Composer:

```bash
composer require anasnashat/laravel-easy-dev
```

The package will automatically register itself via Laravel's package discovery.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=easy-dev-config
```

Publish the stub files (optional):

```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

## Commands

### 1. Auto-Sync Model Relations

Automatically detect and add relationships to your models based on database schema:

```bash
# Sync relations for a specific model
php artisan model:sync-relations User

# Sync relations for all models
php artisan model:sync-relations --all
```

This command will:
- Analyze foreign keys to detect `belongsTo` and `hasMany` relationships
- Identify pivot tables for `belongsToMany` relationships
- Detect polymorphic relationships based on column naming conventions
- Find self-referencing relationships (parent/child)

### 2. Generate CRUD Operations

Generate complete CRUD operations for a model:

```bash
# Generate web CRUD
php artisan make:crud Post

# Generate API CRUD
php artisan make:crud Post --api
```

This creates:
- Controller with all CRUD methods
- Form request classes for validation
- Resource routes in `web.php` or `api.php`

### 3. Add Manual Relations

Add a specific relationship between two models:

```bash
# Add a belongsTo relation
php artisan make:model-relation Post belongsTo User

# Add a hasMany relation with custom method name
php artisan make:model-relation User hasMany Post --method=articles

# Add a belongsToMany relation
php artisan make:model-relation User belongsToMany Role
```

Supported relation types:
- `hasOne`
- `hasMany` 
- `belongsTo`
- `belongsToMany`
- `morphTo`
- `morphOne`
- `morphMany`

## How It Works

### Database Schema Analysis

The package uses database-specific parsers to analyze your schema:

- **Foreign Keys**: Automatically detected to create `belongsTo` and `hasMany` relationships
- **Pivot Tables**: Tables with multiple foreign keys are identified as pivot tables for `belongsToMany`
- **Polymorphic Relations**: Columns ending in `_type` or `able_type` indicate polymorphic relationships
- **Self-Referencing**: `parent_id` columns create parent/child relationships

### Intelligent Code Generation

- **Relationship Methods**: Generated with appropriate naming conventions
- **Form Requests**: Include basic validation rules that can be customized
- **Controllers**: Follow Laravel conventions with proper error handling
- **Routes**: Automatically added to the appropriate route files

### Customization

You can customize the generated code by publishing and modifying the stub files:

```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

The stubs will be published to `resources/stubs/vendor/easy-dev/` and will take precedence over the package defaults.

## Examples

### Relationship Detection Example

Given these database tables:

```sql
-- users table
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255)
);

-- posts table  
CREATE TABLE posts (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    user_id BIGINT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- post_tag pivot table
CREATE TABLE post_tag (
    post_id BIGINT,
    tag_id BIGINT,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
);
```

Running `php artisan model:sync-relations --all` would automatically add:

**To User model:**
```php
public function posts()
{
    return $this->hasMany(Post::class);
}
```

**To Post model:**
```php
public function user()
{
    return $this->belongsTo(User::class);
}

public function tags()
{
    return $this->belongsToMany(Tag::class);
}
```

**To Tag model:**
```php
public function posts()
{
    return $this->belongsToMany(Post::class);
}
```

### CRUD Generation Example

Running `php artisan make:crud Post` generates:

**PostController.php:**
```php
class PostController extends Controller
{
    public function index() { /* ... */ }
    public function create() { /* ... */ }
    public function store(StorePostRequest $request) { /* ... */ }
    public function show(Post $post) { /* ... */ }
    public function edit(Post $post) { /* ... */ }
    public function update(UpdatePostRequest $request, Post $post) { /* ... */ }
    public function destroy(Post $post) { /* ... */ }
}
```

**Form Request classes** with basic validation rules

**Routes** added to `web.php`:
```php
Route::resource('posts', PostController::class);
```

## Configuration Options

The `config/easy-dev.php` file allows you to customize:

```php
return [
    // Model namespace
    'model_namespace' => 'App\\Models\\',
    
    // File generation paths
    'paths' => [
        'controllers' => app_path('Http/Controllers'),
        'requests' => app_path('Http/Requests'),
        'repositories' => app_path('Repositories'),
    ],
    
    // Route configuration
    'routes' => [
        'api_prefix' => 'api',
        'web_middleware' => ['web'],
        'api_middleware' => ['api'],
    ],
];
```

## Requirements

- PHP 8.1+
- Laravel 9.0+
- One of: MySQL, PostgreSQL, or SQLite

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Anas Nashaat](https://github.com/anasnashat)

## Support

If you discover any security vulnerabilities, please send an e-mail to your-email@example.com instead of using the issue tracker.
