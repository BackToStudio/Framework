# Hooks


### WordPress hooks registered

| Class | Hook | Priority | Description |
|---|---|---|---|
| `RegisterDefaultSchemas` | `wp` | default | Registers WebSite, Organization, Article, BreadcrumbList |
| `InjectSchemaInHead` | `wp_head` | 1 | Injects JSON-LD into `<head>` |
| `AddSchemaToTimberContext` | `timber/context` | default | Adds `schema` (SchemaManager) to Twig context |
| `AddSocialLinksToTimberContext` | `timber/context` | default | Adds `facebook`, `twitter`, etc. to Twig context |
| `CleanYoastFootprint` | `wpseo_debug_markers`, `wpseo_hide_version` | default | Removes Yoast debug markers |
| `DisablePluginSchema` | `wpseo_json_ld_output` or `seopress_schemas_auto_enabled` | default | Disables plugin schema output |

### Custom action

| Action | Parameters | Description |
|---|---|---|
| `framework/seo/schema` | `(SchemaManager $manager)` | Fired after default schemas are registered. Add custom schemas here. |
