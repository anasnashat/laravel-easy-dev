# Laravel Easy Dev v3 - Command Reference

Complete reference for all Laravel Easy Dev v3 commands, customization options, and AI-native workflows.

---

## 📋 Table of Contents
1. [Primary Commands](#-primary-commands)
2. [Pattern & File Generators](#-pattern--file-generators)
3. [Relationship Commands](#-relationship-commands)
4. [AI-Native Integration Commands](#-ai-native-integration-commands)
5. [Universal Flags & Options](#-universal-flags--options)
6. [Output Files & Namespaces Reference](#-output-files--namespaces-reference)

---

## 🎯 Primary Commands

### `easy-dev:make`
Enhanced CRUD generator with a beautiful interactive UI wizard.

#### Syntax
```bash
php artisan easy-dev:make {model} [options]
```

#### Arguments
- `model` - The name of the model to generate CRUD for.

#### Options
- `--with-repository` - Include Repository pattern classes.
- `--with-service` - Include Service layer logic classes.
- `--without-interface` - Skip Interface/Contract generation (use concrete classes directly).
- `--api-only` - Generate API resource and API controller only (skip web resources).
- `--web-only` - Generate web controller only (skip API endpoints).
- `--interactive` - Force the step-by-step guided configuration wizard.
- `--force` - Overwrite existing files without prompting.
- `--tests` - Generate corresponding feature and unit test files.

#### Examples
```bash
# Start the interactive UI scaffold wizard
php artisan easy-dev:make Product --interactive

# Standard scaffolding with tests
php artisan easy-dev:make Order --tests
```

---

### `easy-dev:crud`
Classic non-interactive CRUD generator. Highly optimized for quick CLI commands and AI scripting.

#### Syntax
```bash
php artisan easy-dev:crud {model} [options]
```

#### Arguments
- `model` - The name of the model to generate CRUD for.

#### Options
- `--with-repository` - Include Repository pattern.
- `--with-service` - Include Service layer.
- `--without-interface` - Skip Interface/Contract generation.
- `--api-only` - Generate API controller only.
- `--web-only` - Generate web controller only.
- `--stub=NAME` - Specify a custom stub name or absolute file path to use as a template.
- `--path=DIR` - Specify a custom target folder output directory.
- `--module=NAME` - Scaffold everything inside a Domain Module (e.g. `app/Modules/Billing`).
- `--ai` - Silent mode: Suppress all text, return machine-friendly JSON context.

#### Examples
```bash
# Generate basic CRUD
php artisan easy-dev:crud Post

# Full architecture CRUD inside a Domain Module
php artisan easy-dev:crud Payment --module=Billing --with-repository --with-service
```

---

## 🏗️ Pattern & File Generators

These generators allow you to create individual pattern files or custom architecture files independently. They all support the universal v3 customization flags (`--stub`, `--path`, `--module`, `--ai`).

### `easy-dev:repository`
Generate repository pattern files (Interface + Implementation) for a model.

```bash
# Generate ProductRepository and ProductRepositoryInterface
php artisan easy-dev:repository Product

# Skip interface contract and generate concrete class under a custom path
php artisan easy-dev:repository Product --without-interface --path=app/Custom/Repositories
```

### `easy-dev:api-resource`
Generate API resource and collection classes for unified JSON output.

```bash
# Generate both ProductResource and ProductCollection
php artisan easy-dev:api-resource Product

# Generate collection class only
php artisan easy-dev:api-resource Product --collection
```

### `easy-dev:policy`
Generate a custom authorization policy class.

```bash
# Generate ProductPolicy
php artisan easy-dev:policy Product
```

### `easy-dev:dto`
Generate a custom Data Transfer Object (DTO) data class.

```bash
# Generate OrderDto with smart fillable properties
php artisan easy-dev:dto Order
```

### `easy-dev:observer`
Generate database lifecycle observer for clean event handling.

```bash
# Generate UserObserver
php artisan easy-dev:observer User
```

### `easy-dev:filter`
Generate query filter class to handle advanced and dynamic search filters.

```bash
# Generate PostFilter
php artisan easy-dev:filter Post
```

### `easy-dev:enum`
Generate status/type schema PHP enums.

```bash
# Generate OrderStatus enum
php artisan easy-dev:enum OrderStatus
```

---

## 🔄 Relationship Commands

### `easy-dev:sync-relations`
Automatically analyze the database schema and inject relationships into models.

#### Syntax
```bash
php artisan easy-dev:sync-relations {model?} [options]
```

#### Options
- `--all` - Scan all database tables and sync relationships for all models.
- `--morph-targets=MODEL1,MODEL2` - Specify polymorphic target models explicitly.

#### Examples
```bash
# Scan and sync relations for User model
php artisan easy-dev:sync-relations User

# Scan and sync the entire database schema automatically
php artisan easy-dev:sync-relations --all
```

---

### `easy-dev:add-relation`
Manually inject a specific relationship method into an existing model file.

#### Syntax
```bash
php artisan easy-dev:add-relation {model} {type} {related} [options]
```

#### Arguments
- `model` - The source model to write the method to (e.g. `Post`).
- `type` - The relationship type (`belongsTo`, `hasMany`, `belongsToMany`, etc.).
- `related` - The target model to connect to (e.g. `User`).

#### Options
- `--method=NAME` - Custom method name (defaults to snake_case of target).
- `--foreign-key=KEY` - Explicit foreign key column name.
- `--local-key=KEY` - Explicit local/owner key column name.
- `--pivot-table=TABLE` - Custom pivot table name (for `belongsToMany`).

#### Examples
```bash
# Inject belongsTo User relation into Post model
php artisan easy-dev:add-relation Post belongsTo User

# Inject hasMany with custom method and foreign key
php artisan easy-dev:add-relation User hasMany Post --method=articles --foreign-key=author_id
```

---

## 🤖 AI-Native Integration Commands

Laravel Easy Dev v3 introduces advanced high-density commands designed to connect your local development space to AI coding agents seamlessly.

### `easy-dev:publish-stubs`
Publish customizable template stubs to your application directory and generate an AI skill guide.

#### Options
- `--only=NAME,NAME` - Publish only specific stubs (comma-separated, e.g. `--only=model,controller`).
- `--list` - List all available stubs without writing them.
- `--ai` - Silent mode: output machine-friendly JSON list/status.

#### Features
- Publishes templates to `resources/stubs/vendor/easy-dev/` for instant project-level customization.
- **AI Skill Guide**: Automatically places a comprehensive AI instruction manual `easy-dev-ai.md` in your project root, letting external AI agents easily find, read, and master the scaffolding toolset!

```bash
# Publish all stubs and easy-dev-ai.md
php artisan easy-dev:publish-stubs

# List available stubs in machine-friendly format
php artisan easy-dev:publish-stubs --list --ai
```

---

### `easy-dev:ai-context`
Generate a high-density, token-efficient JSON context map of your entire project configuration, database structure, models, stubs, and environment.

#### Options
- `--pretty` - Pretty print the JSON output.

#### Usage
Pipe this command directly into your AI context:
```bash
php artisan easy-dev:ai-context --pretty
```

---

### `easy-dev:snapshot`
Print a high-density database and models schema mapping.

```bash
# Print a beautiful visual database snapshot
php artisan easy-dev:snapshot

# Output token-efficient JSON representation for AI
php artisan easy-dev:snapshot --ai
```

---

### `easy-dev:info`
Output a comprehensive audit report of a specific model's fields, database columns, fillables, hidden attributes, casts, and relations.

```bash
# Print markdown audit report for Post model
php artisan easy-dev:info Post

# Output JSON audit report for AI
php artisan easy-dev:info Post --ai
```

---

### `easy-dev:dream`
Parse natural language instructions to dynamically compile blueprints, migrations, and model properties in real time.

#### Options
- `--dry-run` - Show the compiled database schema blueprint without writing any files.
- `--ai` - Silent mode: output machine-friendly JSON blueprints.

```bash
# Dry run compile using natural language
php artisan easy-dev:dream "Create post with title:string, body:text connected to users" --dry-run
```

---

## 🔧 Universal Flags & Options

These v3 customization options are supported across **all** generator commands:

### `--stub=NAME_OR_PATH`
Override the default stub template. You can pass either a stub name (which checks custom published stubs, then package stubs) or an absolute file path to a custom `.stub` file.
```bash
php artisan easy-dev:crud Article --stub=article-premium
```

### `--path=DIRECTORY`
Override the default target folder directory for the generated file. The generator will automatically resolve and inject correct PSR-4 namespaces.
```bash
php artisan easy-dev:repository Book --path=app/Domains/Books/Repositories
```

### `--module=MODULE_NAME`
Enable Domain-Driven modular structure. Places all generated files inside `app/Modules/MODULE_NAME/` under clean module directories (`Models/`, `Http/Controllers/`, `Repositories/`, etc.) with correct namespace and imports auto-resolved.
```bash
php artisan easy-dev:crud Ticket --module=Support --with-repository
```

### `--ai`
Enables silent AI execution mode. All console interaction is skipped, and the command outputs structured JSON containing success status, generated file paths, namespaces, and content metadata.
```bash
php artisan easy-dev:policy Product --ai
```

---

## 📝 Output Files & Namespaces Reference

When generating files, Laravel Easy Dev places them in standard directories (or inside custom modules if `--module` is used):

| Component | Default Folder Path | Default PSR-4 Namespace |
| :--- | :--- | :--- |
| **Model** | `app/Models/` | `App\Models` |
| **Web Controller** | `app/Http/Controllers/` | `App\Http\Controllers` |
| **API Controller** | `app/Http/Controllers/Api/` | `App\Http\Controllers\Api` |
| **Form Requests** | `app/Http/Requests/` | `App\Http\Requests` |
| **Repository** | `app/Repositories/` | `App\Repositories` |
| **Repo Contract** | `app/Repositories/Contracts/` | `App\Repositories\Contracts` |
| **Service** | `app/Services/` | `App\Services` |
| **Service Contract** | `app/Services/Contracts/` | `App\Services\Contracts` |
| **API Resource** | `app/Http/Resources/` | `App\Http\Resources` |
| **Policy** | `app/Policies/` | `App\Policies` |
| **DTO** | `app/DTOs/` | `App\DTOs` |
| **Observer** | `app/Observers/` | `App\Observers` |
| **Filter** | `app/Filters/` | `App\Filters` |
| **Enum** | `app/Enums/` | `App\Enums` |
| **Feature Test** | `tests/Feature/` | `Tests\Feature` |
| **Unit Test** | `tests/Unit/` | `Tests\Unit` |

*If modular mode `--module=Billing` is active, files are placed in `app/Modules/Billing/{Subfolder}` and namespaces are mapped to `App\Modules\Billing\{Subfolder}`.*
