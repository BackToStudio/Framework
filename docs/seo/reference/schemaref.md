# `SchemaRef`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema`
**Implements:** `JsonSerializable`

Reference to another node in the JSON-LD graph by `@id`.

| Method | Signature | Description |
|---|---|---|
| `getId` | `(): string` | Return the identifier |
| `toArray` | `(): array{@id: string}` | `['@id' => '...']` |
