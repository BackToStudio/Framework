# PostType

Registers WordPress custom post types.

## Classes

| Class | Role |
|-------|------|
| `Entity\PostType` | Domain entity holding key + args |
| `PostTypeFactory` | Creates `PostType` with default args |
| `PostTypeRegistry` | Collects registered post types |
| `RegisterPostType` | Application orchestrator (hooks into `init`) |
| `Contracts\PostTypeInterface` | Interface for post type definitions |
| `Contracts\PostTypeRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressPostTypeRegistrar` | WP adapter |
| `Repository\PostRepository` | Queries posts via `WP_Query` |

## Contracts

### `PostTypeInterface`

```php
interface PostTypeInterface
{
    public function getKey(): ?string;
    public function getArgs(): array;
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
