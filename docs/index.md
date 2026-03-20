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

Chaque bundle possède sa propre documentation Diataxis complète (README, tutorial, how-to, reference, explanation).

| Bundle | Description | Docs |
|--------|-------------|------|
| [Security](security/README.md) | Hardening WordPress, 2FA, CSP, rate limiting, audit | [tutorial](security/tutorial.md) · [how-to](security/how-to.md) · [reference](security/reference.md) · [explanation](security/explanation.md) |
| [Performance](performance/README.md) | Page cache, minification HTML/CSS/JS, optimisation images, .htaccess | [tutorial](performance/tutorial.md) · [how-to](performance/how-to.md) · [reference](performance/reference.md) · [explanation](performance/explanation.md) |
| [Seo](seo/README.md) | Schema.org JSON-LD, breadcrumbs, meta, API fluide | [tutorial](seo/tutorial.md) · [how-to](seo/how-to.md) · [reference](seo/reference.md) · [explanation](seo/explanation.md) |
| [Http](http/README.md) | Couche HTTP, routing, middleware, request/response | [tutorial](http/tutorial.md) · [how-to](http/how-to.md) · [reference](http/reference.md) · [explanation](http/explanation.md) |
| [Admin](admin/README.md) | Pages d'administration, settings, notices | — |
| [Blocks](blocks/README.md) | Enregistrement et gestion des blocs Gutenberg | — |
| [Plugin](plugin/README.md) | Cycle de vie plugin, activation, désactivation | — |
| [Theme](theme/README.md) | Support thème, assets, menus, sidebars | — |
| [Gdpr](gdpr/README.md) | Consentement RGPD, gestion des scripts de tracking | — |

Voir aussi : [Architecture globale](ARCHITECTURE.md)

## Explanation

Background, design decisions, and conceptual understanding.

- [Why a WordPress framework?](explanation/why-a-framework.md)
- [Clean Architecture and DDD in WordPress](explanation/clean-architecture.md)
- [Vendor scoping with php-scoper](explanation/vendor-scoping.md)
- [GDPR consent management design](explanation/gdpr-consent-design.md)
- [Queue system design](explanation/queue-design.md)
