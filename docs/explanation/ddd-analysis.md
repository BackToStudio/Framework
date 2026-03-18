# Analyse DDD approfondie du BackTo Framework

## Vue d'ensemble

Le **BackTo Framework** est un framework WordPress modulaire construit sur **Symfony Dependency Injection**, qui apporte les patterns d'**Architecture Hexagonale** et de **Domain-Driven Design (DDD)** au développement WordPress.

- **Namespace racine** : `BackTo\Framework`
- **PHP minimum** : 8.2
- **Moteur DI** : Symfony DI (scopé sous `BackToVendor\`)

---

## 1. Bounded Contexts (Contextes bornés)

Le framework est découpé en **contextes bornés** autonomes, chacun avec son propre modèle de domaine :

| Bounded Context | Répertoire | Responsabilité |
|-----------------|-----------|----------------|
| **PostType** | `src/PostType/` | Gestion des types de contenus et des posts |
| **Taxonomy** | `src/Taxonomy/` | Gestion des taxonomies et des termes |
| **PostMeta** | `src/PostMeta/` | Gestion des métadonnées de posts |
| **Queue** | `src/Queue/` | Système de files d'attente de jobs asynchrones |
| **GDPR** | `src/Gdpr/` | Consentement RGPD et scripts de tracking |
| **Security** | `src/Security/` | Sécurité (nonce, throttling, 2FA, CSP, CORS, audit) |
| **SEO** | `src/Seo/` | Référencement, balises meta, JSON-LD Schema |
| **Admin** | `src/Admin/` | Pages d'administration |
| **Blocks** | `src/Blocks/` | Blocs Gutenberg |
| **Assets** | `src/Assets/` | Gestion des ressources CSS/JS |
| **Cache** | `src/Cache/` | Stratégies de mise en cache |
| **Hooks** | `src/Hooks/` | Système d'actions/filtres WordPress |
| **RestApi** | `src/RestApi/` | Routes REST API |
| **Observability** | `src/Observability/` | Logging, health checks, performance |
| **Performance** | `src/Performance/` | Optimisations (minification, page cache) |
| **Options** | `src/Options/` | Gestion des options WordPress |
| **Cli** | `src/Cli/` | Commandes WP-CLI et génération de code |
| **Compose** | `src/Compose/` | Orchestration DI, kernel, infrastructure transversale |

### Isolation des contextes

Chaque contexte suit une **structure interne normalisée** :

```
{Module}/
├── Contracts/        # Interfaces (ports du domaine)
├── Entity/           # Entités et Value Objects
├── Factory/          # Fabriques d'agrégats
├── Repository/       # Accès aux données
├── Infrastructure/   # Adaptateurs WordPress
├── DependencyInjection/
│   └── Compiler/     # Compiler passes pour l'auto-wiring
├── Hooks/            # Hooks spécifiques au module
└── Tests/            # Tests unitaires et d'intégration
```

Les contextes communiquent via les **interfaces définies dans `Contracts/`**, jamais par référence directe aux implémentations.

---

## 2. Entités

Les entités sont des objets du domaine avec une **identité** qui persiste dans le temps.

### Post (Agrégat racine)
**Fichier** : `src/PostType/Entity/Post.php`

```php
class Post implements PostInterface
{
    use HasId;       // getId(), setId()
    use HasSlug;     // getSlug(), setSlug()
    use HasParentId; // getParentId(), setParentId()

    // Propriétés métier
    private string $title;
    private string $content;
    private string $excerpt;
    private string $status;
    private DateTimeInterface $date;
    private DateTimeInterface $modifiedDate;
    private int $authorId;
    private string $type;
    private string $mimeType;
    private int $menuOrder;
    // ...
}
```

**Analyse** : `Post` est une entité riche qui compose des traits transversaux (`HasId`, `HasSlug`, `HasParentId`). Elle représente l'agrégat racine du contexte PostType, reliant PostMeta comme entité enfant.

### Term
**Fichier** : `src/Taxonomy/Entity/Term.php`

Même structure que `Post`, utilisant les mêmes traits d'identité. Représente un terme de taxonomie avec `name`, `description`, `count`, `taxonomyName`.

### Job (Machine à états)
**Fichier** : `src/Queue/Entity/Job.php`

```php
class Job
{
    private JobStatus $status;     // Pending, Running, Completed, Failed, Cancelled
    private int $attempts;
    private ?int $maxRetries;
    private ?DateTimeInterface $scheduledAt;
    private ?string $interval;

    public function isReady(): bool { /* logique de planification */ }
    public function canRetry(): bool { /* logique de retry */ }
    public function isRecurring(): bool { /* logique de récurrence */ }
}
```

**Analyse** : `Job` est l'entité la plus riche en logique métier du framework. Elle implémente un **pattern Machine à états** avec 5 transitions de statut et contient de la logique de domaine significative (scheduling, retry, récurrence).

### PostMeta
**Fichier** : `src/PostMeta/Entity/PostMeta.php`

Entité liée à l'agrégat `Post`, représentant une paire clé-valeur de métadonnée.

### PostMetaStructure
**Fichier** : `src/PostMeta/Entity/PostMetaStructure.php`

Entité de configuration avec un **pattern Builder fluent** pour définir la structure d'un champ meta (type, validation, visibilité REST, etc.).

### PostType et Taxonomy (Entités de configuration)
**Fichiers** : `src/PostType/Entity/PostType.php`, `src/Taxonomy/Entity/Taxonomy.php`

Ce sont des **entités de configuration** qui définissent les paramètres d'enregistrement WordPress (`key`, `args`, `postTypes` pour Taxonomy).

---

## 3. Value Objects (Objets-valeur)

Les Value Objects sont des objets **immuables** identifiés par leurs attributs plutôt que par une identité.

### Objets immuables (readonly)

| Value Object | Fichier | Description |
|-------------|---------|-------------|
| `ConsentCategory` | `src/Gdpr/Entity/ConsentCategory.php` | Catégorie de consentement RGPD (readonly constructor) |
| `TrackingScript` | `src/Gdpr/Entity/TrackingScript.php` | Script de tracking (readonly constructor) |

### Enums comme Value Objects

| Enum | Fichier | Valeurs |
|------|---------|---------|
| `JobStatus` | `src/Queue/Entity/JobStatus.php` | `Pending`, `Running`, `Completed`, `Failed`, `Cancelled` |
| `MetaCompare` | `src/PostType/Repository/MetaCompare.php` | `=`, `!=`, `>`, `>=`, `<`, `<=`, `LIKE`, `NOT LIKE` |
| `SortDirection` | `src/PostType/Repository/SortDirection.php` | `ASC`, `DESC` |
| `Type` | `src/Compose/Type.php` | 6 valeurs de types de modules |

**Analyse** : Le framework utilise judicieusement les **enums PHP 8.1** comme Value Objects pour les concepts à domaine fermé. Les objets `readonly` du GDPR garantissent l'immuabilité au niveau du langage.

---

## 4. Agrégats

### Agrégat Post (PostType Context)

```
Post [Agrégat Racine]
├── PostMeta[]        [Entité enfant]
└── PostMetaStructure [Configuration]
```

- **Racine** : `Post` (identifié par `id`)
- **Invariants** : Les métadonnées sont toujours rattachées à un post
- **Repository** : `PostRepository` ne manipule que l'agrégat racine
- **Factory** : `PostFactory` construit l'agrégat à partir de `WP_Post`

### Agrégat Term (Taxonomy Context)

```
Term [Agrégat Racine]
└── Taxonomy [Configuration]
```

### Agrégat Job (Queue Context)

```
Job [Agrégat Racine, auto-contenu]
└── JobStatus [Value Object - état interne]
```

**Analyse** : Les agrégats sont relativement **plats** (peu de profondeur), ce qui est cohérent avec le domaine WordPress où les entités sont principalement des enregistrements avec métadonnées.

---

## 5. Repositories

### PostRepository
**Fichier** : `src/PostType/Repository/PostRepository.php`

```php
class PostRepository
{
    public function find(int $id): ?PostInterface;
    public function findAll(array $args = []): array;
    public function findBy(array $criteria): array;
    public function findOneBy(array $criteria): ?PostInterface;
    public function query(): PostQueryBuilder;
}
```

### PostQueryBuilder (Pattern Spécification)
**Fichier** : `src/PostType/Repository/PostQueryBuilder.php`

```php
$posts = $repository->query()
    ->postType('article')
    ->status('publish')
    ->whereMeta('featured', true, MetaCompare::EQUAL)
    ->inTaxonomyBySlugs('category', ['tech', 'design'])
    ->orderBy('date', SortDirection::DESC)
    ->limit(10)
    ->get();
```

**Analyse** : Le `PostQueryBuilder` implémente un **pattern Specification** sous forme fluente. Il traduit les critères du domaine en `WP_Query` sans exposer la mécanique WordPress au code client. C'est l'une des implémentations DDD les plus abouties du framework.

### TermRepository et TermQueryBuilder
Mêmes patterns appliqués au contexte Taxonomy.

### PostMetaRepository
**Fichier** : `src/PostMeta/Repository/PostMetaRepository.php`

Opérations CRUD sur les métadonnées, toujours dans le périmètre d'un post (agrégat racine).

---

## 6. Factories

### PostFactory
**Fichier** : `src/PostType/Factory/PostFactory.php`

```php
class PostFactory
{
    public function create(WP_Post $wpPost): PostInterface;
    public function createFromPosts(array $wpPosts): array;
}
```

**Rôle** : Transformer les objets WordPress natifs (`WP_Post`) en entités du domaine (`Post`). C'est un **Anti-Corruption Layer** qui protège le domaine de la structure de données WordPress.

### TermFactory
Même pattern pour la transformation `WP_Term` → `Term`.

### PostMetaFactory
Même pattern pour les métadonnées.

---

## 7. Architecture Hexagonale (Ports & Adaptateurs)

Le framework implémente rigoureusement l'**architecture hexagonale** :

### Ports (Interfaces)

| Port | Fichier | Rôle |
|------|---------|------|
| `PostTypeRegistrarInterface` | `src/PostType/Contracts/` | Enregistrement des post types |
| `TaxonomyRegistrarInterface` | `src/Taxonomy/Contracts/` | Enregistrement des taxonomies |
| `HookDispatcherInterface` | `src/Contracts/` | Dispatch d'actions/filtres |
| `PostInterface` | `src/PostType/Contracts/` | Contrat de l'entité Post |
| `TermInterface` | `src/Taxonomy/Contracts/` | Contrat de l'entité Term |
| `PostTypeRegistryInterface` | `src/PostType/Contracts/` | Collection de post types |
| `RegistryInterface` | `src/Contracts/` | Interface marqueur de registre |

### Adaptateurs (Infrastructure)

| Adaptateur | Fichier | Implémente |
|-----------|---------|------------|
| `WordPressPostTypeRegistrar` | `src/PostType/Infrastructure/` | `PostTypeRegistrarInterface` |
| `WordPressTaxonomyRegistrar` | `src/Taxonomy/Infrastructure/` | `TaxonomyRegistrarInterface` |
| `WordPressHookDispatcher` | `src/Hooks/Infrastructure/` | `HookDispatcherInterface` |
| `WordPressNonceManager` | `src/Security/Infrastructure/` | Interface nonce |
| `WordPressOptionsRepository` | `src/Options/` | Options WordPress |

### Diagramme de dépendances

```
┌─────────────────────────────────────────────────────┐
│                  CODE APPLICATIF                     │
│         (Themes & Plugins WordPress)                 │
│                                                      │
│  ┌─────────────────────────────────────────────┐    │
│  │            DOMAINE (Entities)                │    │
│  │  Post, Term, Job, PostMeta, ConsentCategory  │    │
│  │  PostType, Taxonomy, TrackingScript          │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │ implémente                     │
│  ┌──────────────────▼──────────────────────────┐    │
│  │         PORTS (Contracts/Interfaces)          │    │
│  │  PostTypeRegistrarInterface                   │    │
│  │  TaxonomyRegistrarInterface                   │    │
│  │  HookDispatcherInterface                      │    │
│  │  PostInterface, TermInterface                 │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │ implémenté par                  │
│  ┌──────────────────▼──────────────────────────┐    │
│  │       ADAPTATEURS (Infrastructure)            │    │
│  │  WordPressPostTypeRegistrar                   │    │
│  │  WordPressTaxonomyRegistrar                   │    │
│  │  WordPressHookDispatcher                      │    │
│  │  WordPressNonceManager                        │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │ appelle                         │
│  ┌──────────────────▼──────────────────────────┐    │
│  │          WORDPRESS (Système externe)          │    │
│  │  register_post_type(), add_action()           │    │
│  │  WP_Query, WP_Post, WP_Term                   │    │
│  └──────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────┘
```

**Analyse** : **L'inversion de dépendance est respectée** — le code domaine ne dépend jamais de WordPress. Seuls les adaptateurs dans `Infrastructure/` contiennent des appels aux fonctions WordPress natives.

---

## 8. Patterns tactiques DDD implémentés

### 8.1. Registry Pattern (Collection de domaine)

Chaque contexte possède un **Registre** qui collecte les objets de domaine enregistrés :

```
PostTypeRegistry     → collecte PostTypeInterface[]
TaxonomyRegistry     → collecte TaxonomyInterface[]
HookRegistry         → collecte HookInterface[]
BlockRegistry        → collecte BlockInterface[]
AdminPageRegistry    → collecte AdminPageInterface[]
RestRouteRegistry    → collecte RestRouteInterface[]
HealthCheckRegistry  → collecte HealthCheckInterface[]
```

### 8.2. Compiler Pass (Composition Root)

Le pattern `AbstractTaggedServiceCompilerPass` automatise l'injection des services tagués dans les registres :

```php
abstract class AbstractTaggedServiceCompilerPass implements CompilerPassInterface
{
    abstract protected function getRegistryClass(): string;
    abstract protected function getTag(): string;

    public function process(ContainerBuilder $container): void
    {
        // Trouve tous les services tagués
        // Les injecte dans le registre via add()
    }
}
```

**Implémentations** : `RegisterPostTypePass`, `RegisterHookPass`, `RegisterBlockPass`, `RegisterRestRoutePass`, `RegisterAdminPagePass`, `RegisterConsentCategoryPass`, `RegisterTrackingScriptPass`, `RegisterQueuePass`.

### 8.3. Extension Pattern (Module autonome)

```php
interface ExtensionInterface
{
    public function getBundle(): array;                    // Auto-découverte des services
    public function register(ContainerBuilder $container): void;  // Compiler passes
    public function getDefaultConfiguration(): array;       // Configuration par défaut
}
```

Chaque module fournit une extension qui s'auto-enregistre dans le conteneur DI.

### 8.4. Kernel Pattern (Application Service)

```php
AbstractKernel
├── PluginKernel    // Point d'entrée pour les plugins
└── ThemeKernel     // Point d'entrée pour les thèmes
```

Le kernel orchestre le cycle de vie complet :
1. **Chargement** des extensions
2. **Construction** du conteneur (compiler passes, auto-wiring)
3. **Compilation** et dumping du conteneur en PHP
4. **Exécution** des hooks enregistrés

### 8.5. Configurator Pattern (Configuration fluente)

```php
// config/seo.php
return static function (SeoConfigurator $seo): void {
    $seo->titleSeparator('|')->robotsDefault('index, follow');
};
```

Les configurateurs (`CacheConfigurator`, `SeoConfigurator`, `SecurityConfigurator`, etc.) offrent une API fluente typée pour la configuration des modules.

### 8.6. Anti-Corruption Layer

Les **Factories** (`PostFactory`, `TermFactory`) servent d'**Anti-Corruption Layer** entre WordPress et le domaine :

```
WP_Post ──→ PostFactory::create() ──→ Post (Entité du domaine)
WP_Term ──→ TermFactory::create() ──→ Term (Entité du domaine)
```

Cela isole le modèle de domaine des structures de données WordPress brutes.

---

## 9. Patterns stratégiques DDD

### 9.1. Shared Kernel (Noyau partagé)

Le répertoire `src/Contracts/` et `src/Compose/` forment un **Shared Kernel** utilisé par tous les contextes :

- **Traits** : `HasId`, `HasSlug`, `HasParentId`
- **Interfaces** : `IdInterface`, `SlugInterface`, `ParentIdInterface`
- **Base** : `AbstractTaggedServiceCompilerPass`, `ExtensionInterface`
- **Types** : `Type` enum

### 9.2. Context Mapping

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   PostType   │◄──►│   PostMeta   │    │   Taxonomy   │
│   Context    │    │   Context    │    │   Context    │
└──────┬───────┘    └──────────────┘    └──────┬───────┘
       │                                        │
       │         via PostQueryBuilder            │
       └──────────────────────────────────────┘
       (relation cross-context dans les queries)

┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│    Hooks     │    │   Security   │    │     GDPR     │
│   Context    │◄───┤   Context    │    │   Context    │
└──────────────┘    └──────────────┘    └──────────────┘
(Security utilise Hooks pour s'enregistrer)

┌──────────────┐    ┌──────────────┐
│   Compose    │◄───┤  All Modules │
│ (Shared Kernel)   │ (Extensions) │
└──────────────┘    └──────────────┘
```

### 9.3. Ubiquitous Language (Langage omniprésent)

Le framework définit un vocabulaire cohérent :

| Terme du domaine | Signification |
|-----------------|---------------|
| **Extension** | Module auto-enregistrable dans le conteneur DI |
| **Registry** | Collection nommée d'objets de domaine |
| **Kernel** | Point d'entrée applicatif (Plugin ou Theme) |
| **Hook** | Point d'accroche WordPress (action/filtre) |
| **Registrar** | Adaptateur qui enregistre un concept dans WordPress |
| **Bundle** | Configuration d'auto-découverte de services |
| **Compiler Pass** | Transformation du conteneur au moment de la compilation |
| **Configurator** | API fluente de configuration d'un module |

---

## 10. Forces et points d'attention

### Forces DDD

1. **Séparation claire des bounded contexts** — Chaque module est autonome avec sa propre structure Entity/Contract/Infrastructure
2. **Inversion de dépendance rigoureuse** — Le domaine ne dépend jamais de WordPress
3. **Anti-Corruption Layer** — Les Factories isolent le domaine des structures WordPress
4. **Registres typés** — Les compiler passes auto-wirent les services tagués
5. **Query Builder comme Specification** — `PostQueryBuilder` encapsule les critères de requête
6. **Immuabilité des Value Objects** — Utilisation de `readonly` et d'enums PHP 8.1
7. **Architecture hexagonale complète** — Ports (Contracts) et Adaptateurs (Infrastructure) séparés
8. **Shared Kernel bien défini** — Traits et interfaces transversaux sans couplage fort
9. **Configuration typée** — Configurators fluents par module
10. **Testabilité** — L'injection de dépendances permet le mocking de tous les adaptateurs

### Points d'attention et axes d'amélioration

1. **Absence de Domain Events** — Aucun système d'événements de domaine n'est identifié. Les hooks WordPress remplacent partiellement ce besoin, mais un `DomainEventDispatcher` enrichirait la communication inter-contextes
2. **Agrégats peu profonds** — Les agrégats sont principalement des entités isolées sans réelle protection des invariants d'agrégat (pas de méthodes de mutation contrôlée)
3. **Logique métier limitée dans les entités** — À l'exception de `Job`, les entités sont principalement des conteneurs de données (modèle anémique). `Post` et `Term` pourraient bénéficier de méthodes métier
4. **Pas de Value Objects explicites pour les concepts riches** — Des concepts comme `PostStatus`, `PostTitle`, `Email`, `Url` pourraient être encapsulés dans des Value Objects dédiés plutôt que des `string`
5. **Pas de Domain Services explicites** — La logique métier transversale n'est pas encapsulée dans des services de domaine nommés
6. **Couplage Query Builder ↔ WordPress** — Le `PostQueryBuilder` traduit directement vers `WP_Query`, ce qui couple le repository à l'infrastructure
7. **Pas d'Aggregate Root enforcement** — Rien n'empêche d'accéder à `PostMeta` sans passer par l'agrégat `Post`

---

## 11. Recommandations DDD

### Court terme

- **Introduire des Value Objects** pour `PostStatus`, `PostSlug`, `Email`, `Url`, `MetaKey`
- **Ajouter de la logique métier** dans les entités (ex : `Post::publish()`, `Post::isDraft()`, `Job::markAsCompleted()`)
- **Protéger les invariants d'agrégat** en contrôlant l'accès aux entités enfants via la racine

### Moyen terme

- **Implémenter des Domain Events** (`PostPublished`, `JobCompleted`, `ConsentGranted`) pour la communication inter-contextes
- **Créer des Domain Services** pour la logique métier qui ne relève pas d'une seule entité
- **Introduire des Specifications** nommées réutilisables pour les critères de requête communs

### Long terme

- **Séparer le modèle de lecture/écriture** (CQRS) pour les contextes complexes comme Security et Queue
- **Ajouter un Event Store** pour les contextes nécessitant un historique (Audit, GDPR)
- **Module Saga/Process Manager** pour orchestrer les workflows multi-contextes

---

## 12. Conclusion

Le BackTo Framework implémente une **Architecture Hexagonale solide** avec des éléments DDD tactiques bien maîtrisés (Entities, Value Objects, Repositories, Factories, Anti-Corruption Layer). La séparation en bounded contexts via le système d'Extensions est exemplaire pour un framework WordPress.

Les principaux axes de progression DDD se situent au niveau de l'enrichissement du modèle de domaine (Domain Events, Domain Services, Value Objects riches) et de la protection des invariants d'agrégat. Le framework pose néanmoins une **base architecturale de très haute qualité** qui rend ces évolutions naturelles et incrémentales.
