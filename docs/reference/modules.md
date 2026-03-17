# Modules Reference

The framework is organized into independent modules, each following Clean Architecture principles.

## Modules with dedicated reference pages

These modules expose public contracts or configuration:

| Module | Description | Configuration |
|--------|-------------|---------------|
| [PostType](modules/post-type.md) | Custom post types | — |
| [Taxonomy](modules/taxonomy.md) | Custom taxonomies | — |
| [Blocks](modules/blocks.md) | Block styles | — |
| [PostMeta](modules/post-meta.md) | Post meta fields | — |
| [Cache](modules/cache.md) | PSR-16 SimpleCache (Memory, Transient, Filesystem) | `config/cache.php` |
| [Seo](modules/seo.md) | Unified SEO plugin integration (Yoast, SEOPress) | `config/seo.php` |
| [Admin](modules/admin.md) | Admin page management | — |
| [Options](modules/options.md) | `wp_options` abstraction | — |
| [RestApi](modules/rest-api.md) | REST API route management | — |
| [Observability](modules/observability.md) | Logging, error handling, health checks | — |
| [Gdpr](modules/gdpr.md) | GDPR consent management | — |
| [Security](modules/security.md) | Security hardening (30+ rules, 2FA, CSP, audit log) | `config/security.php` |
| [Performance](modules/performance.md) | Performance optimization (cache, minification, .htaccess) | `config/performance.php` |

## Other modules

These modules are used internally or have no public contracts:

| Module | Description |
|--------|-------------|
| Hooks | Central hook orchestration (`HookRegistry`, `WordPressHookDispatcher`) |
| Assets | Media and SVG utilities (`SvgFactory`, `WordPressFileLocator`) |
| Cli | WP-CLI scaffolding: `wp make:post-type`, `wp make:taxonomy`, `wp make:block`, `wp make:hook`, `wp make:rest-route` |
| Compose | Framework kernel (`AbstractKernel`), container lifecycle, autoconfiguration |
