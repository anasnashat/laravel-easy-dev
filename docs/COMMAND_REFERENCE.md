# Command Reference

Complete reference for all Laravel Easy Dev v2 commands.

## 🎯 Primary Commands

### `easy-dev:make`
Enhanced CRUD generator with beautiful interactive UI.

#### Syntax
```bash
php artisan easy-dev:make {model} [options]
```

#### Arguments
- `model` - The name of the model to generate CRUD for

#### Options
- `--with-repository` - Include Repository pattern
- `--with-service` - Include Service layer  
- `--without-interface` - Skip interface generation
- `--api-only` - Generate API controller only
- `--web-only` - Generate web controller only
- `--interactive` - Run in interactive mode
- `--force` - Overwrite existing files
- `--tests` - Generate test files
- `--no-tests` - Skip test generation

#### Examples
```bash
# Basic CRUD generation
php artisan easy-dev:make Product

# With Repository and Service patterns
php artisan easy-dev:make Product --with-repository --with-service

# Interactive mode with all options
php artisan easy-dev:make Product --with-repository --with-service --interactive

# API-only CRUD
php artisan easy-dev:make Product --api-only

# Force overwrite existing files
php artisan easy-dev:make Product --force
```

#### Generated Files
- Controller (web and/or API)
- Form Request classes
- Repository (if requested)
- Service (if requested)
- API Resources
- Routes
- Tests

---

### `easy-dev:crud`
Classic CRUD generator with Repository and Service patterns.

#### Syntax
```bash
php artisan easy-dev:crud {model} [options]
```

#### Arguments
- `model` - The name of the model

#### Options
- `--with-repository` - Generate repository pattern
- `--with-service` - Generate service layer
- `--api-only` - API controller only
- `--web-only` - Web controller only

#### Examples
```bash
# Generate CRUD with patterns
php artisan easy-dev:crud Product --with-repository --with-service

# API-only CRUD
php artisan easy-dev:crud Product --api-only
```

---

## 🔄 Relationship Commands

### `easy-dev:sync-relations`
Automatically detect and add relationships to models based on database schema.

#### Syntax
```bash
php artisan easy-dev:sync-relations {model?} [options]
```

#### Arguments
- `model` - The name of the model (optional if using --all)

#### Options
- `--all` - Sync relationships for all models
- `--morph-targets=MODEL1,MODEL2` - Specify polymorphic relationship targets

#### Examples
```bash
# Sync relationships for specific model
php artisan easy-dev:sync-relations Product

# Sync relationships for all models
php artisan easy-dev:sync-relations --all

# Sync with polymorphic targets
php artisan easy-dev:sync-relations Comment --morph-targets=Post,Video,Image
```

#### Detection Features
- **belongsTo**: Detected from foreign key columns
- **hasMany**: Detected from reverse foreign key relationships
- **belongsToMany**: Detected from pivot tables
- **morphTo/morphMany**: Detected from polymorphic columns
- **Self-referencing**: Detected from parent_id columns

---

### `easy-dev:add-relation`
Manually add a specific relationship between models.

#### Syntax
```bash
php artisan easy-dev:add-relation {model} {type} {related} [options]
```

#### Arguments
- `model` - The source model
- `type` - The relationship type
- `related` - The related model

#### Options
- `--method=NAME` - Custom method name
- `--foreign-key=KEY` - Custom foreign key
- `--local-key=KEY` - Custom local key
- `--pivot-table=TABLE` - Custom pivot table name

#### Supported Types
- `hasOne`
- `hasMany`
- `belongsTo`
- `belongsToMany`
- `morphTo`
- `morphOne`
- `morphMany`

#### Examples
```bash
# Add belongsTo relationship
php artisan easy-dev:add-relation Post belongsTo User

# Add hasMany with custom method
php artisan easy-dev:add-relation User hasMany Post --method=articles

# Add belongsToMany relationship
php artisan easy-dev:add-relation User belongsToMany Role

# Add morphTo relationship
php artisan easy-dev:add-relation Comment morphTo commentable

# Add hasMany with custom foreign key
php artisan easy-dev:add-relation User hasMany Post --foreign-key=author_id
```

---

## 🏗️ Pattern Commands

### `easy-dev:repository`
Generate repository pattern files for existing models.

#### Syntax
```bash
php artisan easy-dev:repository {model} [options]
```

#### Arguments
- `model` - The model name

#### Options
- `--without-interface` - Skip interface generation
- `--force` - Overwrite existing files

#### Examples
```bash
# Generate repository with interface
php artisan easy-dev:repository Product

# Repository without interface
php artisan easy-dev:repository Product --without-interface

# Force overwrite
php artisan easy-dev:repository Product --force
```

#### Generated Files
- Repository class
- Repository interface (unless skipped)
- Service provider binding

---

### `easy-dev:api-resource`
Generate API resource and collection classes.

#### Syntax
```bash
php artisan easy-dev:api-resource {model} [options]
```

#### Arguments
- `model` - The model name

#### Options
- `--collection` - Generate collection class only
- `--resource` - Generate resource class only
- `--force` - Overwrite existing files

#### Examples
```bash
# Generate both resource and collection
php artisan easy-dev:api-resource Product

# Generate resource only
php artisan easy-dev:api-resource Product --resource

# Generate collection only
php artisan easy-dev:api-resource Product --collection
```

#### Generated Files
- ProductResource class
- ProductCollection class

---

## 🎨 Utility Commands

### `easy-dev:help`
Display beautiful help guide with all available commands and options.

#### Syntax
```bash
php artisan easy-dev:help [options]
```

#### Options
- `--examples` - Show usage examples
- `--patterns` - Show pattern explanations

#### Examples
```bash
# Show main help
php artisan easy-dev:help

# Show with examples
php artisan easy-dev:help --examples
```

---

### `easy-dev:demo-ui`
Demonstrate the package's beautiful UI capabilities.

#### Syntax
```bash
php artisan easy-dev:demo-ui
```

#### Features
- Progress bar demonstrations
- Color output examples
- Interactive prompt samples
- Table formatting examples

---

## 🔧 Global Options

These options work with most commands:

### `--force`
Overwrite existing files without prompting.

### `--verbose` / `-v`
Show detailed output and debug information.

### `--quiet` / `-q`
Suppress output except for errors.

### `--no-interaction` / `-n`
Run without any interactive prompts.

### `--help` / `-h`
Show help for the specific command.

---

## 📝 Output Files Reference

### Controllers
```
app/Http/Controllers/{Model}Controller.php
app/Http/Controllers/Api/{Model}Controller.php
```

### Form Requests
```
app/Http/Requests/Store{Model}Request.php
app/Http/Requests/Update{Model}Request.php
```

### Repositories
```
app/Repositories/{Model}Repository.php
app/Repositories/Contracts/{Model}RepositoryInterface.php
```

### Services
```
app/Services/{Model}Service.php
app/Services/Contracts/{Model}ServiceInterface.php
```

### API Resources
```
app/Http/Resources/{Model}Resource.php
app/Http/Resources/{Model}Collection.php
```

### Tests
```
tests/Feature/{Model}ControllerTest.php
tests/Unit/{Model}ServiceTest.php
tests/Unit/{Model}RepositoryTest.php
```

### Routes
Routes are automatically added to:
- `routes/web.php` (for web controllers)
- `routes/api.php` (for API controllers)

---

## 🎯 Quick Reference

### Most Common Commands
```bash
# Complete CRUD with patterns
php artisan easy-dev:make Product --with-repository --with-service --interactive

# Auto-detect all relationships
php artisan easy-dev:sync-relations --all

# Generate API-only CRUD
php artisan easy-dev:make Product --api-only

# Add manual relationship
php artisan easy-dev:add-relation Post belongsTo User

# Generate repository for existing model
php artisan easy-dev:repository Product
```

### Best Practice Workflow
1. Create models and migrations
2. Run migrations
3. Generate CRUD with `easy-dev:make`
4. Auto-detect relationships with `easy-dev:sync-relations --all`
5. Customize generated files as needed
6. Run tests to verify everything works

---

## 🆘 Getting Help

For detailed help on any command:
```bash
php artisan easy-dev:{command} --help
```

For general package help:
```bash
php artisan easy-dev:help
```

For interactive assistance:
```bash
php artisan easy-dev:make {model} --interactive
```
