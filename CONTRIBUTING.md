# Contributing to Laravel Easy Dev

We love your input! We want to make contributing to Laravel Easy Dev as easy and transparent as possible, whether it's:

- Reporting a bug
- Discussing the current state of the code
- Submitting a fix
- Proposing new features
- Becoming a maintainer

## 🚀 Quick Start for Contributors

1. **Fork the repository**
2. **Clone your fork**
   ```bash
   git clone https://github.com/your-username/laravel-easy-dev.git
   cd laravel-easy-dev
   ```

3. **Install dependencies**
   ```bash
   composer install
   ```

4. **Create a feature branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```

5. **Make your changes**
6. **Run tests**
   ```bash
   composer test
   ```

7. **Submit a pull request**

## 🐛 Bug Reports

We use GitHub issues to track public bugs. Report a bug by [opening a new issue](https://github.com/anasnashat/laravel-easy-dev/issues/new); it's that easy!

**Great Bug Reports** tend to have:

- A quick summary and/or background
- Steps to reproduce
  - Be specific!
  - Give sample code if you can
- What you expected would happen
- What actually happens
- Notes (possibly including why you think this might be happening, or stuff you tried that didn't work)

### Bug Report Template

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Run command '...'
2. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Environment:**
 - OS: [e.g. Windows, macOS, Linux]
 - PHP Version: [e.g. 8.1, 8.2]
 - Laravel Version: [e.g. 10.0, 11.0]
 - Package Version: [e.g. 2.0.0]

**Additional context**
Add any other context about the problem here.
```

## 💡 Feature Requests

We welcome feature requests! Please provide:

- **Clear description** of the feature
- **Use case** - why is this feature needed?
- **Proposed implementation** (if you have ideas)
- **Alternatives considered**

### Feature Request Template

```markdown
**Is your feature request related to a problem?**
A clear and concise description of what the problem is.

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions.

**Additional context**
Add any other context or screenshots about the feature request here.
```

## 🔧 Development Guidelines

### Code Style

We use Laravel's coding standards:

- PSR-12 coding standard
- Use Laravel Pint for code formatting
- Follow Laravel naming conventions

**Run code formatting:**
```bash
./vendor/bin/pint
```

### Testing

- Write tests for new features
- Ensure all tests pass before submitting PR
- Aim for high test coverage

**Run tests:**
```bash
composer test
```

**Run specific test:**
```bash
./vendor/bin/pest tests/Unit/SpecificTest.php
```

### Commit Messages

Use conventional commit format:

```
type(scope): description

[optional body]

[optional footer]
```

**Types:**
- `feat`: new feature
- `fix`: bug fix
- `docs`: documentation changes
- `style`: formatting, missing semi colons, etc
- `refactor`: code refactoring
- `test`: adding tests
- `chore`: maintenance

**Examples:**
```bash
feat(crud): add interactive mode for CRUD generation
fix(parser): resolve migration parsing issue for SQLite
docs(readme): update installation instructions
```

## 📁 Project Structure

```
laravel-easy-dev/
├── src/
│   ├── Commands/           # Artisan commands
│   ├── Services/           # Business logic services
│   ├── Parsers/           # Database and file parsers
│   ├── Exceptions/        # Custom exceptions
│   └── Providers/         # Service providers
├── stubs/                 # Template files
├── config/               # Configuration files
├── tests/                # Test suite
│   ├── Unit/            # Unit tests
│   ├── Feature/         # Feature tests
│   └── Fixtures/        # Test fixtures
└── docs/                # Additional documentation
```

## 🧪 Testing Guidelines

### Test Types

1. **Unit Tests** - Test individual classes/methods
2. **Feature Tests** - Test complete workflows
3. **Integration Tests** - Test database interactions

### Writing Tests

```php
<?php

namespace AnasNashat\EasyDev\Tests\Unit;

use AnasNashat\EasyDev\Tests\TestCase;

class ExampleTest extends TestCase
{
    /** @test */
    public function it_can_do_something()
    {
        // Arrange
        $input = 'test-input';
        
        // Act
        $result = $this->service->doSomething($input);
        
        // Assert
        $this->assertEquals('expected-output', $result);
    }
}
```

### Test Database

Tests use SQLite in-memory database for speed:

```php
// In TestCase.php
protected function getEnvironmentSetUp($app)
{
    $app['config']->set('database.default', 'testing');
    $app['config']->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
}
```

## 📝 Documentation

### Code Documentation

- Use PHPDoc blocks for all public methods
- Include parameter types and return types
- Add meaningful descriptions

```php
/**
 * Generate a CRUD for the given model.
 *
 * @param string $modelName The name of the model
 * @param array $options Generation options
 * @return bool True if successful, false otherwise
 * @throws \InvalidArgumentException When model name is invalid
 */
public function generateCrud(string $modelName, array $options = []): bool
{
    // Implementation
}
```

### README Updates

When adding features, update:
- Feature list
- Usage examples
- Configuration options
- Command documentation

## 🎯 Pull Request Process

1. **Check existing issues/PRs** to avoid duplicates
2. **Create descriptive PR title** following conventional commit format
3. **Fill out PR template** completely
4. **Add/update tests** for your changes
5. **Update documentation** if needed
6. **Ensure CI passes** before requesting review

### PR Template

```markdown
## Description
Brief description of changes.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added/updated
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or clearly documented)
```

## 🏷️ Release Process

1. Update `CHANGELOG.md`
2. Update version in `composer.json`
3. Create release tag
4. Publish to Packagist

## ❓ Questions?

Feel free to:
- Open a [discussion](https://github.com/anasnashat/laravel-easy-dev/discussions)
- Create an [issue](https://github.com/anasnashat/laravel-easy-dev/issues)
- Contact maintainers directly

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing to Laravel Easy Dev!** 🎉
