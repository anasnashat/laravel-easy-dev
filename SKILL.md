---
name: laravel-easy-dev
version: "3.4"
description: >
  Complete AI agent skill guide for the `anas/easy-dev` Laravel code generation package.
  Covers CRUD scaffolding, Repository & Service patterns, Clean Architecture (DDD) presets,
  modular generation, relationship detection, natural language "Dream" scaffolding, and
  machine-friendly JSON output for programmatic integration.
---

# Laravel Easy Dev v3 — AI Developer Agent Skill Guide

This skill file provides autonomous AI coding assistants (Cursor, Gemini, Claude, Copilot, Antigravity, etc.) with complete architectural rules, command references, formatting rules, and error-recovery strategies to leverage `laravel-easy-dev` with maximum efficiency.

---

## 🎯 Strategic Objective

Use the `easy-dev` generator suite to rapidly, consistently, and reliably scaffold:

- Database migrations with typed columns and foreign keys
- Eloquent models with fillable, casts, and relationship methods
- Repository layer (implementation + interface contracts)
- Service layer (implementation + interface contracts)
- Controllers (Web & API) with dependency injection
- Validation form requests (Store & Update) with auto-generated rules
- API Resources & Collections
- Policies, DTOs, Observers, Filters, and backed Enums

All generated code follows **Total Harmony** — method signatures are consistent across Controller → Service → Repository layers, guaranteeing compile-time correctness.

---

## 🛠️ The Discovery Rule (Always Start Here)

Before executing any generation command, you **MUST** audit the current project's configuration, active database structures, schemas, and templates.

### Step 1: Full Context Map

```bash
php artisan easy-dev:ai-context --pretty
```

This returns a comprehensive JSON payload containing:

| Key | What It Contains |
|-----|------------------|
| `paths` | Configured output directories for all file types |
| `modules` | Whether modular architecture is enabled and root path |
| `stubs` | Active template names, resolved paths, and whether they are customized |
| `models` | Existing Eloquent models with their table names, columns (name, type, nullable), and discovered relationships |
| `commands_reference` | Available commands with their arguments and options |
| `ai_guidance` | Inline instructions for this AI agent |

### Step 2: Token-Efficient Schema Snapshot

If you only need the database schema and relationships (smaller payload):

```bash
php artisan easy-dev:snapshot --ai
```

Returns:
```json
{
  "status": "success",
  "project": "my-app",
  "models": {
    "User": {
      "table": "users",
      "columns": [
        {"name": "id", "type": "integer", "nullable": false, "default": null},
        {"name": "email", "type": "varchar", "nullable": false, "default": null}
      ],
      "relations": [
        {"name": "posts", "type": "hasMany", "related": "Post"}
      ]
    }
  }
}
```

### Step 3: Inspect a Single Model

```bash
php artisan easy-dev:info ModelName --ai
```

Returns detailed column-level data including fillable, hidden, cast, validation requests, and relationships for a single model.

---

## 🚀 Command Reference

### Core Generation Commands

#### `easy-dev:crud` — The Primary Generator

Generates a complete CRUD system for a model with all architectural layers.

```bash
php artisan easy-dev:crud {model} [options] --ai
```

| Option | Description |
|--------|-------------|
| `--with-repository` | Generate Repository class + RepositoryInterface |
| `--with-service` | Generate Service class + ServiceInterface |
| `--with-policy` | Generate authorization Policy |
| `--with-dto` | Generate Data Transfer Object |
| `--with-observer` | Generate model Observer |
| `--api-only` | Generate only API controller and routes (no web) |
| `--web-only` | Generate only web controller and routes (no API) |
| `--without-interface` | Skip Interface generation for Repository/Service |
| `--dry-run` | Preview files without creating them |
| `--stub=` | Override default stub template name or absolute path |
| `--path=` | Override generation root output directory |
| `--module=` | Place all files inside a Domain Module (e.g. `Orders`) |
| `--preset=clean` | Use Clean Architecture / DDD folder layout |
| `--ai` | Suppress interactive text, return structured JSON |

**Success JSON Payload:**
```json
{
  "status": "success",
  "command": "easy-dev:crud",
  "model": "Product",
  "generated": [
    {
      "type": "model",
      "name": "Product",
      "path": "app/Models/Product.php",
      "stub_used": "packages/laravel-easy-dev/resources/stubs/model.enhanced.stub"
    },
    {
      "type": "repository",
      "name": "ProductRepository",
      "path": "app/Repositories/ProductRepository.php",
      "stub_used": "..."
    }
  ]
}
```

**Error JSON Payload:**
```json
{
  "status": "error",
  "message": "Cannot specify both --api-only and --web-only options.",
  "suggestions": [
    "Verify database table permissions and constraints.",
    "Check write permissions on target generation directories."
  ]
}
```

#### `easy-dev:make` — Interactive CRUD Wizard

Wraps `easy-dev:crud` with a step-by-step interactive CLI wizard. If no arguments are passed, it enters interactive mode asking for model name, architecture choices, and confirmation.

```bash
# Interactive mode
php artisan easy-dev:make

# Non-interactive (same options as easy-dev:crud)
php artisan easy-dev:make Product --with-repository --with-service --module=Sales --preset=clean
```

---

#### `easy-dev:dream` — Natural Language Scaffolding

Scaffold an entire entity with schema fields and relationships from a natural language prompt.

```bash
php artisan easy-dev:dream "{prompt}" [--ai] [--dry-run] [--module=] [--preset=]
```

**Prompt Parsing Rules:**
1. **Model Detection**: Extracts the model noun from verbs like `create`, `add`, `generate`, `make`, `new`
2. **Article Filtering**: Automatically strips leading "A", "An", "The" from prompts
3. **Field Extraction**: Parses `field:type` syntax (e.g. `status:string`, `price:decimal`) and `field as type` syntax
4. **Relationship Detection**: Parses `connected to`, `belongs to`, `related to`, `linked to`, `associated with` followed by model names

**Example Prompts:**
```bash
# Simple entity
php artisan easy-dev:dream "Create customer subscriptions with price:decimal status:string connected to users and products" --ai

# With module and clean preset
php artisan easy-dev:dream "A new Invoice with total:decimal tax:float connected to orders" --module=Billing --preset=clean --ai

# Preview only
php artisan easy-dev:dream "Add product reviews with rating:integer body:text connected to products and users" --dry-run --ai
```

**What it does:**
1. Calls `easy-dev:crud` with `--api`, `--with-repository`, `--with-service`
2. Enhances the generated migration with parsed columns and foreign keys
3. Runs `easy-dev:sync-relations` to wire up Eloquent relationships

---

### Individual Generator Commands

All individual generators share these common options:

| Option | Description |
|--------|-------------|
| `--stub=` | Custom stub template name or absolute path |
| `--path=` | Override output directory |
| `--module=` | Nest inside a modular layout |
| `--preset=` | Use architecture preset (e.g. `clean`) |
| `--ai` | Silent JSON output |

#### `easy-dev:repository {model}`
Generate Repository pattern files. Add `--without-interface` to skip the contract.

#### `easy-dev:api-resource {model}`
Generate API Resource and Collection classes. Add `--without-collection` to skip the collection.

#### `easy-dev:policy {model}`
Generate authorization Policy with `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.

#### `easy-dev:dto {model}`
Generate Data Transfer Object with `fromRequest()`, `fromModel()`, `toArray()`.

#### `easy-dev:observer {model}`
Generate model Observer with `creating`, `created`, `updating`, `updated`, `deleting`, `deleted` hooks.

#### `easy-dev:filter {model}`
Generate query Filter class with `apply()` method.

#### `easy-dev:enum {name} --values={comma-separated}`
Generate PHP 8.1+ string-backed enum with `values()` and `label()` helpers.

---

### Relationship Commands

#### `easy-dev:sync-relations {model?} [--all]`
Auto-detect relationships from database schema or migration files and inject them into model files.

```bash
# Sync one model
php artisan easy-dev:sync-relations Product

# Sync ALL models
php artisan easy-dev:sync-relations --all
```

**Detection sources:**
1. Database schema (`PRAGMA foreign_key_list` for SQLite, `information_schema` for MySQL/PgSQL)
2. Migration file parsing (`foreignId()`, `constrained()`, `morphs()`)
3. Cross-migration scanning for reverse relationships

**Detects:** `belongsTo`, `hasMany`, `morphTo`, `morphMany`

#### `easy-dev:add-relation {model} {relation} {related-model} [--method=]`
Manually add a relationship method to an existing model.

**Supported types:** `hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, `morphTo`, `morphOne`, `morphMany`

---

### AI-Native & Inspection Commands

#### `easy-dev:ai-context [--pretty]`
Comprehensive project context map for AI agents. Returns paths, modules, stubs, models, columns, relationships, and commands reference.

#### `easy-dev:snapshot [--ai]`
High-density, token-efficient snapshot of all models, their database schemas, and relationships.

#### `easy-dev:info {model} [--ai]`
Detailed single-model audit: columns with types/nullable/defaults, fillable/hidden/casts, relationships, and existing form requests.

---

### Stub Management

#### `easy-dev:publish-stubs [--only=] [--list] [--ai]`
Publish package stub templates to your application for customization.

```bash
# List available stubs
php artisan easy-dev:publish-stubs --list --ai

# Publish all stubs
php artisan easy-dev:publish-stubs --ai

# Publish specific stubs only
php artisan easy-dev:publish-stubs --only=controller.api.enhanced,service.enhanced --ai
```

**Also publishes:** `SKILL.md` → `easy-dev-ai.md` in project root for AI agent discoverability.

**Stub Resolution Chain (4 layers):**
1. Explicit `--stub=path` from CLI
2. Project-level published stubs in `resources/stubs/vendor/easy-dev/`
3. Config-mapped stub name in `config/easy-dev.php` → `stubs` section
4. Default package stubs (fallback)

---

### Help & UI Commands

#### `easy-dev:help [--examples]`
Display the built-in help guide with all commands, options, and examples.

#### `easy-dev:beautiful-help`
Enhanced styled help command with boxes, colors, and categorized output.

#### `easy-dev:demo-ui`
Demo command showcasing the package's beautiful CLI output capabilities.

---

## 🏗️ Architecture Presets

### Standard MVC (default)

Files are placed in standard Laravel directories:

```
app/
├── Models/Product.php
├── Http/Controllers/ProductController.php
├── Http/Controllers/Api/ProductApiController.php
├── Http/Requests/StoreProductRequest.php
├── Http/Requests/UpdateProductRequest.php
├── Http/Resources/ProductResource.php
├── Repositories/ProductRepository.php
├── Repositories/Contracts/ProductRepositoryInterface.php
├── Services/ProductService.php
├── Services/Contracts/ProductServiceInterface.php
├── Policies/ProductPolicy.php
├── DTOs/ProductData.php
└── Observers/ProductObserver.php
```

### Clean Architecture / DDD (`--preset=clean`)

When combined with `--module=`, files are organized into Domain-Driven Design layers:

```bash
php artisan easy-dev:crud Product --module=Catalog --preset=clean --with-repository --with-service --with-policy --with-dto --ai
```

```
app/Modules/Catalog/
├── Domain/
│   ├── Models/Product.php                              # Eloquent Model
│   ├── Enums/                                          # Backed Enums
│   └── Repositories/ProductRepositoryInterface.php     # Repository Contract
├── Application/
│   ├── Services/ProductService.php                     # Service Implementation
│   ├── Services/ProductServiceInterface.php            # Service Contract
│   └── DTOs/ProductData.php                            # Data Transfer Object
├── Infrastructure/
│   ├── Repositories/ProductRepository.php              # Repository Implementation
│   ├── Policies/ProductPolicy.php                      # Authorization Policy
│   ├── Observers/ProductObserver.php                    # Model Observer
│   ├── Filters/ProductFilter.php                       # Query Filter
│   └── Providers/CatalogServiceProvider.php            # Auto-managed bindings
└── Presentation/
    └── Http/
        ├── Controllers/Api/ProductApiController.php    # API Controller
        ├── Controllers/ProductController.php           # Web Controller
        ├── Requests/StoreProductRequest.php            # Store Validation
        ├── Requests/UpdateProductRequest.php           # Update Validation
        └── Resources/ProductResource.php               # API Resource
```

**Key DDD Behaviors:**
- Repository interfaces go in `Domain/Repositories/` (contracts belong to the domain)
- Repository implementations go in `Infrastructure/Repositories/`
- Service Provider is auto-created/moved to `Infrastructure/Providers/`
- Bindings (`$this->app->bind(Interface::class, Implementation::class)`) are auto-injected
- `module.json` namespace references are auto-updated
- Empty directories are automatically cleaned up

---

## ⚙️ Configuration Reference

After publishing: `php artisan vendor:publish --tag=easy-dev-config`

Key config sections in `config/easy-dev.php`:

| Section | Purpose |
|---------|---------|
| `model_namespace` | Default model namespace (`App\Models\`) |
| `paths.*` | Output directories for all 17 file types |
| `routes.*` | API/web prefix, middleware, route model binding |
| `stubs.*` | Map config keys to stub template names |
| `modules.enabled` | Enable modular architecture |
| `modules.path` | Root directory for domain modules (`app/Modules`) |
| `modules.namespaces.*` | Sub-folder mapping per file type within modules |
| `defaults.*` | Default flags for commands (repository, service, interface, controllers, tests, factory, seeder) |
| `validation.rules.*` | Validation rules by column type (string, integer, date, etc.) |
| `validation.field_patterns.*` | Validation rules by field name pattern (email, password, slug, _id) |
| `relationships.*` | Auto-detect, polymorphic detection, reverse relationships, key suffixes |
| `ui.*` | Progress bar, banner, icons, colored output, interactive mode |
| `database.*` | Constraint analysis, index analysis, migration parsing, supported drivers |

---

## 💡 AI Agent Playbook

### Playbook A: Scaffold a Standard CRUD

```bash
# 1. Discover project context
php artisan easy-dev:ai-context --pretty

# 2. Generate full CRUD with all layers
php artisan easy-dev:crud Product --with-repository --with-service --with-policy --with-dto --ai

# 3. Run migrations
php artisan migrate

# 4. Sync relationships
php artisan easy-dev:sync-relations Product
```

### Playbook B: Scaffold a Clean Architecture Module

```bash
# 1. Generate modular DDD CRUD
php artisan easy-dev:crud Booking --module=Bookings --preset=clean --with-repository --with-service --ai

# 2. Add policy and DTO
php artisan easy-dev:policy Booking --module=Bookings --preset=clean --ai
php artisan easy-dev:dto Booking --module=Bookings --preset=clean --ai

# 3. Add enum
php artisan easy-dev:enum BookingStatus --values=pending,confirmed,cancelled --module=Bookings --preset=clean --ai
```

### Playbook C: Natural Language Feature Scaffolding

```bash
# 1. Dream up the entire feature
php artisan easy-dev:dream "Create customer invoices with total:decimal tax:float status:string connected to customers and orders" --module=Billing --preset=clean --ai

# 2. Verify the snapshot
php artisan easy-dev:snapshot --ai
```

### Playbook D: Customize Stub Templates

```bash
# 1. List available stubs
php artisan easy-dev:publish-stubs --list --ai

# 2. Publish specific stubs
php artisan easy-dev:publish-stubs --only=controller.api.enhanced,service.enhanced --ai

# 3. Edit published stubs in resources/stubs/vendor/easy-dev/
# 4. Future generations automatically use your customized stubs
```

---

## 🔧 Error Handling & Self-Correction

All commands return structured JSON in `--ai` mode. On error:

```json
{
  "status": "error",
  "message": "Human-readable error description",
  "suggestions": [
    "Actionable fix suggestion 1",
    "Actionable fix suggestion 2"
  ]
}
```

**Common errors and recovery:**

| Error | Recovery |
|-------|----------|
| Model class not found | Run `php artisan easy-dev:crud ModelName --ai` to create it first |
| Migration table does not exist | Run `php artisan migrate` |
| Cannot specify both --api-only and --web-only | Choose one or omit both for dual generation |
| Stub file not found | Check stub name with `easy-dev:publish-stubs --list --ai` |
| Database connection failed | Verify `.env` database credentials |

---

---

## ✏️ Editing & Customizing Stub Templates

This section teaches AI agents how to **modify the generated file templates** to match the project's coding conventions, add custom logic, or enforce architectural patterns.

### The Template Editing Workflow

```bash
# Step 1: Publish stubs to the project (makes them editable)
php artisan easy-dev:publish-stubs --ai

# Step 2: Edit the published stubs at:
#   resources/stubs/vendor/easy-dev/*.stub

# Step 3: Future generations automatically use your edited stubs
php artisan easy-dev:crud Product --with-repository --with-service --ai
```

To publish only specific stubs:
```bash
php artisan easy-dev:publish-stubs --only=controller.api.enhanced,service.enhanced,repository.enhanced --ai
```

The published stubs at `resources/stubs/vendor/easy-dev/` take **priority** over the package defaults. You can freely edit them.

### Stub File Locations

| Source | Path | Priority |
|--------|------|----------|
| Published (editable) | `resources/stubs/vendor/easy-dev/*.stub` | **Highest** |
| Config-mapped | Name set in `config/easy-dev.php` → `stubs.*` | Medium |
| Package defaults | `vendor/anas/easy-dev/resources/stubs/*.stub` | Lowest (fallback) |

### Available Stubs (34 total)

| Category | Stub Files |
|----------|------------|
| **Models** | `model.stub` |
| **Controllers** | `controller.stub`, `controller.enhanced.stub`, `controller.api.stub`, `controller.api.enhanced.stub`, `controller.repository.stub`, `controller.api.service.stub`, `controller.web.service.stub` |
| **Repository** | `repository.stub`, `repository.enhanced.stub`, `repository.interface.stub`, `repository.interface.enhanced.stub` |
| **Service** | `service.stub`, `service.enhanced.stub`, `service.interface.stub`, `service.interface.enhanced.stub` |
| **Requests** | `request.store.stub`, `request.update.stub`, `request.enhanced.stub` |
| **Resources** | `api.resource.stub`, `api.collection.stub` |
| **Generators** | `policy.stub`, `dto.stub`, `observer.stub`, `filter.stub`, `enum.stub` |
| **Relations** | `relations/belongsTo.stub`, `relations/hasOne.stub`, `relations/hasMany.stub`, `relations/belongsToMany.stub`, `relations/morphTo.stub`, `relations/morphOne.stub`, `relations/morphMany.stub` |
| **Other** | `factory.stub` |

### Placeholder Variables Reference

All stubs use the double-curly-brace syntax for placeholders. The generator replaces these at generation time.

#### Universal Variables (available in ALL stubs)

| Variable | Description | Example Value |
|----------|-------------|---------------|
| `namespace` | Auto-resolved PSR-4 namespace | `App\Repositories` |
| `Namespace` | Same as above (alias) | `App\Repositories` |
| `ModelName` | PascalCase model name | `Product` |
| `modelName` | camelCase model name | `product` |
| `ModelNamespace` | Namespace where Model lives | `App\Models` |

#### Model Stub Variables

| Variable | Example |
|----------|---------|
| `class` | `Product` |
| `table` | `products` |

#### Controller Stub Variables

| Variable | Example |
|----------|---------|
| `class` | `ProductApiController` |
| `model` | `Product` |
| `modelVariable` | `product` |
| `modelVariablePlural` | `products` |
| `storeRequest` | `StoreProductRequest` |
| `updateRequest` | `UpdateProductRequest` |
| `storeRequestClass` | `App\Http\Requests\StoreProductRequest` |
| `updateRequestClass` | `App\Http\Requests\UpdateProductRequest` |
| `modelClass` | `App\Models\Product` |
| `resourceName` | `products` |
| `withRelationships` | `->load(['category', 'tags'])` |
| `filterableFields` | `'name', 'status', 'search'` |
| `ServiceInterfaceUse` | `use App\Services\Contracts\ProductServiceInterface;` |
| `ServiceDependency` | `protected ProductServiceInterface $service` |
| `RequestNamespace` | `App\Http\Requests` |
| `ResourceNamespace` | `App\Http\Resources` |
| `ServiceNamespace` | `App\Services` |

#### Repository Stub Variables

| Variable | Example |
|----------|---------|
| `RepositoryName` | `ProductRepository` |
| `InterfaceName` | `ProductRepositoryInterface` |
| `InterfaceUse` | `use App\Repositories\Contracts\ProductRepositoryInterface;` |
| `InterfaceImplements` | ` implements ProductRepositoryInterface` |
| `eagerLoadRelationships` | `'category', 'tags'` |
| `filterLogic` | PHP code for applying query filters |
| `RepositoryNamespace` | `App\Repositories` |
| `RepositoryContractNamespace` | `App\Repositories\Contracts` |

**Default methods (Total Harmony signatures):**
- `getAll(array $filters = []): Collection`
- `findById(int $id): ?Model`
- `create(array $data): Model`
- `update(Model $model, array $data): Model`
- `delete(Model $model): bool`
- `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`
- `findBy(array $criteria): Collection`

#### Service Stub Variables

| Variable | Example |
|----------|---------|
| `ServiceName` | `ProductService` |
| `ServiceInterfaceName` | `ProductServiceInterface` |
| `ServiceInterfaceUse` | `use App\Services\Contracts\ProductServiceInterface;` |
| `ServiceInterfaceImplements` | ` implements ProductServiceInterface` |
| `RepositoryInterfaceUse` | `use App\Repositories\Contracts\ProductRepositoryInterface;` |
| `RepositoryDependency` | `protected ProductRepositoryInterface $repository` |
| `ServiceContractNamespace` | `App\Services\Contracts` |

**Default methods (Total Harmony signatures):**
- `getAll(array $filters = []): Collection`
- `findById(int $id): ?Model`
- `findOrFail(int $id): Model`
- `create(array $data): Model`
- `update(Model $model, array $data): Model`
- `delete(Model $model): bool`

#### Request Stub Variables

| Variable | Example |
|----------|---------|
| `class` | `StoreProductRequest` |
| `model` | `Product` |
| `modelVariable` | `product` |
| `type` | `store` or `update` |
| `validationRules` | `'name' => ['required', 'string', 'max:255']` |
| `customMessages` | `'name.required' => 'Please provide a valid name.'` |
| `customAttributes` | `'name' => 'Name'` |

#### Policy Stub Variables

| Variable | Example |
|----------|---------|
| `PolicyName` | `ProductPolicy` |

**Default methods:** `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` — all return `true`.

#### DTO Stub Variables

| Variable | Example |
|----------|---------|
| `DtoName` | `ProductData` |
| `properties` | Constructor promoted properties |
| `fromRequestBody` | `$request->input('name'),` |
| `fromModelBody` | `$model->name,` |

#### Observer Stub Variables

| Variable | Example |
|----------|---------|
| `ObserverName` | `ProductObserver` |

**Default hooks:** `creating`, `created`, `updating`, `updated`, `deleting`, `deleted`, `restored`, `forceDeleted`

#### Filter Stub Variables

| Variable | Example |
|----------|---------|
| `FilterName` | `ProductFilter` |
| `filterMethods` | Additional filter method stubs |

#### Enum Stub Variables

| Variable | Example |
|----------|---------|
| `EnumName` | `OrderStatus` |
| `cases` | `case PENDING = 'pending';` |

---

### The Total Harmony Rule

When editing stubs, you **MUST** keep method signatures synchronized across the three layers. If you add, rename, or change a method in one layer, you must update ALL matching stubs:

```
Repository Interface  <->  Repository Implementation  <->  Service (calls repository)
      |                            |                            |
Service Interface     <->  Service Implementation       <->  Controller (calls service)
```

**Example:** Adding a `findBySlug()` method requires editing **4 stubs**:

1. `repository.interface.enhanced.stub` — Add the method signature
2. `repository.enhanced.stub` — Add the implementation
3. `service.interface.enhanced.stub` — Add the method signature
4. `service.enhanced.stub` — Add the delegation call

---

### Common Template Customization Examples

#### Example 1: Add Pagination to API Controller

Edit `resources/stubs/vendor/easy-dev/controller.api.enhanced.stub` — change the `index` method to use `paginate()` instead of `getAll()`:

```php
public function index(Request $request): AnonymousResourceCollection
{
    $perPage = $request->input('per_page', 15);
    $filters = $request->except(['page', 'per_page']);
    $items = $this->service->paginate($perPage, $filters);
    return ModelResource::collection($items);
}
```

#### Example 2: Add Soft Deletes to Model

Edit `resources/stubs/vendor/easy-dev/model.stub` — add the `SoftDeletes` trait:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class MyModel extends Model
{
    use HasFactory, SoftDeletes;
}
```

#### Example 3: Add Caching to Repository

Edit `resources/stubs/vendor/easy-dev/repository.enhanced.stub` — wrap `getAll` with a cache layer:

```php
use Illuminate\Support\Facades\Cache;

public function getAll(array $filters = []): Collection
{
    $cacheKey = 'model_all_' . md5(json_encode($filters));
    return Cache::remember($cacheKey, 300, function () use ($filters) {
        $query = $this->model->query();
        // ... existing filter logic
        return $query->get();
    });
}
```

#### Example 4: Add Owner-Based Authorization to Policy

Edit `resources/stubs/vendor/easy-dev/policy.stub`:

```php
public function update(User $user, Model $model): bool
{
    return $user->id === $model->user_id;
}

public function delete(User $user, Model $model): bool
{
    return $user->id === $model->user_id || $user->hasRole('admin');
}
```

#### Example 5: Add API Rate Limiting to Controller

Edit `resources/stubs/vendor/easy-dev/controller.api.enhanced.stub` — add middleware in the constructor:

```php
public function __construct(protected ModelService $service)
{
    $this->middleware('throttle:api');
}
```

#### Example 6: Use a One-Off Custom Stub via CLI

Instead of editing published stubs globally, pass a custom stub for a single generation:

```bash
php artisan easy-dev:crud Product --stub=stubs/my-custom-controller.stub --ai
```

---

### Namespace Auto-Rewriting

When templates are generated, the package **automatically rewrites** hardcoded namespaces in stub files to match the target location. This means:

- `namespace App\Repositories;` in the stub becomes `namespace App\Modules\Sales\Infrastructure\Repositories;` when using `--module=Sales --preset=clean`
- `use App\Models\Product;` becomes `use App\Modules\Sales\Domain\Models\Product;`

You do NOT need to worry about namespaces in your custom stubs. Write them with standard `App\*` namespaces and the generator handles the translation automatically.

**Auto-translated namespace prefixes:**

| Stub Namespace | Rewritten To |
|----------------|-------------|
| `App\Models\*` | Target model namespace |
| `App\Repositories\*` | Target repository namespace |
| `App\Repositories\Contracts\*` | Target repository contract namespace |
| `App\Services\*` | Target service namespace |
| `App\Services\Contracts\*` | Target service contract namespace |
| `App\Http\Requests\*` | Target request namespace |

