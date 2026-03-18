# Architecture Overview

## Layered structure

The framework follows Clean Architecture principles with three layers:

```
┌──────────────────────────────────────────────────┐
│                  Infrastructure                   │
│  WordPress adapters, Repositories, DI config      │
│                                                   │
│  ┌──────────────────────────────────────────────┐ │
│  │              Application Layer               │ │
│  │  Orchestrators: RegisterPostType,            │ │
│  │  RegisterTaxonomy, RegisterBlockStyles, ...  │ │
│  │                                              │ │
│  │  ┌──────────────────────────────────────────┐│ │
│  │  │           Domain Layer                   ││ │
│  │  │  Entities, Value Objects, Contracts,     ││ │
│  │  │  Factories, Registries                   ││ │
│  │  └──────────────────────────────────────────┘│ │
│  └──────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────┘
```

Dependencies always point inward. The domain layer has **zero** WordPress dependencies.

## Module structure

Each module follows the same directory convention:

```
src/PostType/
├── Contracts/                    # Port interfaces (domain)
│   ├── PostTypeInterface.php
│   ├── PostTypeRegistryInterface.php
│   └── PostTypeRegistrarInterface.php
├── Entity/                       # Domain entities and Value Objects
│   ├── Post.php
│   ├── PostType.php
│   └── PostStatus.php            # Value Object (enum)
├── Specification/                # Named query criteria
│   ├── PostSpecification.php     # Interface
│   ├── PublishedPosts.php
│   ├── RecentPosts.php
│   └── AndPostSpecification.php  # Composite
├── Infrastructure/               # WordPress adapters
│   └── WordPressPostTypeRegistrar.php
├── DependencyInjection/
│   └── Compiler/                 # Symfony compiler passes
│       └── RegisterPostTypePass.php
├── Repository/                   # Data access (infrastructure)
│   └── PostRepository.php
├── Tests/                        # Unit tests
├── PostTypeFactory.php           # Domain factory
├── PostTypeRegistry.php          # Domain registry
└── RegisterPostType.php          # Application orchestrator
```

## Shared Query namespace

Cross-cutting query enums live in `src/Query/` to avoid circular dependencies between PostType, Taxonomy, and PostMeta:

| Enum | Values |
|------|--------|
| `MetaCompare` | `EQUAL`, `NOT_EQUAL`, `GREATER_THAN`, `LIKE`, `IN`, `EXISTS`, etc. |
| `SortDirection` | `ASC`, `DESC` |

## Kernel hierarchy

```
AbstractKernel (abstract)
├── uses WordPressContainer (trait)
├── uses TextDomain (trait)
│
├── ThemeKernel
│   └── Parameters: themeDirectory, themeTextDomain
│
└── PluginKernel
    └── Parameters: pluginDirectory, pluginTextDomain
```

## Container lifecycle

1. `Kernel::load()` is called
2. `getContainer()` checks the `ConfigCache` (`var/container.php`)
3. If cache is stale, `getContainerBuilder()` builds a new container:
   a. Sets kernel parameters (directory, text domain)
   b. `loadServices()` loads framework bundles + kernel services + project services
   c. `configureWordPressContainer()` applies autoconfiguration and compiler passes
   d. Container is compiled and dumped to PHP
4. `HookRegistry::runHooks()` iterates over all collected hooks

## Autoconfiguration

Interfaces are auto-tagged in the DI container:

| Interface | Tag | Compiler Pass |
|-----------|-----|---------------|
| `PostTypeInterface` | `wordpress.post_type` | `RegisterPostTypePass` |
| `TaxonomyInterface` | `wordpress.taxonomy` | `RegisterTaxonomyPass` |
| `BlockInterface` | `wordpress.block` | `RegisterBlockPass` |
| `BlockStyleInterface` | `wordpress.block_style` | `RegisterBlockStylePass` |
| `PostMetaStructureInterface` | `wordpress.post_meta` | `RegisterPostMetaStructurePass` |
| `HookInterface` | `wordpress.hook` | `RegisterHookPass` |
| `SecurityRuleInterface` | `wordpress.security_rule` | `RegisterSecurityRulePass` |
| `JobInterface` | `wordpress.queue_job` | `RegisterQueuePass` |
| `RegistryInterface` | *(set public)* | — |

## Port bindings (Clean Architecture)

Port interfaces are bound to WordPress adapters in `WordPressExtension`:

| Port Interface | WordPress Adapter |
|---------------|-------------------|
| `HookDispatcherInterface` | `WordPressHookDispatcher` |
| `PostTypeRegistrarInterface` | `WordPressPostTypeRegistrar` |
| `TaxonomyRegistrarInterface` | `WordPressTaxonomyRegistrar` |
| `BlockStyleRegistrarInterface` | `WordPressBlockStyleRegistrar` |
| `PostMetaRegistrarInterface` | `WordPressPostMetaRegistrar` |
| `FileLocatorInterface` | `WordPressFileLocator` |
| `LoginThrottleInterface` | `WordPressLoginThrottle` |
| `NonceManagerInterface` | `WordPressNonceManager` |
| `InputSanitizerInterface` | `WordPressInputSanitizer` |
| `OutputEscaperInterface` | `WordPressOutputEscaper` |
| `AuditLogRepositoryInterface` | `WordPressAuditLogRepository` |
| `FileIntegrityRepositoryInterface` | `WordPressFileIntegrityRepository` |
| `RateLimiterRepositoryInterface` | `WordPressRateLimiterRepository` |
| `LoginLocationRepositoryInterface` | `WordPressLoginLocationRepository` |
| `TwoFactorRepositoryInterface` | `WordPressTwoFactorRepository` |
| `QueueRepositoryInterface` | `WordPressQueueRepository` |

## Bundle system

The `getBundles()` method in `WordPressContainer` defines which modules are loaded:

```php
[
    'dir' => dirname(__DIR__) . '/PostType',
    'namespace' => 'BackTo\\Framework\\PostType\\',
    'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
]
```

Each bundle auto-registers all classes in its directory (excluding special subdirectories) as autowired, autoconfigured services.
