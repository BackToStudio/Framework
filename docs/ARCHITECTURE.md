# Architecture

*Explanation — Understanding-oriented*

This document describes the layered architecture of the framework and the relationships between modules.

---

## Overview

The framework is organized into **three layers** following the dependency inversion principle: higher layers depend on lower layers, never the reverse.

```
src/
├── Bundle/                        ← APPLICATION BUNDLES
│   ├── Admin/                       (BackTo\Framework\Bundle\Admin)
│   ├── Blocks/                      (BackTo\Framework\Bundle\Blocks)
│   ├── Gdpr/                        (BackTo\Framework\Bundle\Gdpr)
│   ├── Performance/                 (BackTo\Framework\Bundle\Performance)
│   ├── Plugin/                      (BackTo\Framework\Bundle\Plugin)
│   ├── Security/                    (BackTo\Framework\Bundle\Security)
│   ├── Seo/                         (BackTo\Framework\Bundle\Seo)
│   └── Theme/                       (BackTo\Framework\Bundle\Theme)
│
├── (Domain Services)              ← DOMAIN SERVICES
│   ├── Assets/                      (BackTo\Framework\Assets)
│   ├── Observability/               (BackTo\Framework\Observability)
│   ├── Options/                     (BackTo\Framework\Options)
│   ├── PostMeta/                    (BackTo\Framework\PostMeta)
│   ├── PostType/                    (BackTo\Framework\PostType)
│   ├── Queue/                       (BackTo\Framework\Queue)
│   ├── RestApi/                     (BackTo\Framework\RestApi)
│   └── Taxonomy/                    (BackTo\Framework\Taxonomy)
│
└── (Foundation)                   ← FOUNDATION
    ├── Cache/                       (BackTo\Framework\Cache)
    ├── Cli/                         (BackTo\Framework\Cli)
    ├── Clock/                       (BackTo\Framework\Clock)
    ├── Compose/                     (BackTo\Framework\Compose)
    ├── Contracts/                   (BackTo\Framework\Contracts)
    ├── Exception/                   (BackTo\Framework\Exception)
    ├── Hooks/                       (BackTo\Framework\Hooks)
    ├── Http/                        (BackTo\Framework\Http)
    └── Query/                       (BackTo\Framework\Query)
```

Application bundles live in `src/Bundle/` (namespace `BackTo\Framework\Bundle\*`),
similar to Symfony Bundles. The Foundation and Domain Services layers remain
at the root of `src/` (namespace `BackTo\Framework\*`).

---

## Foundation

The lowest layer. Provides the technical infrastructure that the rest of the framework uses. **No WordPress business logic** — only abstractions, standards, and plumbing.

| Module | Role | Standards |
|---|---|---|
| **Contracts** | Port interfaces (HookDispatcher, ContentQuery, UserContext, etc.) | — |
| **Http** | HTTP client, PSR-7 Request/Response | PSR-7, PSR-18 |
| **Cache** | Cache interfaces and strategies | PSR-16 |
| **Clock** | Time abstraction (SystemClock, FrozenClock) | PSR-20 |
| **Exception** | Framework exceptions | — |
| **Query** | Value objects for queries (SortDirection, MetaCompare) | — |
| **Hooks** | Hook registry, DI for WordPress adapters | — |
| **Compose** | Kernel, extensions, DI container (scoped Symfony DI) | PSR-11 (scoped) |
| **Cli** | WP-CLI commands | — |

**Rule**: a Foundation module only depends on other Foundation modules or `Contracts/`.

---

## Domain Services

Middle layer. Abstracts WordPress business concepts (post types, taxonomies, options, etc.) behind clean interfaces. Reusable by application modules.

| Module | Role | Dependencies |
|---|---|---|
| **PostType** | Custom Post Type registration and management | Contracts, PostMeta, Query, Observability |
| **Taxonomy** | Taxonomy registration | Contracts, Query, Observability |
| **PostMeta** | Post metadata management | Contracts |
| **RestApi** | REST route registration | Contracts |
| **Options** | WordPress options read/write | Contracts |
| **Assets** | Script and style management | Contracts, Observability |
| **Queue** | Cron scheduling and async tasks | Contracts, Cache |
| **Observability** | Logging (PSR-3), metrics, health checks | Contracts, Cache |

**Rule**: a Domain Service depends on Foundation and other Domain Services, never on an application module.

---

## Application Modules

The highest layer. Implements concrete features for WordPress sites. This is where specific business logic lives.

| Module | Role | Key dependencies |
|---|---|---|
| **Security** | Hardening, audit, 2FA, rate limiting, CORS, CSP | Cache, Observability, Options, Queue, RestApi, Admin |
| **Performance** | Page cache, htaccess, preloading, minification | Cache, Http, Options, Queue |
| **Seo** | Schema.org, Yoast/SEOPress providers, breadcrumbs | Contracts, Options |
| **Admin** | Admin pages, menus, capabilities | Contracts |
| **Theme** | Head cleanup, text domain, theme hooks | Contracts, Plugin |
| **Plugin** | Text domain, plugin lifecycle hooks | Contracts, Theme |
| **Blocks** | Gutenberg blocks and block styles | Assets, Contracts |
| **Gdpr** | GDPR compliance | Contracts |

**Rule**: an application module can depend on anything below it (Foundation + Domain Services) and on other application modules when justified.

---

## Dependency graph

```
                         Contracts
                        ╱    │    ╲
                 Exception  Query  Clock
                    │        │       │
    ┌───────────────┼────────┼───────┼──────────────┐
    │           Http    Cache    Hooks    Compose     │  Foundation
    └───────────────┼────────┼───────┼──────────────┘
                    │        │       │
    ┌───────────────┼────────┼───────┼──────────────┐
    │  Options  Assets  PostType  Taxonomy  RestApi   │
    │  PostMeta   Queue   Observability               │  Domain Services
    └───────────────┼────────┼───────┼──────────────┘
                    │        │       │
    ┌───────────────┼────────┼───────┼──────────────┐
    │  Security  Performance  Seo  Admin  Theme       │
    │  Blocks    Plugin       Gdpr                    │  Application
    └─────────────────────────────────────────────────┘
```

---

## PSR compliance

| PSR | Standard | Module | Status |
|---|---|---|---|
| PSR-3 | Logger | Observability | `LoggerInterface extends Psr\Log\LoggerInterface` |
| PSR-4 | Autoloading | (global) | Compliant |
| PSR-7 | HTTP Message | Http | `Response`, `StringStream` implement PSR-7 |
| PSR-11 | Container | Compose | Via Symfony DI (vendor-scoped) |
| PSR-12 | Coding Style | (global) | PER-CS 2.0 (successor to PSR-12) |
| PSR-16 | Simple Cache | Cache | `CacheInterface extends Psr\SimpleCache\CacheInterface` |
| PSR-18 | HTTP Client | Http | `HttpClientInterface extends Psr\Http\Client\ClientInterface` |
| PSR-20 | Clock | Clock | `ClockInterface extends Psr\Clock\ClockInterface` |

---

## Hexagonal Architecture (Ports & Adapters)

Each module follows the Hexagonal pattern:

```
src/ModuleName/
├── Contracts/           ← Ports (interfaces)
├── Infrastructure/      ← WordPress adapters (implementations)
├── Tests/               ← Unit tests
├── Hooks/               ← Hook classes (application layer)
└── *.php                ← Domain/application logic
```

- **Ports** (`Contracts/`): interfaces consumed by business code
- **Adapters** (`Infrastructure/`): WordPress implementations that call WP functions
- Business code never depends on WordPress directly — only on ports

Concrete example:

```php
// Business code uses the port
class PreloadExecutor
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,  // port
    ) {}
}

// The WordPress adapter provides the implementation
class WordPressHttpClient implements HttpClientInterface    // adapter
{
    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->toResponse(\wp_remote_request($url, ...));
    }
}
```

---

## Naming conventions

| Pattern | Meaning | Example |
|---|---|---|
| `*Interface` | Port / contract | `HttpClientInterface` |
| `WordPress*` | WordPress adapter | `WordPressHttpClient` |
| `*Extension` | DI module (registers services) | `SecurityExtension` |
| `*Test` | Unit test | `ResponseTest` |
| `Abstract*` | Shared base class | `AbstractCache` |

---

## Adding a new module

1. Create `src/MyModule/` with the structure above
2. Create `MyModuleExtension extends AbstractExtension` for DI
3. Place interfaces in `Contracts/`, WordPress adapters in `Infrastructure/`
4. Determine the layer: is this module Foundation, Domain Service, or Application?
5. Respect the dependency rules of its layer
