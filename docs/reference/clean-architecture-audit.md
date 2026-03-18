# Audit Clean Architecture — BackTo Framework

> Audit réalisé le 18/03/2026 — 531 fichiers PHP, 24 modules, ~10 960 lignes de code.

---

## Synthèse globale

| Module | Note | Violations | Points forts |
|--------|------|-----------|--------------|
| **Compose** | A+ | 0 | DIP exemplaire, 40+ port bindings |
| **Contracts** | A- | 2 | ISP excellent, abstractions pures |
| **Exception** | A+ | 0 | Hiérarchie propre, zéro couplage |
| **Hooks** | A+ | 0 | Isolation WP parfaite |
| **Blocks** | A+ | 0 | Port/Adapter correct |
| **Observability** | A+ | 0 | Decorator pattern, Null Object |
| **PostType** | A | 2 | Entités pures, Repository pattern |
| **Taxonomy** | A- | 3 | Bonne structure, 1 bug logique |
| **PostMeta** | A | 1 | Registrar bien abstrait |
| **Options** | A- | 1 | Infrastructure isolée |
| **Queue** | B+ | 2 | Factory exemplaire, DIP solide |
| **Seo** | A- | 1 | SchemaManager propre, Provider pattern |
| **RestApi** | B+ | 2 | Registrar correct |
| **Assets** | B+ | 2 | Factory/FileLocator corrects |
| **Cache** | B+ | 2 | Strategy pattern, PSR-16 |
| **Admin** | B | 5 | RegisterAdminPage correct |
| **Plugin** | B | 2 | Kernel correct |
| **Theme** | B | 5 | Pattern inconsistant |
| **Gdpr** | B | 1 | Registres propres |
| **Cli** | B- | 6 | Pas d'abstraction FS/Output |
| **Security** | B | 12+ | Infra bien isolée, mais couplage WP dans règles |
| **Performance** | B- | 3+ | God class PreloadPageCache |

**Score global : ~80% de conformité Clean Architecture.**

---

## Violations par catégorie

### 1. Couplage WordPress dans la couche Application/Domaine

C'est la violation la plus fréquente. Des fonctions WordPress sont appelées directement dans des classes qui ne sont pas dans le dossier `Infrastructure/`.

| Fichier | Ligne(s) | Fonction(s) WP |
|---------|----------|----------------|
| `PostType/Factory/PostFactory.php` | 36 | `error_log()` |
| `PostType/RegisterPostType.php` | 55, 69 | `error_log()` |
| `Taxonomy/RegisterTaxonomy.php` | 50 | `error_log()` |
| `PostMeta/Factory/PostMetaStructureFactory.php` | 21 | `"sanitize_text_field"` hardcodé |
| `Plugin/I18n/LoadPluginTextDomain.php` | 51-55 | `load_plugin_textdomain()` |
| `Plugin/I18n/LoadMuPluginTextDomain.php` | 51-54 | `load_muplugin_textdomain()` |
| `Theme/I18n/LoadThemeTextDomain.php` | 53 | `load_theme_textdomain()` |
| `Theme/Actions/CleanHead.php` | 17-31 | `remove_action()` ×8 |
| `Theme/Actions/RemoveEmojis.php` | 17-28 | `remove_action()`, `remove_filter()` |
| `Theme/Actions/RemoveSvgFilters.php` | 16-17 | `remove_action()` ×2 |
| `Admin/AddMenuForEditors.php` | 31-45 | `get_role()`, `add_cap()`, `current_user_can()`, `remove_submenu_page()` |
| `Admin/AddReusableBlockMenu.php` | 26 | `add_menu_page()` |
| `RestApi/RegisterRestRoute.php` | 34 | `is_user_logged_in()` |
| `Cache/Strategy/TransientCache.php` | 32, 51, 62 | `get_transient()`, `set_transient()`, `delete_transient()` |
| `Queue/RegisterQueue.php` | 102-212 | `wp_next_scheduled()`, `wp_schedule_event()`, `wp_clear_scheduled_hook()`, `get_transient()`, `set_transient()`, `delete_transient()` |
| `Security/AdminUrlObfuscation.php` | 146-182 | `status_header()`, `nocache_headers()`, `is_user_logged_in()`, `get_query_template()` |
| `Security/LoginAnomalyDetector.php` | 156-170 | `apply_filters()`, `$_SERVER` |
| `Security/PasswordPolicy.php` | 73-110 | `$_POST` direct |
| `Security/SessionManager.php` | 29-46 | `$_SERVER`, `WP_Session_Tokens` |
| `Security/MalwareScanner.php` | 172-179 | `wp_upload_dir()` |
| `Security/FileIntegrityMonitor.php` | 66-73 | `wp_next_scheduled()`, `wp_schedule_event()` |
| `Performance/Hooks/PreloadPageCache.php` | 71-313 | 20+ fonctions WP (get_post, wp_remote_get, spawn_cron…) |
| `Performance/Hooks/ServePageCache.php` | 82-151 | `is_404()`, `is_search()`, `is_admin()`, `is_user_logged_in()` |
| `Assets/ReplaceImgTagBySvgTag.php` | 32 | `error_log()` |
| `Cli/Command/AbstractMakeCommand.php` | 31, 116, 123 | `file_get_contents()`, `WP_CLI::success()`, `WP_CLI::error()` |
| `Cli/Generator/ClassGenerator.php` | 31, 34 | `mkdir()`, `file_put_contents()` |
| `Cli/cli-bootstrap.php` | 25-76 | `WP_CLI::add_command()` ×5 |

### 2. Fuite d'infrastructure dans les Contracts

| Fichier | Ligne(s) | Problème |
|---------|----------|----------|
| `Contracts/ExtensionInterface.php` | 7, 39 | Import de `ContainerBuilder` (Symfony) dans un contrat domaine |
| `Contracts/HookDispatcherInterface.php` | 15-24 | Terminologie WordPress (`addAction`, `addFilter`, `isAdmin`) |
| `RestApi/Contracts/RestRouteInterface.php` | 8, 23 | Import de `WP_REST_Request` / `WP_REST_Response` dans un contrat |

### 3. God Classes (responsabilités multiples)

| Fichier | Lignes | Responsabilités mélangées |
|---------|--------|--------------------------|
| `Gdpr/ConsentBanner.php` | 18-323 | HTML + CSS inline (100 lignes) + JS inline (150 lignes) + logique données |
| `Performance/Hooks/PreloadPageCache.php` | 25-343 | Scheduling cron + collecte URLs + détection pages liées + preload HTTP |
| `Security/AuditLogAdminPage.php` | — | 10+ méthodes publiques : rendu HTML, filtrage, export CSV, purge |
| `Security/ContentSecurityPolicyManager.php` | — | 3 interfaces, génération directives + envoi headers + modification script tags |
| `Security/CorsManager.php` | — | 3 interfaces, logique preflight + headers + validation origin + CIDR matching |

### 4. Bug logique détecté

| Fichier | Ligne | Problème |
|---------|-------|----------|
| `Taxonomy/RegisterTaxonomy.php` | 43 | `return;` devrait être `continue;` — arrête toute la boucle après la première taxonomy existante |

### 5. Dette technique

| Fichier | Problème |
|---------|----------|
| `Options/OptionsRepository.php` | Classe `@deprecated` encore présente dans le code |
| `PostMeta/Entity/PostMetaType.php` | Classe `@deprecated`, à supprimer |

---

## Points d'excellence

### Dependency Inversion Principle (DIP) — Exemplaire

Le module **Compose** centralise 40+ port bindings dans `WordPressExtension.php` :

```
HookDispatcherInterface        → WordPressHookDispatcher
PostTypeRegistrarInterface     → WordPressPostTypeRegistrar
TaxonomyRegistrarInterface     → WordPressTaxonomyRegistrar
AdminPageRegistrarInterface    → WordPressAdminPageRegistrar
BlockRegistrarInterface        → WordPressBlockRegistrar
OptionsRepositoryInterface     → WordPressOptionsRepository
InputSanitizerInterface        → WordPressInputSanitizer
OutputEscaperInterface         → WordPressOutputEscaper
LoggerInterface                → WordPressLogger
...
```

Tous les modules consomment des **interfaces**, jamais des implémentations concrètes dans leurs constructeurs.

### Interface Segregation Principle (ISP)

Les contrats sont finement découpés :
- `IdInterface`, `SlugInterface`, `ParentIdInterface` — 1 responsabilité chacun
- `BlockInterface` vs `BlockStyleInterface` vs `DynamicBlock` — séparation nette
- `ActivationHooks`, `DeactivationHooks`, `AdminHooks`, `Hooks` — hooks par contexte
- `SecurityRuleInterface` séparé de chaque sous-contrat (sanitizer, escaper, rate limiter…)

### Entités pures

Les entités domaine n'ont **aucune** dépendance WordPress :
- `PostType/Entity/Post.php` — POPO avec traits `HasId`, `HasSlug`, `HasParentId`
- `PostType/Entity/PostType.php` — configuration pure
- `Taxonomy/Entity/Term.php` — POPO pur
- `Taxonomy/Entity/Taxonomy.php` — configuration pure
- `PostMeta/Entity/PostMeta.php` — POPO pur
- `Queue/Entity/Job.php` — entité domaine avec `JobStatus` enum

### Patterns bien implémentés

| Pattern | Module | Implémentation |
|---------|--------|---------------|
| Strategy | Cache | 3 stratégies interchangeables (Memory, Transient, Filesystem) |
| Factory | PostType, Taxonomy, Queue | Transformation WP_Post/WP_Term → entité domaine |
| Repository | PostType, Taxonomy, PostMeta, Queue | Abstraction de la persistance |
| Registry | Tous | Collecte de services tagués via Compiler Pass |
| Decorator | Observability | `ObservableHookDispatcher` décore `HookDispatcherInterface` |
| Null Object | Observability | `NullLogger` |
| Template Method | Compose | `AbstractTaggedServiceCompilerPass` étendu par 9+ modules |
| Port/Adapter | Tous | Infrastructure/ contient les adaptateurs WordPress |
| Configurator | RestApi, Cache, Gdpr, Seo, Performance, Observability | API fluent typée |

### Tests

- 16+ suites de tests configurées dans `phpunit.xml`
- Tests unitaires et d'intégration séparés
- Tests par module dans `{Module}/Tests/`

---

## Recommandations par priorité

### Priorité 1 — Corrections critiques

1. **Corriger le bug `RegisterTaxonomy.php:43`** : remplacer `return;` par `continue;`
2. **Supprimer les classes `@deprecated`** : `OptionsRepository.php`, `PostMetaType.php`

### Priorité 2 — Abstractions manquantes

Créer les interfaces suivantes pour éliminer le couplage WP hors Infrastructure :

| Interface à créer | Remplace | Modules impactés |
|-------------------|----------|-----------------|
| `TextDomainLoaderInterface` | `load_plugin_textdomain()`, `load_theme_textdomain()` | Plugin, Theme |
| `HookRemoverInterface` ou méthode `removeAction/removeFilter` sur `HookDispatcherInterface` | `remove_action()`, `remove_filter()` directes | Theme |
| `CapabilityManagerInterface` | `get_role()`, `add_cap()`, `current_user_can()` | Admin, Security |
| `CronSchedulerInterface` | `wp_next_scheduled()`, `wp_schedule_event()` | Queue, Performance, Security |
| `TransientStoreInterface` | `get_transient()`, `set_transient()`, `delete_transient()` | Cache, Queue |
| `HttpClientInterface` | `wp_remote_get()` | Performance |
| `FilesystemInterface` | `file_get_contents()`, `mkdir()`, `file_put_contents()` | Cli, Cache |
| `CliOutputInterface` | `WP_CLI::success()`, `WP_CLI::error()` | Cli |
| `RequestInterface` / `ResponseInterface` | `WP_REST_Request`, `WP_REST_Response` dans contrats | RestApi |
| `GeoIpProviderInterface` | `apply_filters()` pour géolocalisation IP | Security |
| `TemplateLoaderInterface` | `get_query_template()` | Security |

### Priorité 3 — Refactoring God Classes

1. **`Gdpr/ConsentBanner.php`** → Séparer en :
   - `BannerRenderer` (HTML)
   - `BannerAssetsProvider` (JS/CSS en fichiers séparés)
   - `BannerDataBuilder` (logique données)

2. **`Performance/Hooks/PreloadPageCache.php`** → Séparer en :
   - `CachePreloadScheduler` (cron)
   - `PreloadUrlCollector` (collecte URLs via repositories existants)
   - `HttpPreloader` (requêtes HTTP)

3. **`Security/AuditLogAdminPage.php`** → Séparer en :
   - `AuditLogListRenderer`
   - `AuditLogExporter`
   - `AuditLogPurger`

### Priorité 4 — Cohérence des patterns

1. **Theme/Actions** : `CleanHead`, `RemoveEmojis`, `RemoveSvgFilters` doivent utiliser `HookDispatcherInterface` comme le fait déjà `RemoveWordPressVersion` — c'est le bon pattern à suivre.

2. **`error_log()` directes** : Remplacer par le `LoggerInterface` déjà disponible dans le framework (injecté via `WordPressLogger`).

3. **Admin** : `AddReusableBlockMenu` devrait utiliser `AdminPageRegistrarInterface` comme le fait `RegisterAdminPage`.

---

## Architecture de référence (conforme)

Pour référence, voici le pattern idéal suivi par les modules les mieux architecturés :

```
Module/
├── Contracts/
│   ├── {Entity}Interface.php          ← Contrat domaine pur
│   └── {Registrar}Interface.php       ← Port (abstraction infra)
├── Entity/
│   └── {Entity}.php                   ← POPO, traits HasId/HasSlug
├── Factory/
│   └── {Entity}Factory.php            ← Transformation WP → domaine
├── Repository/
│   └── {Entity}Repository.php         ← Accès données abstrait
├── Infrastructure/
│   └── WordPress{Registrar}.php       ← Adaptateur WP (seul endroit autorisé pour les appels WP)
├── DependencyInjection/
│   └── Compiler/
│       └── Register{Module}Pass.php   ← Auto-registration via tags
├── {Module}Registry.php               ← Collecte des services tagués
├── {Module}Extension.php              ← Configuration DI
├── Register{Module}.php               ← Orchestration (Application layer)
└── Tests/
    └── {Module}Test.php
```

**Règle d'or** : Les appels WordPress (`add_action`, `register_post_type`, `get_option`, etc.) ne doivent apparaître **que** dans `Infrastructure/`.
