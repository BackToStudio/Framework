# Hooks

Central hook orchestration.

## Classes

| Class | Role |
|-------|------|
| `HookRegistry` | Collects and runs all hook services |
| `Infrastructure\WordPressHookDispatcher` | WP adapter for `add_action`/`add_filter` |

## Contracts

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

### `HookDispatcherInterface`

Abstracts the WordPress hook system.

```php
interface HookDispatcherInterface
{
    public function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;
    public function addFilter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;
    public function removeAction(string $hookName, callable $callback, int $priority = 10): void;
    public function isAdmin(): bool;
    public function registerActivationHook(string $file, callable $callback): void;
    public function registerDeactivationHook(string $file, callable $callback): void;
}
```
