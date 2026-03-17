# Modules Reference

The framework is organized into independent modules, each following Clean Architecture principles with entities, contracts, infrastructure adapters, and application orchestrators.

| Module | Description | Configuration |
|--------|-------------|---------------|
| [PostType](modules/post-type.md) | Registers WordPress custom post types | — |
| [Taxonomy](modules/taxonomy.md) | Registers WordPress custom taxonomies | — |
| [Blocks](modules/blocks.md) | Registers WordPress block styles | — |
| [PostMeta](modules/post-meta.md) | Registers WordPress post meta fields | — |
| [Cache](modules/cache.md) | PSR-16 SimpleCache with 3 strategies | — |
| [Seo](modules/seo.md) | Unified SEO plugin integration | — |
| [Hooks](modules/hooks.md) | Central hook orchestration | — |
| [Assets](modules/assets.md) | Media and SVG utilities | — |
| [Admin](modules/admin.md) | WordPress admin page management | — |
| [Options](modules/options.md) | WordPress `wp_options` abstraction | — |
| [RestApi](modules/rest-api.md) | REST API route management | — |
| [Observability](modules/observability.md) | Logging, error handling, health checks | — |
| [Cli](modules/cli.md) | WP-CLI scaffolding commands | — |
| [Security](modules/security.md) | Security hardening (30+ rules) | `config/security.php` |
| [Gdpr](modules/gdpr.md) | GDPR consent management | — |
| [Performance](modules/performance.md) | Performance optimization | `config/performance.php` |
| [Compose](modules/compose.md) | Framework kernel and container | — |
