# The JSON-LD @graph pattern

### Why a graph?

Schema.org entities on a page are related: an Article has a publisher (Organization), belongs to a WebSite, and sits in a BreadcrumbList. Rather than emitting separate `<script>` blocks for each entity, the bundle groups them into a single `@graph`:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "WebSite", "@id": "https://example.com/#website" },
    { "@type": "Organization", "@id": "https://example.com/#organization" },
    { "@type": "Article", "publisher": { "@id": "https://example.com/#organization" } }
  ]
}
```

This approach provides:

1. **One `<script>` block** -- easier to inspect and debug
2. **Google compliance** -- Google recommends `@graph` for related entities on the same page
3. **No duplication** -- shared entities (Organization, WebSite) are declared once and referenced everywhere

### The @id mechanism

Each node receives a unique `@id`. Other nodes reference it instead of duplicating data. The framework uses these conventions:

| Node | @id |
|---|---|
| WebSite | `{siteUrl}/#website` |
| Organization | `{siteUrl}/#organization` |
| Article | `{permalink}/#article` |

The `SchemaRef` class encapsulates this. `Schema::ref('#organization')` produces `{"@id": "#organization"}`. Generators use this to link WebSite, Organization, and Article nodes.

### One or many schemas?

`SchemaManager` adapts its output format automatically:

- **One schema** -- emitted as a plain JSON-LD object with `@context`, no `@graph`
- **Multiple schemas** -- wrapped in `@context` + `@graph`

This is transparent to the developer.
