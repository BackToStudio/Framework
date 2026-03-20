# `SchemaType`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema`
**Implements:** `JsonSerializable`

Base class for all schema.org types.

| Method | Signature | Description |
|---|---|---|
| `set` | `(string $property, mixed $value): static` | Set any property (fluent) |
| `id` | `(string $id): static` | Set the `@id` |
| `getType` | `(): string` | Return the `@type` |
| `getProperties` | `(): array<string, mixed>` | Return all properties |
| `toArray` | `(): array<string, mixed>` | Convert to JSON-LD array |
| `validate` | `(): string[]` | Return missing required properties |
| `isValid` | `(): bool` | `true` if all required properties are present |
