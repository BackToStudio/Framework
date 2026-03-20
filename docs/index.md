# BackTo Framework Documentation

BackTo Framework improves the WordPress development experience by providing a structured, modular architecture powered by Symfony Dependency Injection.

This documentation follows the [Diataxis](https://diataxis.fr/) framework and is organized into four sections:

## Tutorials

Step-by-step lessons to get started with the framework.

- [Build a WordPress theme with BackTo Framework](tutorials/build-a-theme.md)
- [Build a WordPress plugin with BackTo Framework](tutorials/build-a-plugin.md)

## How-to Guides

Practical guides for accomplishing specific tasks.

- [How to register custom post types](how-to/register-post-types.md)
- [How to register custom taxonomies](how-to/register-taxonomies.md)
- [How to register block styles](how-to/register-block-styles.md)
- [How to register post meta fields](how-to/register-post-meta.md)
- [How to use the cache system](how-to/use-cache.md)
- [How to integrate SEO plugins](how-to/integrate-seo.md)
- [How to register REST API routes](how-to/use-rest-api.md)
- [How to work with options](how-to/work-with-options.md)
- [How to use WP-CLI scaffolding](how-to/use-cli-scaffolding.md)
- [How to use the observability module](how-to/use-observability.md)
- [How to debug with Query Monitor](how-to/debug-with-query-monitor.md)
- [How to manage GDPR consent and tracking scripts](how-to/manage-gdpr-consent.md)
- [How to use WordPress security hardening](how-to/use-security.md)
- [How to use the async queue system](how-to/use-queue.md)
- [How to use Specifications for querying](how-to/use-specifications.md)
- [How to use Value Objects](how-to/use-value-objects.md)

## Reference

Technical descriptions of the framework's modules, contracts, and architecture.

- [Architecture overview](reference/architecture.md)
- [Modules reference](reference/modules.md) — index of all 17 modules with per-module detail pages
- [Contracts and interfaces](reference/contracts.md) — shared cross-cutting contracts
- [Configuration reference](reference/configuration.md) — framework-level configuration
- [Compatibility matrix](reference/compatibility.md)

## Bundles

Each bundle has its own Diataxis documentation (tutorial, how-to, reference, explanation).

| Bundle | Description | Docs |
|--------|-------------|------|
| [Security](security/README.md) | WordPress hardening, 2FA, CSP, rate limiting, audit logging | [tutorial](security/tutorial.md) · [how-to](security/how-to.md) · [reference](security/reference.md) · [explanation](security/explanation.md) |
| [Performance](performance/README.md) | Page cache, HTML/CSS/JS minification, image optimization, .htaccess | [tutorial](performance/tutorial.md) · [how-to](performance/how-to.md) · [reference](performance/reference.md) · [explanation](performance/explanation.md) |
| [Seo](seo/README.md) | Schema.org JSON-LD, breadcrumbs, meta tags, fluent API | [tutorial](seo/tutorial.md) · [how-to](seo/how-to.md) · [reference](seo/reference.md) · [explanation](seo/explanation.md) |
| [Http](http/README.md) | PSR-18 HTTP client, request/response, async support | [tutorial](http/tutorial.md) · [how-to](http/how-to.md) · [reference](http/reference.md) · [explanation](http/explanation.md) |
| [Admin](admin/README.md) | Admin pages, settings, menu registration, notices | [tutorial](admin/tutorial.md) · [how-to](admin/how-to.md) · [reference](admin/reference.md) · [explanation](admin/explanation.md) |
| [Blocks](blocks/README.md) | Block registration, block styles, Gutenberg integration | [tutorial](blocks/tutorial.md) · [how-to](blocks/how-to.md) · [reference](blocks/reference.md) · [explanation](blocks/explanation.md) |
| [Plugin](plugin/README.md) | Plugin lifecycle, activation, deactivation hooks | [tutorial](plugin/tutorial.md) · [how-to](plugin/how-to.md) · [reference](plugin/reference.md) · [explanation](plugin/explanation.md) |
| [Theme](theme/README.md) | Theme support, assets, menus, sidebars, templates | [tutorial](theme/tutorial.md) · [how-to](theme/how-to.md) · [reference](theme/reference.md) · [explanation](theme/explanation.md) |
| [Gdpr](gdpr/README.md) | GDPR consent management, tracking script control | [tutorial](gdpr/tutorial.md) · [how-to](gdpr/how-to.md) · [reference](gdpr/reference.md) · [explanation](gdpr/explanation.md) |

See also: [Architecture overview](ARCHITECTURE.md)

## Explanation

Background, design decisions, and conceptual understanding.

- [Why a WordPress framework?](explanation/why-a-framework.md)
- [Clean Architecture and DDD in WordPress](explanation/clean-architecture.md)
- [Vendor scoping with php-scoper](explanation/vendor-scoping.md)
- [GDPR consent management design](explanation/gdpr-consent-design.md)
- [Queue system design](explanation/queue-design.md)
