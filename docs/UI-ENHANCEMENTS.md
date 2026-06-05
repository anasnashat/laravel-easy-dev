# CLI Experience

Laravel Easy Dev includes an interactive command experience for developers who prefer guided setup.

## Interactive Wizard

```bash
php artisan easy-dev:make Product --interactive
```

Use the wizard when you want to choose options step by step.

## Script-Friendly Commands

Use `easy-dev:crud` for repeatable commands, automation, and AI coding agents:

```bash
php artisan easy-dev:crud Product --api --with-service --tests --swagger
```

## Help

```bash
php artisan easy-dev:help
php artisan easy-dev:help --examples
```

## Demo

```bash
php artisan easy-dev:demo-ui
```

## AI-Friendly Mode

Many commands support machine-readable output:

```bash
php artisan easy-dev:crud Product --api --ai
php artisan easy-dev:publish-stubs --list --ai
php artisan easy-dev:snapshot --ai
```
