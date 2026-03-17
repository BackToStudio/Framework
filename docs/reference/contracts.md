# Contracts and Interfaces Reference

This page documents shared, cross-cutting contracts. Module-specific contracts are documented in their respective [module reference pages](modules.md).

## Hook contracts

### `HookInterface`

Marker interface. All hook services must implement this.

```php
interface HookInterface {}
```

### `Hooks` extends `HookInterface`

Front-end hooks (always executed).

```php
interface Hooks extends HookInterface
{
    public function hooks(): void;
}
```

### `AdminHooks` extends `HookInterface`

Admin-only hooks (executed when `is_admin()` is true).

```php
interface AdminHooks extends HookInterface
{
    public function hooks(): void;
}
```

### `ActivationHooks` extends `HookInterface`

Plugin activation callback. Only effective when a plugin file is set on the `HookRegistry`.

```php
interface ActivationHooks extends HookInterface
{
    public function activate();
}
```

### `DeactivationHooks` extends `HookInterface`

Plugin deactivation callback.

```php
interface DeactivationHooks extends HookInterface
{
    public function deactivate();
}
```

## Configuration contracts

### `ModuleConfiguratorInterface`

Contract for module configurators used in `config/*.php` files. Implementations provide a fluent API for setting module parameters without exposing internal key names.

```php
interface ModuleConfiguratorInterface
{
    /** @return array<string, mixed> */
    public function toParameters(): array;
}
```

Implementations: [`AssetsConfigurator`](modules/assets.md#configuration), [`CacheConfigurator`](modules/cache.md#configuration), [`ObservabilityConfigurator`](modules/observability.md#configuration), [`PerformanceConfigurator`](modules/performance.md#configuration), [`RestApiConfigurator`](modules/rest-api.md#configuration), [`SecurityConfigurator`](modules/security.md#configuration), [`SeoConfigurator`](modules/seo.md#configuration).

## Registry contracts

### `RegistryInterface`

Marker interface. All registry services are automatically set as public in the DI container.

```php
interface RegistryInterface {}
```
