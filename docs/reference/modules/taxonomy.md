# Taxonomy

Registers WordPress custom taxonomies.

## Classes

| Class | Role |
|-------|------|
| `Entity\Taxonomy` | Domain entity holding key + args + post types |
| `TaxonomyFactory` | Creates `Taxonomy` with default args |
| `TaxonomyRegistry` | Collects registered taxonomies |
| `RegisterTaxonomy` | Application orchestrator (hooks into `init`) |
| `Contracts\TaxonomyInterface` | Interface for taxonomy definitions |
| `Contracts\TaxonomyRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressTaxonomyRegistrar` | WP adapter |
| `Repository\TermRepository` | Queries terms via `get_terms` |

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
