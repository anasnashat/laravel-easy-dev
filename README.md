# 🚀 Laravel Easy Dev v2

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anas/easy-dev.svg?style=flat-square)](https://packagist.org/packages/anas/easy-dev)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/anasnashat/laravel-easy-dev/tests.yml?branch=v2-development&label=tests&style=flat-square)](https://github.com/anasnashat/laravel-easy-dev/actions)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/anasnashat/laravel-easy-dev/pint.yml?branch=v2-development&label=code%20style&style=flat-square)](https://github.com/anasnashat/laravel-easy-dev/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/anas/easy-dev.svg?style=flat-square)](https://packagist.org/packages/anas/easy-dev)

**Laravel Easy Dev v2** is a powerful package that supercharges your Laravel development workflow with beautiful UI, interactive CRUD generation, Repository & Service patterns, and intelligent relationship detection.

## ✨ Features


- 🚀 **Enhanced CRUD Generation** - Interactive CRUD with Repository and Service patterns
- 🎯 **Beautiful UI** - Stunning command-line interface with progress bars and colors
- 🔄 **Auto Relationship Detection** - Intelligent schema analysis and relationship generation
- 🏗️ **Clean Architecture** - Repository and Service layer with interfaces
- 📝 **Smart Form Requests** - Intelligent validation rules with custom error messages
- 🌐 **API & Web Support** - Generate controllers for both API and web routes
- 🎨 **Customizable Templates** - Flexible stub templates for all generated files
- 🧪 **Test Generation** - Generate comprehensive feature and unit tests
- 📚 **Comprehensive Documentation** - Built-in help system with examples
- 🎮 **Interactive Mode** - Step-by-step guided setup wizard

## 📦 Installation

Install the package via Composer:

```bash
composer require anas/easy-dev --dev
```

The package will automatically register itself via Laravel's package discovery.

## ⚙️ Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=easy-dev-config
```

Publish the stub files (optional):

```bash
php artisan vendor:publish --tag=easy-dev-stubs
```

## 🚀 Quick Start

Get started in seconds with the interactive mode:

```bash
# Interactive CRUD generation
php artisan easy-dev:make Product --interactive

# Quick CRUD with Repository and Service
php artisan easy-dev:crud Order --with-repository --with-service

# Auto-detect all relationships
php artisan easy-dev:sync-relations --all
```

## 📖 Documentation

- **[📚 Complete Documentation](docs/COMPLETE_DOCUMENTATION.md)** - Full guide with examples
- **[⚡ Quick Start Guide](docs/QUICK_START.md)** - Get up and running fast
- **[🔧 Command Reference](docs/COMMAND_REFERENCE.md)** - All commands and options
- **[🔗 Relationship Detection](docs/RELATIONSHIP_DETECTION.md)** - Auto-relationship system
- **[⚙️ Configuration Guide](docs/CONFIGURATION.md)** - Customize everything
- **[🌐 API Development](docs/API_DEVELOPMENT.md)** - API-first development
- **[💡 Examples & Use Cases](docs/EXAMPLES_USE_CASES.md)** - Real-world examples

## 🎯 Core Commands

### Enhanced CRUD Generation

```bash
# Interactive mode with guided setup
php artisan easy-dev:make Product --interactive

# Generate with Repository and Service patterns
php artisan easy-dev:crud Order --with-repository --with-service

# API-only generation
php artisan easy-dev:make User --api-only --with-service
```

### Relationship Management

```bash
# Auto-detect all relationships
php artisan easy-dev:sync-relations --all

# Sync specific model
php artisan easy-dev:sync-relations User

# Add custom relationship
php artisan easy-dev:add-relation User hasMany Post
```

### Utility Commands

```bash
# Generate API resources
php artisan easy-dev:api-resource Product

# Generate repository pattern
php artisan easy-dev:repository Order

# Beautiful help guide
php artisan easy-dev:help

# UI demonstration
php artisan easy-dev:demo-ui
```

## 🎨 Beautiful UI

Experience the stunning command-line interface:

```bash
╭─────────────────────────────────────────────────────────────╮
│                                                             │
│   🚀 Laravel Easy Dev CRUD Generator 🚀                │
│                                                             │
│   Generate complete CRUD with Repository & Service patterns   │
│                                                             │
╰─────────────────────────────────────────────────────────────╯

 10/10 [============================] 100% ✨ Finalizing...
```

## 🏗️ Generated Architecture

Laravel Easy Dev v2 creates a clean, maintainable architecture:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── ProductController.php         # Web controller
│   │   └── Api/
│   │       └── ProductApiController.php  # API controller
│   ├── Requests/
│   │   ├── StoreProductRequest.php       # Validation
│   │   └── UpdateProductRequest.php      # Validation
│   └── Resources/
│       ├── ProductResource.php           # API resource
│       └── ProductCollection.php         # API collection
├── Models/
│   └── Product.php                       # Enhanced model
├── Repositories/
│   ├── ProductRepository.php             # Implementation
│   └── Contracts/
│       └── ProductRepositoryInterface.php # Interface
└── Services/
    ├── ProductService.php                # Implementation
    └── Contracts/
        └── ProductServiceInterface.php   # Interface
```

## 🔄 Intelligent Relationship Detection

Automatically detects and generates:

- **Foreign Key Relationships** - `belongsTo` and `hasMany`
- **Pivot Table Relationships** - `belongsToMany`
- **Polymorphic Relationships** - `morphTo`, `morphOne`, `morphMany`
- **Self-Referencing Relationships** - Parent/child hierarchies


## 📋 Requirements

- **PHP**: 8.1+
- **Laravel**: 9.0+ | 10.0+ | 11.0+ | 12.0+
- **Database**: MySQL, PostgreSQL, or SQLite

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 👨‍💻 Credits

- [Anas Nashaat](https://github.com/anasnashat)
- [All Contributors](https://github.com/anasnashat/laravel-easy-dev/contributors)

## 💬 Support & Community

- **📖 Documentation**: [GitHub Wiki](https://github.com/anasnashat/laravel-easy-dev/wiki)
- **🐛 Issues**: [GitHub Issues](https://github.com/anasnashat/laravel-easy-dev/issues)
- **💬 Discussions**: [GitHub Discussions](https://github.com/anasnashat/laravel-easy-dev/discussions)
- **⭐ Star us**: [GitHub Repository](https://github.com/anasnashat/laravel-easy-dev)

---

<div align="center">

**Made with ❤️ for the Laravel community**

[⭐ Star us on GitHub](https://github.com/anasnashat/laravel-easy-dev) • [📖 Read the Docs](https://github.com/anasnashat/laravel-easy-dev/wiki) • [🐛 Report Issues](https://github.com/anasnashat/laravel-easy-dev/issues)

</div>
