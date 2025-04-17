# Laravel CRUD & Model Relations Generator

A Laravel package that provides powerful artisan commands to generate CRUD operations and manage model relationships with minimal effort.

## Installation

You can install the package via composer:

```bash
composer require anas/easy-dev
```

## Features

- **CRUD Generation**: Generate models, controllers, repositories, and form requests with a single command
- **Model Relations**: Easily add relationships to existing models
- **Database Sync**: Scan database structure to automatically detect and add model relationships
- **Field Detection**: Automatically detect fields from migrations and generate appropriate validation rules
- **Repository Pattern**: Option to implement the repository pattern with dependency injection

## Usage

### Generate CRUD 

```bash
# Basic usage
php artisan make:crud Post

# With API controller
php artisan make:crud Post --api

# With routes and predefined relationships
php artisan make:crud Post --routes --relations="user:belongsTo,comment:hasMany"
```

### Add Relationships to Models

```bash
php artisan make:model-relation Post --relations="user:belongsTo,tag:belongsToMany,comment:hasMany"
```

### Sync Database Relationships

```bash
# For a specific model
php artisan model:sync-relations Post

# For all models
php artisan model:sync-relations --all

# For polymorphic relationships
php artisan model:sync-relations Post --morph-targets="User,Comment"
```

## Documentation

For detailed documentation, see the [Laravel Easy Dev Commands](docs/commands.md) guide.

### make:crud

The `make:crud` command generates a complete set of files for CRUD operations including a model, migration, controller, form requests, and optionally a repository pattern implementation.

#### Features

- Intelligent field detection for validation rules
- Repository pattern support with dependency injection
- Smart relationship detection from database structure
- Optional API controller support
- Automatic route registration

#### Options

| Option | Description |
|--------|-------------|
| `--api` | Generate an API controller that returns JSON responses |
| `--routes` | Automatically add resource routes to the routes file |
| `--force` | Overwrite existing files without prompting |
| `--relations` | Define relationships for the model (format: "model:type,model:type") |

### make:model-relation

Add relationships to existing models with proper methods and naming conventions.

#### Options

| Option | Description |
|--------|-------------|
| `--relations` | Define relationships for the model (format: "model:type,model:type") |

#### Supported Relationship Types

- `belongsTo` - Creates a relationship where this model "belongs to" another model
- `hasMany` - Creates a one-to-many relationship
- `hasOne` - Creates a one-to-one relationship
- `belongsToMany` - Creates a many-to-many relationship through a pivot table

### model:sync-relations

Automatically detect and set up Eloquent relationships between your models based on database schema and migration files.

#### Options

| Option | Description |
|--------|-------------|
| `--all` | Sync relationships for all models in your application |
| `--morph-targets` | Comma-separated list of models to apply polymorphic relationships to |

## Configuration

You can publish the configuration file with:

```bash
php artisan vendor:publish --tag=easy-dev-config
```

This will publish a `easy-dev.php` file in your config directory where you can customize the default behavior.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.