# Taxonomy

Registers WordPress custom taxonomies and provides a domain model for terms.

## Contracts

### `TaxonomyInterface`

```php
interface TaxonomyInterface
{
    public function getKey(): ?string;
    public function getArgs(): array;
    public function getPostTypes(): array;
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

### `TermRepositoryInterface`

```php
interface TermRepositoryInterface
{
    public function find(int $id): ?TermInterface;
    public function findAll(array $args = []): array;
    public function query(): TermQueryBuilder;
}
```

## Specifications

Named, composable query criteria implementing `TermSpecification`.

```php
interface TermSpecification
{
    public function apply(TermQueryBuilder $builder): TermQueryBuilder;
}
```

### Available specifications

| Class | Description |
|-------|-------------|
| `TermsInTaxonomy` | Terms belonging to a specific taxonomy |
| `TopLevelTerms` | Terms with no parent (root level) |
| `NonEmptyTerms` | Terms associated with at least one post |
| `AndTermSpecification` | Combines specifications with AND logic |

## Invariants

`Taxonomy::addPostType()` validates that the post type key is not empty, throwing `\InvalidArgumentException` otherwise.

## Autoconfiguration

Classes implementing `TaxonomyInterface` are tagged `wordpress.taxonomy` and collected by `RegisterTaxonomyPass`.
