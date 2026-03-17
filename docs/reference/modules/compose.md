# Compose

Framework kernel and container management.

## Classes

| Class | Role |
|-------|------|
| `AbstractKernel` | Base class for theme/plugin kernels |
| `WordPressContainer` | Trait — container lifecycle (build, cache, dump, load) |
| `TextDomain` | Trait — text domain management |
| `DependencyInjection\WordPressExtension` | Autoconfiguration + compiler passes + port bindings |
| `Configuration\FrameworkConfiguration` | Default framework parameters |

## Contracts

### `ModuleConfiguratorInterface`

Contract for module configurators used in `config/*.php` files. Implementations provide a fluent API for setting module parameters without exposing internal key names.

```php
interface ModuleConfiguratorInterface
{
    /** @return array<string, mixed> */
    public function toParameters(): array;
}
```

Implementations: `PerformanceConfigurator`, `SecurityConfigurator`.

### `RegistryInterface`

Marker interface. All registry services are automatically set as public in the DI container.

```php
interface RegistryInterface {}
```
