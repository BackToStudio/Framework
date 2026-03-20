# Interfaces

### `SeoProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`
**Extends:** `SocialLinksProviderInterface`, `MetaProviderInterface`

| Method | Signature | Description |
|---|---|---|
| `getName` | `(): string` | Plugin identifier (`'yoast'`, `'seopress'`) |
| `isActive` | `(): bool` | Whether the SEO plugin is active |

### `MetaProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `getTitle` | `(?int $postId = null): ?string` |
| `getDescription` | `(?int $postId = null): ?string` |
| `getCanonicalUrl` | `(?int $postId = null): ?string` |
| `getOgTitle` | `(?int $postId = null): ?string` |
| `getOgDescription` | `(?int $postId = null): ?string` |
| `getOgImageUrl` | `(?int $postId = null): ?string` |

### `SocialLinksProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `getFacebookUrl` | `(): ?string` |
| `getTwitterUrl` | `(): ?string` |
| `getInstagramUrl` | `(): ?string` |
| `getLinkedInUrl` | `(): ?string` |
| `getPinterestUrl` | `(): ?string` |
| `getYouTubeUrl` | `(): ?string` |
| `getSocialLinks` | `(): array<string, string\|null>` |

### `SchemaProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `registerSchemas` | `(SchemaManager $schemaManager): void` |

### `BreadcrumbSchemaGeneratorInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `generate` | `(): ?SchemaType` |
