# Render schema in a Twig template

The `SchemaManager` is available in the Timber context as `schema`. Use it to render JSON-LD at a specific location in your template instead of relying on `wp_head`:

```twig
{{ schema.render()|raw }}
```

Both mechanisms coexist: `InjectSchemaInHead` writes to `wp_head`, and `schema.render()` can be used in templates.
