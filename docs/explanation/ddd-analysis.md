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
| **Query** | `src/Query/` | Enums partagés pour les requêtes (MetaCompare, SortDirection) |
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
├── Specification/    # Critères de requête nommés
├── ValueObject/      # Objets-valeur immuables
├── Infrastructure/   # Adaptateurs WordPress
├── DependencyInjection/
│   └── Compiler/     # Compiler passes pour l'auto-wiring
├── Hooks/            # Hooks spécifiques au module
└── Tests/            # Tests unitaires et d'intégration
```

Les contextes communiquent via les **interfaces définies dans `Contracts/`**, jamais par référence directe aux implémentations.

#### Anti-Corruption Layer inter-contextes

Le contexte PostMeta définit `PostReferenceInterface` — une vue minimale de ce dont il a besoin d'un Post — pour éviter toute dépendance vers le contexte PostType :

```php
// src/PostMeta/Contracts/PostReferenceInterface.php
interface PostReferenceInterface
{
    public function getId(): ?int;
}
```

Les enums partagés (`MetaCompare`, `SortDirection`) vivent dans le namespace `Query` — un **Shared Kernel** léger — plutôt que dans un contexte spécifique, évitant les dépendances circulaires entre PostType et Taxonomy.

---

## 2. Entités

Les entités sont des objets du domaine avec une **identité** qui persiste dans le temps.

### Post (Agrégat racine)
**Fichier** : `src/PostType/Entity/Post.php`

```php
class Post implements PostInterface
{
    use HasId;       // getId(), setId()
    use HasSlug;     // getSlug(), setSlug() — accepte Slug|string
    use HasParentId; // getParentId(), setParentId()

    private PostStatus|string $status;     // Value Object enum
    private string $title;
    private string $content;
    private string $excerpt;
    // ...
}
```

`Post` est l'agrégat racine du contexte PostType. Il utilise le Value Object `PostStatus` pour le statut et accepte `Slug|string` pour le slug (rétrocompatibilité).

### Term
**Fichier** : `src/Taxonomy/Entity/Term.php`

Même structure que `Post`, utilisant les mêmes traits d'identité. Représente un terme de taxonomie avec `name`, `description`, `count`, `taxonomyName`.

### Job (Machine à états avec invariants)
**Fichier** : `src/Queue/Entity/Job.php`

```php
final class Job
{
    private JobStatus $status;
    private int $attempts;
    private int $maxRetries;

    // Guards — protègent les invariants
    public function setAttempts(int $attempts): self;     // rejette < 0
    public function setMaxRetries(int $maxRetries): self; // rejette < 0
    public function setIntervalSeconds(int $s): self;     // rejette < 0

    // Domain methods — transitions d'état contrôlées
    public function markAsRunning(string $claimToken): self;  // Pending|Failed → Running
    public function markAsCompleted(): self;                   // Running → Completed
    public function markAsFailed(string $error): self;         // Running → Failed
    public function cancel(): self;                            // !Completed → Cancelled
    public function reschedule(\DateTimeImmutable $at): self;  // Completed|Failed → Pending
    public function incrementAttempts(): self;

    // Query methods
    public function isReady(): bool;
    public function canRetry(): bool;
    public function isRecurring(): bool;
}
```

`Job` est l'entité la plus riche du framework : machine à états avec 5 statuts, invariants protégés par guards, et méthodes de domaine expressives qui encapsulent les transitions d'état autorisées.

### PostMeta
**Fichier** : `src/PostMeta/Entity/PostMeta.php`

Entité liée à l'agrégat `Post`, représentant une paire clé-valeur de métadonnée. Utilise le Value Object `MetaKey` pour sa clé.

### PostMetaStructure
**Fichier** : `src/PostMeta/Entity/PostMetaStructure.php`

Entité de configuration avec un **pattern Builder fluent** pour définir la structure d'un champ meta (type, validation, visibilité REST, etc.).

### PostType et Taxonomy (Entités de configuration)
**Fichiers** : `src/PostType/Entity/PostType.php`, `src/Taxonomy/Entity/Taxonomy.php`

Ce sont des **entités de configuration** qui définissent les paramètres d'enregistrement WordPress. `Taxonomy` valide que `addPostType()` reçoit une clé non vide.

---

## 3. Value Objects (Objets-valeur)

Les Value Objects sont des objets **immuables** identifiés par leurs attributs plutôt que par une identité.

### Value Objects readonly

| Value Object | Fichier | Validation | API |
|-------------|---------|------------|-----|
| `Slug` | `src/Compose/ValueObject/Slug.php` | Pas d'espaces | `fromString()`, `equals()`, `isEmpty()` |
| `MetaKey` | `src/PostMeta/ValueObject/MetaKey.php` | Non vide | `fromString()`, `equals()`, `isProtected()` |
| `ConsentCategory` | `src/Gdpr/Entity/ConsentCategory.php` | Constructor readonly | — |
| `TrackingScript` | `src/Gdpr/Entity/TrackingScript.php` | Location valide | — |

Exemple d'utilisation :

```php
$slug = Slug::fromString('mon-article');
$slug->isEmpty();  // false
$slug->equals(Slug::fromString('mon-article')); // true

$key = MetaKey::fromString('_thumbnail_id');
$key->isProtected(); // true (commence par _)
```

### Enums comme Value Objects

| Enum | Fichier | Valeurs | Méthodes |
|------|---------|---------|----------|
| `PostStatus` | `src/PostType/Entity/PostStatus.php` | `Publish`, `Draft`, `Pending`, `Private`, `Trash`, `AutoDraft`, `Inherit`, `Future` | `isPublic()`, `isEditable()`, `isViewable()` |
| `JobStatus` | `src/Queue/Entity/JobStatus.php` | `Pending`, `Running`, `Completed`, `Failed`, `Cancelled` | — |
| `MetaCompare` | `src/Query/MetaCompare.php` | `EQUAL`, `NOT_EQUAL`, `GREATER_THAN`, `LIKE`, `IN`, `EXISTS`, etc. | — |
| `SortDirection` | `src/Query/SortDirection.php` | `ASC`, `DESC` | — |
| `Type` | `src/Compose/Type.php` | 6 valeurs de types de modules | — |

`PostStatus` enrichit l'enum avec de la **logique de domaine** :

```php
PostStatus::Publish->isPublic();    // true
PostStatus::Draft->isEditable();    // true
PostStatus::Trash->isViewable();    // false
```

### Rétrocompatibilité

Les setters acceptent les union types `PostStatus|string`, `Slug|string`, `MetaKey|string` pour ne pas casser le code existant.

---

## 4. Specifications (Critères de requête)

Le framework implémente le **Specification Pattern** pour composer des critères de requête nommés et réutilisables.

### PostSpecification

```php
interface PostSpecification
{
    public function apply(PostQueryBuilder $builder): PostQueryBuilder;
}
```

| Specification | Paramètres | Comportement |
|---------------|------------|-------------|
| `PublishedPosts` | — | `status(Publish)` |
| `RecentPosts` | `int $limit = 10` | Published + tri par date DESC + limit |
| `PostsByAuthor` | `int $authorId` | Filtre par auteur |
| `PostsByStatus` | `PostStatus $status` | Filtre par statut |
| `PostsByType` | `string $postType` | Filtre par type de contenu |
| `PostsInTaxonomy` | `string $taxonomy, int[] $termIds` | Filtre par termes de taxonomie |
| `PostsWithMeta` | `MetaKey\|string $key, mixed $value, MetaCompare` | Filtre par méta |
| `AndPostSpecification` | `PostSpecification ...$specs` | Composite AND |

### TermSpecification

```php
interface TermSpecification
{
    public function apply(TermQueryBuilder $builder): TermQueryBuilder;
}
```

| Specification | Comportement |
|---------------|-------------|
| `TermsInTaxonomy` | Filtre par taxonomie |
| `TopLevelTerms` | Termes sans parent |
| `NonEmptyTerms` | Termes avec au moins un post |
| `AndTermSpecification` | Composite AND |

### Composition

Les specifications se composent via `AndPostSpecification` :

```php
$spec = new AndPostSpecification(
    new PublishedPosts(),
    new PostsByType('article'),
    new PostsInTaxonomy('category', [12, 34]),
    new RecentPosts(5),
);

$posts = $repository->query()->matching($spec)->get();
```

---

## 5. Agrégats

### Agrégat Post (PostType Context)

```
Post [Agrégat Racine]
├── PostStatus          [Value Object — statut]
├── Slug                [Value Object — identifiant URL]
├── PostMeta[]          [Entité enfant]
└── PostMetaStructure   [Configuration]
```

- **Racine** : `Post` (identifié par `id`)
- **Invariants** : Les métadonnées sont toujours rattachées à un post. Le statut est validé via l'enum `PostStatus`.
- **Repository** : `PostRepository` ne manipule que l'agrégat racine
- **Factory** : `PostFactory` construit l'agrégat à partir de `WP_Post` (Anti-Corruption Layer)

### Agrégat Term (Taxonomy Context)

```
Term [Agrégat Racine]
└── Taxonomy [Configuration — valide les clés de post types]
```

### Agrégat Job (Queue Context)

```
Job [Agrégat Racine, auto-contenu]
├── JobStatus              [Value Object — état interne]
├── Guards                 [attempts ≥ 0, maxRetries ≥ 0, intervalSeconds ≥ 0]
└── State Machine          [transitions contrôlées par domain methods]
```

**Transitions d'état autorisées** :

```
Pending ──markAsRunning()──→ Running
Failed  ──markAsRunning()──→ Running
Running ──markAsCompleted()──→ Completed
Running ──markAsFailed()──→ Failed
*       ──cancel()──→ Cancelled (sauf Completed)
Completed|Failed ──reschedule()──→ Pending
```

---

## 6. Repositories

### PostRepository
**Fichier** : `src/PostType/Infrastructure/WordPressPostRepository.php`

```php
class WordPressPostRepository implements PostRepositoryInterface
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
    ->status(PostStatus::Publish)
    ->whereMeta('featured', true, MetaCompare::EQUAL)
    ->inTaxonomyBySlugs('category', ['tech', 'design'])
    ->orderBy('date', SortDirection::DESC)
    ->limit(10)
    ->matching(new PublishedPosts())  // accepte PostSpecification
    ->get();
```

Le `PostQueryBuilder` traduit les critères du domaine en `WP_Query` sans exposer la mécanique WordPress au code client.

### TermRepository et TermQueryBuilder
Mêmes patterns appliqués au contexte Taxonomy, avec support des `TermSpecification`.

### PostMetaRepository
**Fichier** : `src/PostMeta/Infrastructure/WordPressPostMetaRepository.php`

Opérations CRUD sur les métadonnées, utilisant `MetaKey` pour les clés. Toujours dans le périmètre d'un post (agrégat racine).

---

## 7. Factories

### PostFactory
**Fichier** : `src/PostType/Factory/PostFactory.php`

```php
class PostFactory
{
    public function create(WP_Post $wpPost): PostInterface;
    public function createFromPosts(array $wpPosts): array;
}
```

**Rôle** : Transformer les objets WordPress natifs (`WP_Post`) en entités du domaine (`Post`). C'est un **Anti-Corruption Layer** qui protège le domaine de la structure de données WordPress. La factory utilise `PostStatus::tryFrom()` pour convertir les strings WordPress en Value Objects.

### TermFactory
Même pattern pour la transformation `WP_Term` → `Term`.

### PostMetaFactory
Même pattern pour les métadonnées, avec conversion en `MetaKey`.

---

## 8. Architecture Hexagonale (Ports & Adaptateurs)

Le framework implémente rigoureusement l'**architecture hexagonale** :

### Ports (Interfaces)

| Port | Fichier | Rôle |
|------|---------|------|
| `PostTypeRegistrarInterface` | `src/PostType/Contracts/` | Enregistrement des post types |
| `TaxonomyRegistrarInterface` | `src/Taxonomy/Contracts/` | Enregistrement des taxonomies |
| `HookDispatcherInterface` | `src/Contracts/` | Dispatch d'actions/filtres |
| `PostInterface` | `src/PostType/Contracts/` | Contrat de l'entité Post |
| `TermInterface` | `src/Taxonomy/Contracts/` | Contrat de l'entité Term |
| `PostReferenceInterface` | `src/PostMeta/Contracts/` | Vue minimale d'un Post pour PostMeta (ACL) |

### Adaptateurs (Infrastructure)

| Adaptateur | Fichier | Implémente |
|-----------|---------|------------|
| `WordPressPostTypeRegistrar` | `src/PostType/Infrastructure/` | `PostTypeRegistrarInterface` |
| `WordPressTaxonomyRegistrar` | `src/Taxonomy/Infrastructure/` | `TaxonomyRegistrarInterface` |
| `WordPressHookDispatcher` | `src/Hooks/Infrastructure/` | `HookDispatcherInterface` |
| `WordPressPostRepository` | `src/PostType/Infrastructure/` | `PostRepositoryInterface` |
| `WordPressPostMetaRepository` | `src/PostMeta/Infrastructure/` | `PostMetaRepositoryInterface` |
| `WordPressTermRepository` | `src/Taxonomy/Infrastructure/` | `TermRepositoryInterface` |

### Diagramme de dépendances

```
┌─────────────────────────────────────────────────────┐
│                  CODE APPLICATIF                     │
│         (Themes & Plugins WordPress)                 │
│                                                      │
│  ┌─────────────────────────────────────────────┐    │
│  │            DOMAINE (Entities)                │    │
│  │  Post, Term, Job, PostMeta, ConsentCategory  │    │
│  │  Value Objects: PostStatus, Slug, MetaKey    │    │
│  │  Specifications: PublishedPosts, RecentPosts │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │ implémente                     │
│  ┌──────────────────▼──────────────────────────┐    │
│  │         PORTS (Contracts/Interfaces)          │    │
│  │  PostRepositoryInterface                      │    │
│  │  PostMetaRepositoryInterface                  │    │
│  │  PostReferenceInterface (ACL)                 │    │
│  │  PostSpecification, TermSpecification         │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │ implémenté par                  │
│  ┌──────────────────▼──────────────────────────┐    │
│  │       ADAPTATEURS (Infrastructure)            │    │
│  │  WordPressPostRepository                      │    │
│  │  WordPressPostMetaRepository                  │    │
│  │  WordPressTermRepository                      │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │ appelle                         │
│  ┌──────────────────▼──────────────────────────┐    │
│  │          WORDPRESS (Système externe)          │    │
│  │  register_post_type(), add_action()           │    │
│  │  WP_Query, WP_Post, WP_Term                   │    │
│  └──────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────┘
```

---

## 9. Patterns tactiques DDD implémentés

### 9.1. Registry Pattern (Collection de domaine)

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

### 9.2. Specification Pattern (Critères nommés)

Les Specifications encapsulent des critères de requête réutilisables et composables :

```php
// Specification simple
$spec = new PublishedPosts();

// Composition via AND
$spec = new AndPostSpecification(
    new PostsByType('article'),
    new PostsInTaxonomy('category', [12]),
    new RecentPosts(5),
);

// Application via le QueryBuilder
$posts = $repository->query()->matching($spec)->get();
```

Chaque Specification est une classe `final` avec des dépendances injectées via le constructeur. Les composites (`AndPostSpecification`, `AndTermSpecification`) permettent de combiner les critères sans couplage.

### 9.3. Compiler Pass (Composition Root)

Le pattern `AbstractTaggedServiceCompilerPass` automatise l'injection des services tagués dans les registres.

### 9.4. Extension Pattern (Module autonome)

```php
interface ExtensionInterface
{
    public function getBundle(): array;
    public function register(ContainerBuilder $container): void;
    public function getDefaultConfiguration(): array;
}
```

### 9.5. Kernel Pattern (Application Service)

```php
AbstractKernel
├── PluginKernel    // Point d'entrée pour les plugins
└── ThemeKernel     // Point d'entrée pour les thèmes
```

### 9.6. Configurator Pattern (Configuration fluente)

```php
return static function (SeoConfigurator $seo): void {
    $seo->titleSeparator('|')->robotsDefault('index, follow');
};
```

### 9.7. Anti-Corruption Layer

Les **Factories** (`PostFactory`, `TermFactory`) et les **interfaces minimales** (`PostReferenceInterface`) servent d'Anti-Corruption Layer :

```
WP_Post ──→ PostFactory::create() ──→ Post (avec PostStatus Value Object)
PostType ──PostReferenceInterface──→ PostMeta (sans dépendance directe)
```

---

## 10. Patterns stratégiques DDD

### 10.1. Shared Kernel (Noyau partagé)

Deux niveaux de partage :

| Namespace | Contenu | Utilisé par |
|-----------|---------|-------------|
| `Compose` | Traits (`HasId`, `HasSlug`, `HasParentId`), Value Objects (`Slug`), `AbstractKernel` | Tous les contextes |
| `Query` | `MetaCompare`, `SortDirection` | PostType, Taxonomy, PostMeta |
| `Contracts` | `HookDispatcherInterface`, `RegistryInterface` | Tous les contextes |

### 10.2. Context Mapping

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   PostType   │◄──►│   PostMeta   │    │   Taxonomy   │
│   Context    │    │   Context    │    │   Context    │
└──────┬───────┘    └──────────────┘    └──────┬───────┘
       │     PostReferenceInterface (ACL)       │
       │                                        │
       │          via Query (Shared Kernel)     │
       └──────────────────────────────────────┘
       (MetaCompare, SortDirection partagés)

┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│    Hooks     │    │   Security   │    │     GDPR     │
│   Context    │◄───┤   Context    │    │   Context    │
└──────────────┘    └──────────────┘    └──────────────┘

┌──────────────┐    ┌──────────────┐
│   Compose    │◄───┤  All Modules │
│ (Shared Kernel)   │ (Extensions) │
└──────────────┘    └──────────────┘
```

### 10.3. Ubiquitous Language (Langage omniprésent)

| Terme du domaine | Signification |
|-----------------|---------------|
| **Extension** | Module auto-enregistrable dans le conteneur DI |
| **Registry** | Collection nommée d'objets de domaine |
| **Kernel** | Point d'entrée applicatif (Plugin ou Theme) |
| **Hook** | Point d'accroche WordPress (action/filtre) |
| **Registrar** | Adaptateur qui enregistre un concept dans WordPress |
| **Specification** | Critère de requête nommé, composable et réutilisable |
| **Value Object** | Objet immuable identifié par sa valeur, avec validation |
| **Guard** | Validation d'invariant dans un setter d'entité |

---

## 11. Forces et points d'attention

### Forces DDD

1. **Séparation claire des bounded contexts** — Chaque module est autonome avec sa propre structure Entity/Contract/Infrastructure
2. **Inversion de dépendance rigoureuse** — Le domaine ne dépend jamais de WordPress
3. **Anti-Corruption Layer** — Factories + `PostReferenceInterface` isolent les contextes
4. **Value Objects riches** — `PostStatus` avec logique métier, `Slug` et `MetaKey` avec validation
5. **Specification Pattern** — Critères de requête nommés, testables et composables
6. **Invariants protégés** — Guards dans `Job`, validation dans `Taxonomy`, transitions d'état contrôlées
7. **Architecture hexagonale complète** — Ports (Contracts) et Adaptateurs (Infrastructure) séparés
8. **Shared Kernel bien défini** — `Compose` pour les traits, `Query` pour les enums partagés
9. **Configuration typée** — Configurators fluents par module
10. **Testabilité** — L'injection de dépendances permet le mocking de tous les adaptateurs

### Points d'attention et axes d'amélioration

1. **Absence de Domain Events** — Aucun système d'événements de domaine n'est identifié. Les hooks WordPress remplacent partiellement ce besoin, mais un `DomainEventDispatcher` enrichirait la communication inter-contextes
2. **Pas de Domain Services explicites** — La logique métier transversale n'est pas encapsulée dans des services de domaine nommés
3. **Couplage Query Builder ↔ WordPress** — Le `PostQueryBuilder` traduit directement vers `WP_Query`, ce qui couple le repository à l'infrastructure

---

## 12. Recommandations DDD

### Court terme

- **Implémenter des Domain Events** (`PostPublished`, `JobCompleted`, `ConsentGranted`) pour la communication inter-contextes
- **Créer des Domain Services** pour la logique métier qui ne relève pas d'une seule entité

### Moyen terme

- **Séparer le modèle de lecture/écriture** (CQRS) pour les contextes complexes comme Security et Queue
- **Ajouter un Event Store** pour les contextes nécessitant un historique (Audit, GDPR)

### Long terme

- **Module Saga/Process Manager** pour orchestrer les workflows multi-contextes

---

## 13. Conclusion

Le BackTo Framework implémente une **Architecture Hexagonale solide** avec des patterns DDD tactiques matures : Entities avec invariants protégés, Value Objects riches (`PostStatus`, `Slug`, `MetaKey`), Repositories avec Specification Pattern, Factories comme Anti-Corruption Layer, et Bounded Contexts isolés via interfaces et Shared Kernel.

Les principaux axes de progression se situent au niveau des Domain Events et Domain Services. Le framework pose une **base architecturale de très haute qualité** qui rend ces évolutions naturelles et incrémentales.
