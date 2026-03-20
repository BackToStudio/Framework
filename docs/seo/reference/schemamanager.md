# `SchemaManager`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema`

Central registry for structured data. Collects `SchemaType` instances and renders them as JSON-LD.

| Method | Signature | Description |
|---|---|---|
| `add` | `(SchemaType $schema): self` | Add a schema to the registry |
| `hasSchemas` | `(): bool` | Whether any schemas are registered |
| `getSchemas` | `(): SchemaType[]` | Return all registered schemas |
| `validate` | `(): array<string, string[]>` | Validate all schemas, return errors by type |
| `toArray` | `(): array<int, array>` | Export as array of JSON-LD data |
| `render` | `(): string` | Render a `<script type="application/ld+json">` block |

Render behavior: 0 schemas returns empty string, 1 schema returns a single JSON-LD object with `@context`, 2+ schemas returns an object with `@context` and `@graph`.
