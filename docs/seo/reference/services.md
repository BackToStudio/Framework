# Services

### `SeoManager`

**Namespace:** `BackTo\Framework\Bundle\Seo`

Resolves the active SEO provider from registered providers.

| Method | Signature | Description |
|---|---|---|
| `addProvider` | `(SeoProviderInterface $provider): self` | Add a provider |
| `getProvider` | `(): ?SeoProviderInterface` | Return the first active provider |
| `hasProvider` | `(): bool` | Whether an SEO plugin is active |
| `getSocialLinks` | `(): array<string, string\|null>` | Social links from the active provider |

### `SeoConfig`

**Namespace:** `BackTo\Framework\Bundle\Seo`

Loads configuration from the theme's `config/seo.php`.

| Method | Signature | Description |
|---|---|---|
| `all` | `(): array` | Full configuration array |
| `get` | `(string $key, mixed $default = null): mixed` | Value by dot notation |
| `getPostTypeMap` | `(): array<string, class-string>` | Post type to generator mapping |
| `shouldDisablePluginSchema` | `(): bool` | Whether to disable plugin schema (default: `true`) |

### `SeoConfigurator`

**Namespace:** `BackTo\Framework\Bundle\Seo`

Fluent configurator for SEO container parameters.

| Method | Signature | Description |
|---|---|---|
| `titleSeparator` | `(string $separator): self` | Title separator (default: `\|`) |
| `robotsDefault` | `(string $robots): self` | Default robots directive (default: `index, follow`) |
| `toParameters` | `(): array<string, mixed>` | Export as container parameters |

### `PostTypeSchemaResolver`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema\Generator`

Resolves a schema generator for a given post type based on the `schema.post_type_map` configuration.

| Method | Signature | Description |
|---|---|---|
| `addGenerator` | `(string $postType, object $generator): self` | Register a generator |
| `supports` | `(string $postType): bool` | Whether a generator exists |
| `resolve` | `(string $postType, ?int $postId = null): ?SchemaType` | Generate the schema |
| `getGenerators` | `(): array<string, object>` | All registered generators |
