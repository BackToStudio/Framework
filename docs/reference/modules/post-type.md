# PostType

Registers WordPress custom post types and provides a domain model for posts.

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

### `PostInterface`

```php
interface PostInterface extends IdInterface, SlugInterface, ParentIdInterface
{
    public function getTitle(): string;
    public function setTitle(string $title): self;
    public function getContent(): string;
    public function setContent(string $content): self;
    public function getStatus(): PostStatus|string;
    public function setStatus(PostStatus|string $status): self;
    public function getSlug(): Slug|string;
    public function setSlug(Slug|string $slug): self;
    // ...
}
```

### `PostRepositoryInterface`

```php
interface PostRepositoryInterface
{
    public function find(int $id): ?PostInterface;
    public function findAll(array $args = []): array;
    public function findBy(array $criteria): array;
    public function findOneBy(array $criteria): ?PostInterface;
    public function query(): PostQueryBuilder;
}
```

## Value Objects

### `PostStatus`

Backed enum representing WordPress post statuses with domain logic.

```php
enum PostStatus: string
{
    case Publish = 'publish';
    case Draft = 'draft';
    case Pending = 'pending';
    case Private = 'private';
    case Trash = 'trash';
    case AutoDraft = 'auto-draft';
    case Inherit = 'inherit';
    case Future = 'future';

    public function isPublic(): bool;    // true for Publish
    public function isEditable(): bool;  // true for Draft, Pending, AutoDraft, Future
    public function isViewable(): bool;  // true for Publish, Private
}
```

## Specifications

Named, composable query criteria implementing `PostSpecification`.

```php
interface PostSpecification
{
    public function apply(PostQueryBuilder $builder): PostQueryBuilder;
}
```

### Available specifications

| Class | Constructor | Description |
|-------|-------------|-------------|
| `PublishedPosts` | — | Posts with `Publish` status |
| `RecentPosts` | `int $limit = 10` | Published posts ordered by date DESC |
| `PostsByAuthor` | `int $authorId` | Posts by a specific author |
| `PostsByStatus` | `PostStatus $status` | Posts with a given status |
| `PostsByType` | `string $postType` | Posts of a given type |
| `PostsInTaxonomy` | `string $taxonomy, int[] $termIds` | Posts in specific taxonomy terms |
| `PostsWithMeta` | `MetaKey\|string $key, mixed $value, MetaCompare` | Posts matching a meta condition |
| `AndPostSpecification` | `PostSpecification ...$specs` | Combines specifications with AND logic |

## Query Builder

`PostQueryBuilder` provides a fluent API for building `WP_Query` arguments.

```php
$posts = $repository->query()
    ->postType('article')
    ->status(PostStatus::Publish)
    ->whereMeta('featured', true, MetaCompare::EQUAL)
    ->inTaxonomyBySlugs('category', ['tech'])
    ->orderBy('date', SortDirection::DESC)
    ->limit(10)
    ->matching(new PublishedPosts())
    ->get();
```

## Autoconfiguration

Classes implementing `PostTypeInterface` are tagged `wordpress.post_type` and collected by `RegisterPostTypePass`.
