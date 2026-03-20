# Architecture

*Explanation — Understanding-oriented*

Ce document décrit l'architecture en couches du framework et les relations entre modules.

---

## Vue d'ensemble

Le framework est organisé en **trois couches** qui suivent le principe de dépendance inversée : les couches hautes dépendent des couches basses, jamais l'inverse.

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

Les bundles applicatifs vivent dans `src/Bundle/` (namespace `BackTo\Framework\Bundle\*`),
à la manière des Symfony Bundles. Les couches Foundation et Domain Services restent
à la racine de `src/` (namespace `BackTo\Framework\*`).

---

## Foundation

La couche la plus basse. Fournit l'infrastructure technique que tout le reste du framework utilise. **Aucune logique métier WordPress** — uniquement des abstractions, des standards, et de la plomberie.

| Module | Rôle | Standards |
|---|---|---|
| **Contracts** | Interfaces ports (HookDispatcher, ContentQuery, UserContext, etc.) | — |
| **Http** | Client HTTP, Request/Response PSR-7 | PSR-7, PSR-18 |
| **Cache** | Interfaces et stratégies de cache | PSR-16 |
| **Clock** | Abstraction du temps (SystemClock, FrozenClock) | PSR-20 |
| **Exception** | Exceptions framework | — |
| **Query** | Value objects pour les requêtes (SortDirection, MetaCompare) | — |
| **Hooks** | Registre de hooks, DI des adapters WordPress | — |
| **Compose** | Kernel, extensions, container DI (Symfony DI scopé) | PSR-11 (scopé) |
| **Cli** | Commandes WP-CLI | — |

**Règle** : un module Foundation ne dépend que d'autres modules Foundation ou de `Contracts/`.

---

## Domain Services

Couche intermédiaire. Abstrait les concepts métier WordPress (post types, taxonomies, options, etc.) derrière des interfaces propres. Réutilisable par les modules applicatifs.

| Module | Rôle | Dépendances |
|---|---|---|
| **PostType** | Enregistrement et gestion des Custom Post Types | Contracts, PostMeta, Query, Observability |
| **Taxonomy** | Enregistrement des taxonomies | Contracts, Query, Observability |
| **PostMeta** | Gestion des métadonnées de posts | Contracts |
| **RestApi** | Enregistrement de routes REST | Contracts |
| **Options** | Lecture/écriture des options WordPress | Contracts |
| **Assets** | Gestion des scripts et styles | Contracts, Observability |
| **Queue** | Cron scheduling et tâches asynchrones | Contracts, Cache |
| **Observability** | Logging (PSR-3), métriques, health checks | Contracts, Cache |

**Règle** : un Domain Service dépend du Foundation et d'autres Domain Services, jamais d'un module applicatif.

---

## Application Modules

La couche la plus haute. Implémente des fonctionnalités concrètes pour les sites WordPress. C'est ici que vit la logique métier spécifique.

| Module | Rôle | Dépendances clés |
|---|---|---|
| **Security** | Hardening, audit, 2FA, rate limiting, CORS, CSP | Cache, Observability, Options, Queue, RestApi, Admin |
| **Performance** | Page cache, htaccess, preloading, minification | Cache, Http, Options, Queue |
| **Seo** | Schema.org, providers Yoast/SEOPress, breadcrumbs | Contracts, Options |
| **Admin** | Pages admin, menus, capabilities | Contracts |
| **Theme** | Nettoyage head, text domain, hooks theme | Contracts, Plugin |
| **Plugin** | Text domain, hooks plugin lifecycle | Contracts, Theme |
| **Blocks** | Gutenberg blocks et block styles | Assets, Contracts |
| **Gdpr** | Conformité RGPD | Contracts |

**Règle** : un module applicatif peut dépendre de tout ce qui est en dessous (Foundation + Domain Services) et d'autres modules applicatifs quand c'est justifié.

---

## Graphe de dépendances

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

## Conformité PSR

| PSR | Standard | Module | Statut |
|---|---|---|---|
| PSR-3 | Logger | Observability | `LoggerInterface extends Psr\Log\LoggerInterface` |
| PSR-4 | Autoloading | (global) | Conforme |
| PSR-7 | HTTP Message | Http | `Response`, `StringStream` implémentent PSR-7 |
| PSR-11 | Container | Compose | Via Symfony DI (vendor-scopé) |
| PSR-12 | Coding Style | (global) | PER-CS 2.0 (successeur de PSR-12) |
| PSR-16 | Simple Cache | Cache | `CacheInterface extends Psr\SimpleCache\CacheInterface` |
| PSR-18 | HTTP Client | Http | `HttpClientInterface extends Psr\Http\Client\ClientInterface` |
| PSR-20 | Clock | Clock | `ClockInterface extends Psr\Clock\ClockInterface` |

---

## Hexagonal Architecture (Ports & Adapters)

Chaque module suit le pattern Hexagonal :

```
src/ModuleName/
├── Contracts/           ← Ports (interfaces)
├── Infrastructure/      ← Adapters WordPress (implémentations)
├── Tests/               ← Tests unitaires
├── Hooks/               ← Hook classes (application layer)
└── *.php                ← Domain/application logic
```

- **Ports** (`Contracts/`) : interfaces que le code métier consomme
- **Adapters** (`Infrastructure/`) : implémentations WordPress qui appellent les fonctions WP
- Le code métier ne dépend jamais directement de WordPress — uniquement des ports

Exemple concret :

```php
// Le code métier utilise le port
class PreloadExecutor
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,  // port
    ) {}
}

// L'adapter WordPress fournit l'implémentation
class WordPressHttpClient implements HttpClientInterface    // adapter
{
    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->toResponse(\wp_remote_request($url, ...));
    }
}
```

---

## Convention de nommage

| Pattern | Signification | Exemple |
|---|---|---|
| `*Interface` | Port / contrat | `HttpClientInterface` |
| `WordPress*` | Adapter WordPress | `WordPressHttpClient` |
| `*Extension` | Module DI (enregistre services) | `SecurityExtension` |
| `*Test` | Test unitaire | `ResponseTest` |
| `Abstract*` | Classe de base partagée | `AbstractCache` |

---

## Ajouter un nouveau module

1. Créer `src/MonModule/` avec la structure ci-dessus
2. Créer `MonModuleExtension extends AbstractExtension` pour le DI
3. Placer les interfaces dans `Contracts/`, les adapters WP dans `Infrastructure/`
4. Vérifier la couche : ce module est-il Foundation, Domain Service ou Application ?
5. Respecter les règles de dépendance de sa couche
