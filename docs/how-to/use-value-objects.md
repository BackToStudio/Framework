# How to use Value Objects

Value Objects replace primitive types with validated, domain-specific types. The framework provides three Value Objects: `PostStatus`, `Slug`, and `MetaKey`.

## PostStatus

`PostStatus` is a backed enum that replaces raw status strings.

```php
use BackTo\Framework\PostType\Entity\PostStatus;

// From a string (e.g., from user input or database)
$status = PostStatus::from('publish');      // PostStatus::Publish
$status = PostStatus::tryFrom('unknown');   // null (no exception)

// Domain logic
$status->isPublic();    // true for Publish
$status->isEditable();  // true for Draft, Pending, AutoDraft, Future
$status->isViewable();  // true for Publish, Private

// Use in queries
$repository->query()->status(PostStatus::Publish)->get();
```

Setters accept both `PostStatus` and `string` for backward compatibility:

```php
$post->setStatus(PostStatus::Draft);   // Value Object
$post->setStatus('draft');             // still works
```

## Slug

`Slug` validates that a slug contains no spaces.

```php
use BackTo\Framework\Compose\ValueObject\Slug;

$slug = Slug::fromString('mon-article');
$slug->value;      // 'mon-article'
$slug->isEmpty();  // false
$slug->equals(Slug::fromString('mon-article')); // true

// Validation
Slug::fromString('has spaces'); // throws InvalidArgumentException

// Use on entities
$post->setSlug(Slug::fromString('mon-article'));
$post->setSlug('mon-article'); // still works (backward compatible)
```

## MetaKey

`MetaKey` validates that a meta key is not empty and detects protected keys.

```php
use BackTo\Framework\PostMeta\ValueObject\MetaKey;

$key = MetaKey::fromString('_thumbnail_id');
$key->isProtected(); // true (starts with _)
$key->value;          // '_thumbnail_id'

$key = MetaKey::fromString('custom_field');
$key->isProtected(); // false

// Validation
MetaKey::fromString(''); // throws InvalidArgumentException
```

## Creating your own Value Object

Follow the same pattern: `final readonly class`, constructor validation, `Stringable` interface.

```php
final readonly class Email implements \Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email address: "%s".', $value)
            );
        }
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

## Backward compatibility

All entity setters accept union types (`PostStatus|string`, `Slug|string`, `MetaKey|string`). Existing code using raw strings continues to work without changes.
