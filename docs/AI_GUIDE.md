# AI Guide

This guide helps AI coding agents such as Codex, Cursor, Claude, and ChatGPT use Laravel Easy Dev safely and efficiently inside Laravel projects.

Laravel Easy Dev is useful for AI because it gives agents structured project context and repeatable generation commands. That reduces manual file discovery, boilerplate writing, and token-heavy guessing.

## Install

```bash
composer require anas/easy-dev:^3.1 --dev
```

## Best AI Workflow

Start by collecting structured context:

```bash
php artisan easy-dev:ai-context --pretty
php artisan easy-dev:snapshot --ai
php artisan easy-dev:analyze --json
```

Then generate only what the task needs:

```bash
php artisan easy-dev:crud Product --api --with-service --tests --swagger
```

Finally, verify the generated code:

```bash
php artisan route:list --path=products
php artisan test
```

## When To Use Each Command

| Goal | Command |
| --- | --- |
| Understand the Laravel app quickly | `easy-dev:ai-context --pretty` |
| Inspect models, tables, and relationships | `easy-dev:snapshot --ai` |
| Find missing layers or structure issues | `easy-dev:analyze --json` |
| Generate a full feature | `easy-dev:crud ModelName ...` |
| Generate tests only | `easy-dev:test ModelName ...` |
| Generate OpenAPI docs | `easy-dev:swagger ModelName` |
| Publish customizable stubs | `easy-dev:publish-stubs` |
| List stubs for automation | `easy-dev:publish-stubs --list --ai` |

## Token-Saving Pattern

For AI agents, prefer structured command output before reading many files manually.

Recommended order:

1. Run `php artisan easy-dev:ai-context --pretty`.
2. Run `php artisan easy-dev:snapshot --ai` when database/model context matters.
3. Run `php artisan easy-dev:analyze --json` before deciding what to generate.
4. Generate code with explicit flags.
5. Read only the generated files and the nearby files they depend on.
6. Run tests.

This can save thousands of tokens on medium and large Laravel projects because the agent gets a compact map before opening many controllers, models, migrations, requests, and routes.

## Safe Generation Rules

AI agents should follow these rules:

- Use `--dry-run` first when the user wants a preview.
- Use explicit flags instead of relying on interactive prompts.
- Use `--api` for API-first features.
- Use `--with-service` only when business logic needs a separate layer.
- Use `--with-repository` only when the project or team uses repositories.
- Use `--module=Name` only when the project already uses modules or the user asks for modular structure.
- Review generated files before editing unrelated project code.
- Run `php artisan test` after generation.

## Recommended Generation Recipes

### API Feature

```bash
php artisan easy-dev:crud Product --api --with-service --tests --swagger
```

### Full Backend Feature

```bash
php artisan easy-dev:crud Product --api --with-repository --with-service --with-policy --with-dto --tests --swagger
```

### Modular Feature

```bash
php artisan easy-dev:crud Invoice --module=Billing --architecture=clean --with-service --with-repository --tests
```

### Tests For Existing Feature

```bash
php artisan easy-dev:test Product --api --feature --unit --service --repository
```

### AI Analysis Before Refactor

```bash
php artisan easy-dev:analyze --json
php artisan easy-dev:ai-context --pretty
```

## Prompt Examples For AI Agents

### Generate A Feature

```text
Use Laravel Easy Dev to generate an API feature for Product with service layer, tests, and OpenAPI docs. Then inspect the generated files and run the test suite.
```

Recommended command:

```bash
php artisan easy-dev:crud Product --api --with-service --tests --swagger
```

### Analyze A Project

```text
Use Laravel Easy Dev AI commands to inspect this Laravel project. Summarize missing layers, risky structure, routes, models, and recommended next changes.
```

Recommended commands:

```bash
php artisan easy-dev:ai-context --pretty
php artisan easy-dev:snapshot --ai
php artisan easy-dev:analyze --json
```

### Work With Existing Stubs

```text
List Laravel Easy Dev stubs, publish them if needed, and adjust only the relevant stub for the requested generation style.
```

Recommended commands:

```bash
php artisan easy-dev:publish-stubs --list --ai
php artisan easy-dev:publish-stubs
```

## Laravel 11, 12, And 13 API Routing Note

If generated API routes do not appear, confirm `routes/api.php` is loaded in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

Then check routes again:

```bash
php artisan route:list --path=products
```

## What AI Should Not Do

- Do not rename the Composer package.
- Do not assume Repository and Service layers are required.
- Do not generate module structure unless the project needs modules.
- Do not edit published stubs blindly across the whole project.
- Do not skip validation after generating files.

## Minimal AI Context Block

When an AI agent needs a short package summary, use this:

```text
Laravel Easy Dev generates Laravel CRUD, APIs, requests, resources, optional services/repositories, policies, DTOs, observers, tests, OpenAPI docs, modules, frontend starter stubs, and AI-ready project context. Prefer explicit commands such as `php artisan easy-dev:crud Product --api --with-service --tests --swagger`. Use `easy-dev:ai-context --pretty`, `easy-dev:snapshot --ai`, and `easy-dev:analyze --json` before large changes.
```
