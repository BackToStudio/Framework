# Generation pipeline

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
