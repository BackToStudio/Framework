# Timber/Twig integration

The bundle exposes two types of data in the Timber context:

### SchemaManager

`AddSchemaToTimberContext` adds the `SchemaManager` under the `schema` key. In a Twig template: `{{ schema.render()|raw }}`. This allows rendering JSON-LD at a specific template location instead of relying on `wp_head`. Both mechanisms coexist.

### Social links

`AddSocialLinksToTimberContext` adds social links as top-level context variables: `facebook`, `twitter`, `instagram`, `linkedin`, `pinterest`, `youtube`. Each is either a URL string or `null`.

Top-level variables were chosen for simplicity: `{% if facebook %}` reads better than `{% if social_links.facebook %}`.
