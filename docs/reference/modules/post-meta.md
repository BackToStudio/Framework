# PostMeta

Registers WordPress post meta fields.

## Classes

| Class | Role |
|-------|------|
| `Entity\PostMetaStructure` | Domain entity (fluent API for meta definition) |
| `Entity\PostMeta` | Value object for a single meta entry |
| `Entity\PostMetaType` | Enum-like class for meta types |
| `PostMetaStructureRegistry` | Collects registered meta structures |
| `RegisterPostMetaStructure` | Application orchestrator (hooks into `init`) |
| `Contracts\PostMetaStructureInterface` | Interface for meta definitions |
| `Contracts\PostMetaRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressPostMetaRegistrar` | WP adapter |
| `Repository\PostMetaRepository` | Queries post meta via `get_post_meta` |
| `Factory\PostMetaFactory` | Creates `PostMeta` from raw data |
| `Factory\PostMetaStructureFactory` | Creates `PostMetaStructure` entities |

## Contracts

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

### `PostMetaRegistrarInterface`

```php
interface PostMetaRegistrarInterface
{
    public function register(string $postType, string $metaKey, array $args): void;
}
```
