# Contributing to BackTo Framework

Thank you for your interest in contributing! This guide covers everything you need to get started.

## Development Setup

```bash
# Clone the repository
git clone https://github.com/BackToStudio/Framework.git
cd Framework

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer phpstan
```

### Requirements

- PHP 8.2+
- Composer 2.x
- WordPress stubs (installed via dev dependencies)

## Code Standards

### Architecture

The framework follows **Clean Architecture** (Ports & Adapters):

- **Contracts/** — Port interfaces (domain boundaries)
- **Infrastructure/** — WordPress adapters (implementations)
- **Entity/** — Domain entities
- **DependencyInjection/** — Compiler passes and DI config
- **Tests/** — PHPUnit tests

### PHP Standards

- **Strict types**: Every file must declare `strict_types=1`
- **PHPStan Level 8**: All code must pass the strictest analysis level
- **PSR-4 autoloading**: Namespace matches directory structure
- **Type declarations**: All parameters and return types must be typed
- **PHPDoc**: Only add `@param`/`@return` when they provide information beyond the type declaration (e.g., generic types like `array<string, mixed>`)

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Interface | `*Interface` suffix | `PostTypeInterface` |
| Registrar port | `*RegistrarInterface` | `PostTypeRegistrarInterface` |
| WP adapter | `WordPress*` prefix | `WordPressPostTypeRegistrar` |
| Compiler pass | `Register*Pass` | `RegisterPostTypePass` |
| Registry | `*Registry` | `PostTypeRegistry` |
| Orchestrator | `Register*` | `RegisterPostType` |

### Module Pattern

Every module follows this structure:

```
ModuleName/
  Contracts/          # Port interfaces
  Infrastructure/     # WordPress adapters
  Entity/             # Domain entities (if applicable)
  DependencyInjection/
    Compiler/         # Compiler passes
  Tests/              # PHPUnit tests
  *Registry.php       # Service collection
  Register*.php       # Hook orchestrator
```

## Pull Request Process

1. **Create a branch** from `main` with a descriptive name
2. **Make your changes** following the code standards above
3. **Write tests** for new functionality
4. **Run the full validation suite**:
   ```bash
   composer test
   composer phpstan
   ```
5. **Commit** with a clear message following [Conventional Commits](https://www.conventionalcommits.org/):
   - `feat:` new feature
   - `fix:` bug fix
   - `docs:` documentation only
   - `refactor:` code change that neither fixes a bug nor adds a feature
   - `test:` adding or correcting tests
   - `security:` security fix
6. **Open a PR** against `main`

### PR Checklist

- [ ] Tests pass (`composer test`)
- [ ] PHPStan passes (`composer phpstan`)
- [ ] New code has tests
- [ ] Documentation updated if applicable
- [ ] No breaking changes (or documented in PR description)

## Testing

Tests live in `Tests/` directories within each module:

```bash
# Run all tests
vendor/bin/phpunit

# Run a specific module's tests
vendor/bin/phpunit src/PostType/Tests/

# Run a specific test
vendor/bin/phpunit --filter testRegistersPostType
```

## Vendor Scoping

Symfony dependencies are scoped with `BackToVendor\` prefix via php-scoper to avoid conflicts with other plugins. See [Vendor Scoping documentation](docs/explanation/vendor-scoping.md).

When adding or updating Symfony dependencies, run:

```bash
composer prefix-vendor
```

## Reporting Issues

- Use GitHub Issues for bug reports and feature requests
- Include PHP version, WordPress version, and steps to reproduce
- For security vulnerabilities, email florian@truchot.co directly

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.
