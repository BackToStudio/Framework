# PostMeta

Registers WordPress post meta fields and provides a domain model for post metadata.

## Contracts

### `PostMetaStructureInterface`

Fluent interface for defining meta field structure.

| Method | Returns |
|--------|---------|
| `getObjectType()` / `setObjectType()` | `string` / `self` |
| `getMetaKey()` / `setMetaKey()` | `MetaKey\|string` / `self` |
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

### `PostMetaRepositoryInterface`

```php
interface PostMetaRepositoryInterface
{
    public function create(PostMetaInterface $postMeta): PostMetaInterface;
    public function get(int $postId, MetaKey|string $metaKey, bool $single = true): PostMetaInterface;
    public function update(PostMetaInterface $postMeta): int|bool;
    public function delete(PostMetaInterface $postMeta): bool;
}
```

### `PostReferenceInterface`

Anti-corruption layer: PostMeta's minimal view of a Post. Avoids a direct dependency on the PostType bounded context.

```php
interface PostReferenceInterface
{
    public function getId(): ?int;
}
```

## Value Objects

### `MetaKey`

Immutable, validated meta key.

```php
final readonly class MetaKey implements Stringable
{
    public string $value;

    public function __construct(string $value);           // throws if empty
    public static function fromString(string $value): self;
    public function isProtected(): bool;                  // true if starts with _
    public function equals(self $other): bool;
    public function __toString(): string;
}
```

## Autoconfiguration

Classes implementing `PostMetaStructureInterface` are tagged `wordpress.post_meta` and collected by `RegisterPostMetaStructurePass`.
