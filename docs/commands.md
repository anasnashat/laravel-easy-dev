# Laravel Easy Dev - Artisan Commands

This documentation provides details on the custom artisan commands available in the Laravel Easy Dev package.

## Table of Contents

- [make:crud](#makecrud) - Generate complete CRUD functionality for a model
- [make:model-relation](#makemodel-relation) - Add relationships to an existing model
- [model:sync-relations](#modelsync-relations) - Automatically detect and add relationships to models

## make:crud

The `make:crud` command generates a complete set of files for CRUD operations including a model, migration, controller, form requests, and optionally a repository pattern implementation.

### Basic Usage

```bash
php artisan make:crud Post
```

This command will create:
1. A `Post` model with migration
2. `StorePostRequest` and `UpdatePostRequest` form request classes with intelligent validation rules
3. A `PostController` with all necessary CRUD methods
4. (Optionally) A repository interface and implementation for the Post model

### Available Options

| Option | Description |
|--------|-------------|
| `--api` | Generate an API controller that returns JSON responses |
| `--routes` | Automatically add resource routes to the routes file |
| `--force` | Overwrite existing files without prompting |
| `--relations` | Define relationships for the model (format: "model:type,model:type") |

### Examples

**Basic CRUD generation**
```bash
php artisan make:crud Product
```

**API CRUD with routes**
```bash
php artisan make:crud Order --api --routes
```

**CRUD with predefined relationships**
```bash
php artisan make:crud Invoice --relations="user:belongsTo,items:hasMany"
```

### Features

#### Intelligent Field Detection

The command automatically analyzes your migration file to determine model fields and generates appropriate validation rules based on field names:

- Email fields get `email` validation
- Password fields get `min:8` validation
- URL fields get `url` validation
- Date fields get `date` validation
- Boolean fields get `boolean` validation
- Numeric fields get `numeric` validation
- Integer fields get `integer` validation

#### Repository Pattern Support

When enabled (through interactive prompt):
1. Generates a repository interface and implementation
2. Implements all CRUD operations through the repository
3. Adds dependency injection in the controller
4. Registers the repository binding in the service provider

## make:model-relation

The `make:model-relation` command allows you to manually define and add relationships to an existing model.

### Basic Usage

```bash
php artisan make:model-relation Post --relations="user:belongsTo,comment:hasMany,tag:belongsToMany"
```

This command will add the specified relationships to the Post model:
- A `user()` method with a `belongsTo` relationship
- A `comments()` method with a `hasMany` relationship
- A `tags()` method with a `belongsToMany` relationship

### Available Options

| Option | Description |
|--------|-------------|
| `--relations` | Define relationships for the model (format: "model:type,model:type") |

### Supported Relationship Types

- `belongsTo` - Creates a relationship where this model "belongs to" another model
- `hasMany` - Creates a one-to-many relationship
- `hasOne` - Creates a one-to-one relationship
- `belongsToMany` - Creates a many-to-many relationship through a pivot table

### Examples

**Adding a belongsTo and hasMany relationship**
```bash
php artisan make:model-relation Product --relations="category:belongsTo,review:hasMany"
```

**Adding a many-to-many relationship**
```bash
php artisan make:model-relation Post --relations="tag:belongsToMany"
```

The command:
1. Validates that the specified model exists
2. Validates that the relationship types are supported
3. Generates appropriate relationship methods with proper naming conventions
4. Detects if relationship methods already exist to avoid duplicates

## model:sync-relations

The `model:sync-relations` command automatically detects and sets up Eloquent relationships between your models based on database schema and migration files. This is the most powerful relationship command that can analyze your application's structure.

### Basic Usage

To sync relationships for a single model:

```bash
php artisan model:sync-relations Post
```

This command will:
1. Analyze the database schema for foreign keys related to the Post model
2. Parse migration files if database tables don't exist
3. Add relationship methods to the Post model
4. Add inverse relationship methods to related models (e.g., if Post belongs to User, User hasMany Posts)

### Available Options

| Option | Description |
|--------|-------------|
| `--all` | Sync relationships for all models in your application |
| `--morph-targets` | Comma-separated list of models to apply polymorphic relationships to |

### Using with All Models

To scan and update relationships for all models in your application:

```bash
php artisan model:sync-relations --all
```

This will process every model in your `app/Models` directory, detecting and adding relationships automatically.

### Polymorphic Relationships

The command can detect and set up polymorphic relationships from your migrations. For example, if you have an `Image` model with a polymorphic `imageable` relationship:

```php
// Migration example
Schema::create('images', function (Blueprint $table) {
    $table->id();
    $table->string('url');
    $table->morphs('imageable'); // Creates imageable_id and imageable_type columns
    $table->timestamps();
});
```

Running:

```bash
php artisan model:sync-relations Image
```

Will add:
1. The `imageable()` method with `morphTo()` relation to the Image model
2. The `images()` methods with `morphMany()` relation to potential related models

The command will interactively ask which models should have the polymorphic relationship added.

### Targeting Specific Models for Polymorphic Relations

When working with polymorphic relationships, you can specify which models should receive the polymorphic relations using the `--morph-targets` option:

```bash
php artisan model:sync-relations Image --morph-targets="Post,User"
```

This will only add the `images()` method to the Post and User models, without prompting for each model.

### Self-Referential Relationships

The command also handles unary (self-referential) relationships, such as categories with parent-child relationships:

```php
// Migration example
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->foreign('parent_id')->references('id')->on('categories');
    $table->timestamps();
});
```

Running:

```bash
php artisan model:sync-relations Category
```

Will add:
1. A `parent()` method to access the parent category
2. A `children()` method to access child categories

### Examples

**Example 1: Basic Foreign Key Relationship**

Migration:
```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
});
```

Command:
```bash
php artisan model:sync-relations Post
```

Results:
- Adds `user()` method to Post model
- Adds `posts()` method to User model

**Example 2: Many-to-Many Relationship**

Migration:
```php
Schema::create('post_tag', function (Blueprint $table) {
    $table->foreignId('post_id')->constrained();
    $table->foreignId('tag_id')->constrained();
    $table->primary(['post_id', 'tag_id']);
});
```

Command:
```bash
php artisan model:sync-relations Post
```

Results:
- Adds `tags()` method to Post model
- Adds `posts()` method to Tag model

**Example 3: Polymorphic Relationship**

Migration:
```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->text('body');
    $table->morphs('commentable');
    $table->timestamps();
});
```

Command:
```bash
php artisan model:sync-relations Comment --morph-targets="Post,Video"
```

Results:
- Adds `commentable()` method to Comment model (correctly named without _type suffix)
- Adds `comments()` method to Post model
- Adds `comments()` method to Video model

## Differences Between Relationship Commands

| Feature | `model:sync-relations` | `make:model-relation` | `make:crud --relations` |
|---------|------------------------|----------------------|-------------------------|
| Automatic detection | ✅ Uses database schema & migrations | ❌ Requires manual specification | ⚠️ Only detects basic relationships |
| Multiple models | ✅ Can process all models at once | ❌ One model at a time | ❌ Only for the model being created |
| Bidirectional relationships | ✅ Creates both sides of relationships | ❌ Only adds to one model | ❌ Only adds to one model |
| Polymorphic support | ✅ Full detection and configuration | ❌ Not supported | ❌ Not supported |
| Requires existing tables | ⚠️ Falls back to migrations if no tables | ✅ Only needs model file | ✅ Only needs model file |

## Troubleshooting

### Tables Not Found
If the command reports that tables are not found in the database, make sure:
1. Your database connection is properly configured
2. You've run migrations or the migration files exist
3. The command will fall back to parsing migration files if tables don't exist

### Relationship Method Already Exists
If the command says "Relationship method already exists", the relationship is already defined in the model. The command will skip adding it to avoid duplicates.

### Missing Models
If related models don't exist, create them first using:
```bash
php artisan make:model ModelName
```

### Database Driver Support
The `model:sync-relations` command supports MySQL, PostgreSQL, and SQLite database drivers. Each driver has specific methods for detecting relationships.