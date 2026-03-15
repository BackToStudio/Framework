# Contracts and Interfaces Reference

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

## Entity contracts

### `PostTypeInterface`

```php
interface PostTypeInterface
{
    public function getKey(): ?string;
    public function getArgs(): array;
}
```

### `TaxonomyInterface`

```php
interface TaxonomyInterface
{
    public function getKey(): ?string;
    public function getArgs(): array;
    public function getPostTypes(): array;
}
```

### `BlockInterface`

```php
interface BlockInterface
{
    public function getName(): string;
}
```

### `BlockStyleInterface`

```php
interface BlockStyleInterface
{
    public function getProperties(): array;
    public function getLabel(): string;
    public function getStyleName(): string;
    public function getBlocks(): array;
}
```

### `PostMetaStructureInterface`

Fluent interface for defining meta field structure. Key methods:

| Method | Returns |
|--------|---------|
| `getObjectType()` / `setObjectType()` | `string` / `self` |
| `getMetaKey()` / `setMetaKey()` | `string` / `self` |
| `getType()` / `setType()` | `string` / `self` |
| `getLabel()` / `setLabel()` | `string` / `self` |
| `getDescription()` / `setDescription()` | `string` / `self` |
| `isSingle()` / `setSingle()` | `bool` / `self` |
| `isShowInRest()` / `showInRest()` / `dontShowInRest()` | `bool` / `self` |
| `isRevisionsEnabled()` / `setRevisionsEnabled()` | `bool` / `self` |
| `getDefault()` / `setDefault()` | `mixed` / `self` |
| `getSanitizeCallback()` / `setSanitizeCallback()` | `?callable` / `self` |
| `getAuthCallback()` / `setAuthCallback()` | `?callable` / `self` |

## Port interfaces (Clean Architecture)

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

### `PostTypeRegistrarInterface`

```php
interface PostTypeRegistrarInterface
{
    public function register(string $key, array $args): void;
    public function exists(string $key): bool;
    public function flushRewriteRules(): void;
}
```

### `TaxonomyRegistrarInterface`

```php
interface TaxonomyRegistrarInterface
{
    public function register(string $key, $objectType, array $args): void;
    public function exists(string $key): bool;
    public function flushRewriteRules(): void;
}
```

### `BlockStyleRegistrarInterface`

```php
interface BlockStyleRegistrarInterface
{
    public function register(string $blockName, array $styleProperties): void;
}
```

### `PostMetaRegistrarInterface`

```php
interface PostMetaRegistrarInterface
{
    public function register(string $postType, string $metaKey, array $args): void;
}
```

### `FileLocatorInterface`

```php
interface FileLocatorInterface
{
    public function getAttachedFile(int $attachmentId): string;
    public function getUploadDir(): array; // {basedir, baseurl}
}
```

## Cache contracts

### `CacheInterface`

PSR-16 SimpleCache compatible. Methods: `get`, `set`, `delete`, `clear`, `has`, `getMultiple`, `setMultiple`, `deleteMultiple`.

See [PSR-16 specification](https://www.php-fig.org/psr/psr-16/).

## SEO contracts

### `SeoProviderInterface` extends `SocialLinksProviderInterface`, `MetaProviderInterface`

```php
interface SeoProviderInterface
{
    public function getName(): string;
    public function isActive(): bool;
    // + all social links + meta methods
}
```

### `SocialLinksProviderInterface`

Methods: `getFacebookUrl`, `getTwitterUrl`, `getInstagramUrl`, `getLinkedInUrl`, `getPinterestUrl`, `getYouTubeUrl`, `getSocialLinks`.

### `MetaProviderInterface`

Methods: `getTitle`, `getDescription`, `getCanonicalUrl`, `getOgTitle`, `getOgDescription`, `getOgImageUrl` (all with optional `?int $postId`).

## Registry contracts

### `RegistryInterface`

Marker interface. All registry services are automatically set as public in the DI container.

```php
interface RegistryInterface {}
```
