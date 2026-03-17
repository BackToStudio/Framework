# PostMeta

Registers WordPress post meta fields.

## Contracts

### `PostMetaStructureInterface`

Fluent interface for defining meta field structure.

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
