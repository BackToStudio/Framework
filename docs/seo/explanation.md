# SEO Bundle -- Architecture & Design

*Explanation -- Understanding-oriented*

---

## Why generate structured data in PHP?

WordPress SEO plugins (Yoast, SEOPress) generate their own JSON-LD, but their output is limited to generic types (WebSite, Organization, Article). When a theme needs Product, Event, Course, or FAQ schemas tied to custom post types, plugin-generated markup is insufficient.

The SEO bundle places structured data generation in the theme's PHP layer, where it has full access to post data, custom fields, and business logic. The result is a single `<script type="application/ld+json">` block in the `<head>` with a coherent `@graph` linking all entities.

---

## The JSON-LD @graph pattern

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

---

## Generation pipeline

Structured data generation follows a three-stage pipeline orchestrated by WordPress hooks.

### Stage 1: RegisterDefaultSchemas (hook: `wp`)

Runs on the `wp` action, when the query context is established. It performs four operations in order:

1. **WebSiteSchemaGenerator** -- produces a WebSite node with site name, URL, description, and a SearchAction for the sitelinks search box
2. **OrganizationSchemaGenerator** -- produces an Organization node with site name, logo (`custom_logo` theme mod), and `sameAs` links from the SEO provider
3. **PostTypeSchemaResolver** -- checks the `post_type_map` configuration for a generator matching the current post type. Runs only on singular pages (`is_singular()`). Default mapping: `post` to `ArticleSchemaGenerator`
4. **BreadcrumbSchemaGeneratorInterface** -- produces a BreadcrumbList from the current page context. The default implementation (`WordPressBreadcrumbSchemaGenerator`) builds breadcrumbs for posts, pages, archives, taxonomies, authors, and search results

After these four steps, the `framework/seo/schema` action fires with the `SchemaManager` as its parameter. This is the primary extension point for themes.

### Stage 2: SchemaManager (registry)

The `SchemaManager` is a simple registry. Each generator adds schemas via `add()`. No transformation happens at this stage -- schemas remain as PHP objects with typed properties.

### Stage 3: InjectSchemaInHead (hook: `wp_head`, priority 1)

Calls `SchemaManager::render()`, which:

1. Converts each `SchemaType` to an array via `toArray()`, recursively resolving nested schemas and `SchemaRef` instances
2. Builds the `@context` / `@graph` envelope
3. Encodes as JSON with `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
4. Wraps in `<script type="application/ld+json">`

```
wp (WordPress action)
  RegisterDefaultSchemas::register()
    WebSiteSchemaGenerator::generate()      --> SchemaManager::add()
    OrganizationSchemaGenerator::generate()  --> SchemaManager::add()
    PostTypeSchemaResolver::resolve()        --> SchemaManager::add()
    BreadcrumbSchemaGenerator::generate()    --> SchemaManager::add()
    do_action('framework/seo/schema')        --> [theme adds custom schemas]

wp_head (WordPress action, priority 1)
  InjectSchemaInHead::render()
    SchemaManager::render()
      SchemaType::toArray() (for each schema)
      Build @graph
      json_encode() + <script>
```

---

## Post type to generator mapping

The `PostTypeSchemaResolver` maps post types to schema generators via the `config/seo.php` file:

```php
'post_type_map' => [
    'post'   => ArticleSchemaGenerator::class,
    'event'  => EventSchemaGenerator::class,
    'course' => CourseSchemaGenerator::class,
],
```

Each generator must expose a `generate(?int $postId): ?SchemaType` method. The resolver:

1. Checks that the current page is singular (`is_singular()`)
2. Reads the `post_type` of the current post
3. Looks up a generator in the mapping
4. Calls `generate($postId)` and adds the result to the `SchemaManager`

Generators use duck typing (no interface required) -- any object with a `generate(?int): ?SchemaType` method works. This keeps custom generators simple and decoupled from the framework.

---

## SEO provider abstraction

### The problem

WordPress SEO plugins store data in completely different ways:

| Data | Yoast SEO | SEOPress |
|---|---|---|
| SEO title | `_yoast_wpseo_title` | `_seopress_titles_title` |
| Meta description | `_yoast_wpseo_metadesc` | `_seopress_titles_desc` |
| Canonical URL | `_yoast_wpseo_canonical` | `_seopress_robots_canonical` |
| Facebook URL | `wpseo_social[facebook_site]` | `seopress_social_option_name[..._facebook]` |
| Schema output | `wpseo_json_ld_output` filter | `seopress_schemas_auto_enabled` filter |

A theme accessing these meta keys directly couples itself to a specific plugin.

### The solution: SeoProviderInterface

The framework defines a unified `SeoProviderInterface` that extends `MetaProviderInterface` (title, description, Open Graph) and `SocialLinksProviderInterface` (social URLs). Each plugin is wrapped in a provider:

- `YoastProvider` -- reads `wpseo_social` and `_yoast_wpseo_*`
- `SeoPressProvider` -- reads `seopress_social_option_name` and `_seopress_*`

The `SeoManager` iterates over registered providers and returns the first one where `isActive()` returns `true`. Theme code never needs to know which plugin is installed.

### Adding support for a new plugin

To support a new SEO plugin (e.g. Rank Math):

1. Create a class implementing `SeoProviderInterface`
2. Register it via `SeoManager::addProvider()`

The rest of the framework (social links in Twig, Organization `sameAs`, plugin schema disabling) works automatically.

---

## Timber/Twig integration

The bundle exposes two types of data in the Timber context:

### SchemaManager

`AddSchemaToTimberContext` adds the `SchemaManager` under the `schema` key. In a Twig template: `{{ schema.render()|raw }}`. This allows rendering JSON-LD at a specific template location instead of relying on `wp_head`. Both mechanisms coexist.

### Social links

`AddSocialLinksToTimberContext` adds social links as top-level context variables: `facebook`, `twitter`, `instagram`, `linkedin`, `pinterest`, `youtube`. Each is either a URL string or `null`.

Top-level variables were chosen for simplicity: `{% if facebook %}` reads better than `{% if social_links.facebook %}`.

---

## Schema validation

Each schema type declares its Google-required properties via the protected `getRequiredProperties()` method. For example, `Product` requires `name`, `image`, and `offers`.

Validation is optional and non-blocking: an incomplete schema is still rendered as JSON-LD. The `validate()` method returns missing property names, allowing developers to check conformance during development. The `SchemaManager` provides global validation via `validate()`, returning `[type => [missing properties]]` for all registered schemas.

---

## Plugin schema disabling

SEO plugins generate their own JSON-LD, which would duplicate the framework's output. `DisablePluginSchema` neutralizes this:

- **Yoast SEO**: `wpseo_json_ld_output` filter with `__return_empty_array`
- **SEOPress**: `seopress_schemas_auto_enabled` filter with `__return_false`

This is enabled by default (`schema.disable_plugin_schema` is `true`). Themes can disable it in `config/seo.php` to keep the plugin's schema output.

The action checks that an SEO provider is active before attempting to disable anything, avoiding unnecessary filter registration.

---

## Yoast footprint cleanup

Yoast SEO injects HTML comments (`<!-- This site is optimized with the Yoast SEO plugin ... -->`) and exposes its version number in the page source. `CleanYoastFootprint` removes these via two Yoast-native filters:

- `wpseo_debug_markers` -- `__return_false`
- `wpseo_hide_version` -- `__return_true`

This runs automatically with no configuration required.
