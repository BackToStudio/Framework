# CLAUDE.md — BackTo Framework Development Guidelines

## Architecture

This is a PHP 8.2+ WordPress framework using hexagonal architecture (ports & adapters), Symfony DI container (vendor-scoped under `BackToVendor\`), and DDD principles.

### Module Structure

```
ModuleName/
├── Contracts/           # Port interfaces (abstraction layer)
├── Infrastructure/      # WordPress/external adapters
├── DependencyInjection/Compiler/  # Compiler passes
├── Entity/              # Value objects, data models
├── Tests/               # PHPUnit tests
└── ModuleExtension.php  # Extension registration
```

### Key Patterns

- **Extensions** implement `ExtensionInterface` and are registered in `WordPressContainer::getExtensions()`
- **Compiler passes** extend `AbstractTaggedServiceCompilerPass` for service collection
- **Port bindings**: register interface → implementation in `register()`, then alias
- **Autoconfiguration**: tag services by interface (e.g. `RestRouteInterface` → `wordpress.rest_route`)

## Validation Rules

**Every user-facing input must be validated before use.** This applies to:

### REST API Routes
- Routes accepting parameters MUST implement `ValidatedRestRouteInterface` and declare `rules()` returning constraint arrays
- Validation runs automatically before the handler — invalid requests get a 400 response
- Use the built-in constraints: `NotBlank`, `NotNull`, `Email`, `Length`, `Regex`, `Choice`, `Type`, `Range`, `Url`, `Count`, `Unique`, `Each`, `Callback`

### Tracking Scripts / JavaScript Output
- Any value interpolated into JavaScript MUST be validated in the constructor with a regex matching the expected format
- Example: Google Analytics ID → `/^[A-Z0-9]+-[A-Z0-9]+$/i`

### Configurators
- Numeric parameters must have bounds (no negative TTLs, ports in 1-65535, etc.)
- String parameters with a finite set of valid values must use allowlists
- Invalid config must throw `\InvalidArgumentException` immediately (fail-fast)

### Cookie / External Data
- Data decoded from cookies or external sources must be structurally validated (type-check each value, not just `is_array`)

### Database
- Always use `$db->prepare()` with `%s`/`%d` placeholders — never interpolate variables into SQL strings

## Testing

- Tests live in `src/ModuleName/Tests/` alongside the module code
- New test suites must be registered in `phpunit.xml`
- Run tests: `./vendor/bin/phpunit` (full) or `./vendor/bin/phpunit --testsuite <name>`
