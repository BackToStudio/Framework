# Analyse SRP, DDD & Clean Architecture — BackTo Framework 4.0.0

**Date :** 2026-03-20
**Branche :** 4.0.0
**PHP minimum :** 8.2+

---

## 1. Vue d'ensemble architecturale

Le framework est organisé en **3 couches** respectant le principe d'inversion de dépendances :

```
┌─────────────────────────────────────────────────────────────┐
│  APPLICATION BUNDLES (src/Bundle/)                          │
│  Security, Performance, Seo, Admin, Theme, Plugin,         │
│  Blocks, Gdpr                                              │
├─────────────────────────────────────────────────────────────┤
│  DOMAIN SERVICES (src/)                                     │
│  PostType, Taxonomy, PostMeta, RestApi, Options, Assets,    │
│  Queue, Observability                                       │
├─────────────────────────────────────────────────────────────┤
│  FOUNDATION (src/)                                          │
│  Contracts, Http, Cache, Clock, Exception, Query, Hooks,    │
│  Compose, Cli                                               │
└─────────────────────────────────────────────────────────────┘
```

**Pattern architectural** : Hexagonal (Ports & Adapters) avec chaque module structuré en :
- `Contracts/` — Ports (interfaces)
- `Infrastructure/` — Adaptateurs WordPress
- `Entity/` — Objets du domaine
- `Factory/` — Création d'objets
- `Repository/` — Accès aux données
- `DependencyInjection/` — Configuration DI (Extensions + Compiler Passes)
- `Tests/` — Tests unitaires

---

## 2. Analyse SRP (Single Responsibility Principle)

### 2.1 Score par module

| Module | SRP | Constat |
|--------|:---:|---------|
| Hooks | **A** | 1 classe = 1 responsabilité, délégation propre |
| Options | **A** | Interface minimale, focus unique |
| Plugin | **A** | Lifecycle hooks uniquement |
| Theme | **A** | Configuration thème uniquement |
| Blocks | **A** | Registration pattern propre |
| CLI | **A** | Commandes isolées, generators |
| GDPR | **A** | Entités `final readonly`, presets bien découpés |
| Observability | **A** | Decorator pattern (ObservableHookDispatcher), séparation logging/metrics/health |
| Queue | **A-** | `RegisterQueue` bien refactoré : délègue à `QueueProcessor` et `QueueMaintenance`. Reste le scheduling cron mélangé au lifecycle |
| Cache | **B+** | Strategies bien séparées (Filesystem, Memory, Transient). Nommage WordPress-spécifique sur les interfaces |
| Admin | **B+** | Registre + Compiler Pass propres |
| RestApi | **B** | `RegisterRestRoute` gère registration + permission checking |
| Assets | **B-** | `ReplaceImgTagBySvgTag` mélange parsing HTML + création SVG + factory |
| PostType | **B-** | `RegisterPostType` gère hooks + activation + registry + factory + logging |
| Security | **C+** | `AuditLogCsvExporter` : export + headers HTTP. `AdminUrlObfuscation` : rewriting + 404 + cookie management |
| Performance | **C+** | `ServePageCache` : serving + buffering + capture + cacheability + résolution URL (atténué par composition) |
| Seo | **C+** | `RegisterDefaultSchemas` : registration + condition checking + factory |

### 2.2 Violations SRP détaillées

#### `ServePageCache` — 3 responsabilités résiduelles
**Fichier :** `src/Bundle/Performance/Hooks/Cache/ServePageCache.php`

Bien que refactoré avec `CacheableRequestChecker` et `RequestUrlResolver`, la classe conserve :
1. **Servir** les pages cachées (avec `header()` + `exit` en dur ligne 73-75)
2. **Capturer** le HTML via output buffering (`ob_start`)
3. **Stocker** le résultat dans le cache

```php
// Violation : appel direct à header() au lieu de ResponseEmitterInterface
header('X-Page-Cache: HIT');
echo $html;
exit;
```

**Recommandation :** Injecter `ResponseEmitterInterface` (déjà existant dans `Contracts/`) au lieu d'appeler `header()` directement.

#### `AuditLogCsvExporter` — 2 responsabilités
**Fichier :** `src/Bundle/Security/Audit/AuditLogCsvExporter.php`

1. Génère le contenu CSV
2. Envoie les headers HTTP (`Content-Type`, `Content-Disposition`)

**Recommandation :** Séparer la génération CSV du transport HTTP. La classe devrait retourner un `Response` ou un `StreamInterface`.

#### `RegisterPostType` — lifecycle + registration
**Fichier :** `src/PostType/RegisterPostType.php`

Gère simultanément :
1. Enregistrement des hooks WordPress
2. Activation (flush rewrite rules)
3. Orchestration du registre + factory + logging

**Recommandation :** Extraire `PostTypeActivationHandler` pour le lifecycle.

---

## 3. Analyse DDD (Domain-Driven Design)

### 3.1 Cartographie tactique

| Pattern DDD | Implémentation | Score | Commentaire |
|-------------|---------------|:-----:|-------------|
| **Entités** | `Post`, `Term`, `Job` | **B** | `Job` est riche (transitions d'état, invariants). `Post` a de la logique (`isPublished`, `hasContent`). `Term` reste anémique |
| **Value Objects** | `PostStatus` (enum), `JobStatus` (enum), `MetaCompare`, `SortDirection`, `Slug` | **C+** | Enums bien utilisés mais manque `JobKey`, `IPAddress`, `CIDR`, `MetaKey`, `CacheKey` |
| **Aggregate Roots** | Implicites (Post → PostMeta) | **D** | Non formalisés. Pas de protection des invariants d'agrégat |
| **Repositories** | `PostRepositoryInterface`, `TermRepositoryInterface` | **B** | Interfaces propres, mais QueryBuilders appellent WordPress directement |
| **Factories** | `PostFactory`, `TermFactory`, `JobFactory` | **B+** | Hydratation WP → Domain bien gérée |
| **Domain Events** | Absents | **D** | Aucun événement de domaine (ex: `PostPublished`, `JobCompleted`) |
| **Specifications** | `TermSpecification`, `PostSpecification` | **A** | Pattern bien implémenté avec `apply()` |
| **Domain Services** | `QueueProcessor`, `QueueMaintenance` | **B+** | Bonne séparation des préoccupations |
| **Ports/Adapters** | `Contracts/` + `Infrastructure/` | **A** | Pattern systématiquement appliqué |

### 3.2 Analyse des entités

#### `Job` (Queue) — **Entité riche : B+**
`src/Queue/Entity/Job.php` — 344 lignes

**Points forts :**
- Transitions d'état explicites : `markAsRunning()`, `markAsCompleted()`, `markAsFailed()`, `cancel()`, `reschedule()`
- Invariants protégés : impossible de compléter un job non-running, d'annuler un job complété
- Validation sur les setters numériques (`attempts >= 0`, `maxRetries >= 0`, `intervalSeconds >= 0`)
- Méthodes de requête : `isRecurring()`, `isReady()`, `canRetry()`

**Points faibles :**
- Setters publics ouverts sur `key`, `group`, `payload`, `claimToken` sans validation
- Pas de constructeur statique avec factory obligatoire
- `new \DateTimeImmutable('now')` créé dans l'entité elle-même au lieu d'utiliser `ClockInterface`

```php
// Problème : couplage temporel dans l'entité
$this->claimedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

// Recommandé : injection du clock
public function markAsRunning(string $claimToken, \DateTimeImmutable $at): self
```

#### `Post` (PostType) — **Entité semi-riche : B-**
`src/PostType/Entity/Post.php` — 178 lignes

**Points forts :**
- Logique métier présente : `isPublished()`, `isDraft()`, `isTrashed()`, `hasBeenModifiedAfterPublication()`, `hasContent()`, `hasExcerpt()`
- Utilise l'enum `PostStatus` avec délégation (`$status->isEditable()`)
- Classe `final`

**Points faibles :**
- Propriétés `protected` au lieu de `private` (fuite d'encapsulation malgré `final`)
- Setters sans validation (titre vide accepté, postType vide accepté)
- Pas de protection des invariants (un post publié peut avoir son contenu vidé)

```php
// Problème : setter sans validation
public function setPostType(string $postType): PostInterface
{
    $this->postType = $postType; // accepte chaîne vide
    return $this;
}

// Recommandé :
public function setPostType(string $postType): PostInterface
{
    if ($postType === '') {
        throw new \InvalidArgumentException('Post type cannot be empty.');
    }
    $this->postType = $postType;
    return $this;
}
```

#### `Term` (Taxonomy) — **Entité anémique : D+**

Aucune logique métier. Conteneur de données pur avec getters/setters.

### 3.3 Absence d'Aggregate Roots formalisés

La relation `Post` → `PostMeta` constitue un agrégat naturel, mais :
- `PostMeta` peut être modifié indépendamment de `Post`
- Pas de méthode `Post::addMeta()` ou `Post::removeMeta()`
- Les repositories sont séparés sans transactionnalité

**Recommandation :**
```php
// Post comme Aggregate Root
final class Post implements PostInterface
{
    /** @var PostMeta[] */
    private array $metadata = [];

    public function setMeta(string $key, mixed $value): self { /* ... */ }
    public function getMeta(string $key): mixed { /* ... */ }
    public function removeMeta(string $key): self { /* ... */ }
}
```

### 3.4 Value Objects manquants

| Value Object | Type actuel | Module | Bénéfice |
|-------------|-------------|--------|----------|
| `JobKey` | `string` | Queue | Validation format, immutabilité |
| `QueueGroup` | `string` | Queue | Valeurs autorisées |
| `IPAddress` | `string` | Security | Validation IPv4/IPv6 |
| `CIDR` | `string` | Security | Validation réseau |
| `MetaKey` | `string` | PostMeta | Validation format WordPress |
| `CacheKey` | `string` | Cache | Longueur max, caractères autorisés |
| `PostSlug` | `string` | PostType | Validation slug WordPress |

### 3.5 Domain Events absents

Aucun événement de domaine n'est émis. Candidats prioritaires :

```php
// Exemples de Domain Events à implémenter
final readonly class JobCompleted {
    public function __construct(
        public readonly int $jobId,
        public readonly string $key,
        public readonly \DateTimeImmutable $completedAt,
    ) {}
}

final readonly class PostPublished {
    public function __construct(
        public readonly int $postId,
        public readonly \DateTimeImmutable $publishedAt,
    ) {}
}
```

---

## 4. Analyse Clean Architecture

### 4.1 Respect de la règle de dépendance

```
La règle : les dépendances pointent toujours vers l'intérieur (Infrastructure → Domain → Contracts)
```

| Vérification | Statut | Détail |
|-------------|--------|--------|
| Foundation ne dépend pas de Domain Services | **OK** | Aucune violation détectée |
| Foundation ne dépend pas des Bundles | **OK** | Aucune violation détectée |
| Domain Services ne dépendent pas des Bundles | **OK** | Aucune violation détectée |
| Contracts ne dépendent de rien | **OK** | Module pur d'interfaces |
| Infrastructure isolée dans `Infrastructure/` | **Partiel** | QueryBuilders appellent WP directement |
| Pas de superglobales dans le domaine | **OK** | `$_SERVER`/`$_POST` isolés dans `SuperglobalRequestContext` (Http/Infrastructure) |
| Pas de `header()` dans le domaine | **Partiel** | `ServePageCache` et `AuditLogCsvExporter` appellent `header()` |

### 4.2 Points forts de l'architecture

#### Port/Adapter exemplaire
Le framework définit **32 interfaces** dans `src/Contracts/` servant de ports :

```
RequestContextInterface   → SuperglobalRequestContext (adapter)
ResponseEmitterInterface  → NativeResponseEmitter (adapter)
UserContextInterface      → WordPressUserContext (adapter)
HookDispatcherInterface   → WordPressHookDispatcher (adapter)
ContentQueryInterface     → WordPressContentQuery (adapter)
QueryContextInterface     → WordPressQueryContext (adapter)
```

Ces abstractions sont **bien conçues** et permettent de tester le code métier sans WordPress.

#### DI Container bien structuré
- Symfony DI vendor-scoped (pas de conflit avec d'autres plugins)
- Extensions par module (`CacheExtension`, `SecurityExtension`, etc.)
- Compiler Passes pour l'assemblage (`RegisterPostTypePass`, `RegisterBlockPass`, etc.)
- Auto-wiring et auto-configuration activés

#### PSR compliance forte
PSR-3, PSR-4, PSR-7, PSR-11, PSR-16, PSR-18, PSR-20 — tous implémentés correctement.

### 4.3 Violations détectées

#### 4.3.1 QueryBuilders couplés à WordPress (CRITIQUE)

Les `QueryBuilder` dans le domaine appellent directement les fonctions WordPress :

```php
// src/Taxonomy/Repository/TermQueryBuilder.php:146
use function get_terms;
$wpTerms = get_terms(array_merge($defaults, $this->args));

// src/PostType/Repository/PostQueryBuilder.php
use function get_posts;
$wpPosts = get_posts(array_merge($defaults, $this->args));
```

**Problème :** Ces classes sont dans le namespace `Repository` (couche domaine) mais font des appels directs à l'infrastructure WordPress.

**Correction :** Déplacer les QueryBuilders dans `Infrastructure/` ou introduire un `WordPressQueryGatewayInterface` :

```php
// Port dans Contracts/
interface PostQueryGatewayInterface
{
    /** @return \WP_Post[] */
    public function queryPosts(array $args): array;
}

// Adapter dans Infrastructure/
final class WordPressPostQueryGateway implements PostQueryGatewayInterface
{
    public function queryPosts(array $args): array
    {
        return get_posts($args);
    }
}
```

#### 4.3.2 `header()` et `exit` dans un Bundle (HIGH)

```php
// src/Bundle/Performance/Hooks/Cache/ServePageCache.php:73-75
header('X-Page-Cache: HIT');
echo $html;
exit;
```

`ResponseEmitterInterface` existe déjà dans `Contracts/` mais n'est pas utilisé ici.

```php
// src/Bundle/Security/Audit/AuditLogCsvExporter.php:78-79
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="audit-log-...');
```

#### 4.3.3 Nommage WordPress dans les Contracts (MEDIUM)

```php
// Terminologie WordPress dans des interfaces censées être agnostiques
TransientStoreInterface    → devrait être CacheStoreInterface
TransientCleanerInterface  → devrait être CacheCleanerInterface
```

#### 4.3.4 Cross-module coupling résolu partiellement

Le couplage `Taxonomy → PostType` pour `MetaCompare` et `SortDirection` a été **corrigé** :
ces types sont maintenant dans `src/Query/` (Foundation), ce qui est la bonne approche.

```php
// src/Taxonomy/Repository/TermQueryBuilder.php:7-8
use BackTo\Framework\Query\MetaCompare;     // ✓ Foundation
use BackTo\Framework\Query\SortDirection;   // ✓ Foundation
```

### 4.4 Matrice de conformité par couche

| Couche | Isolation | SRP | DDD | Clean Arch | Global |
|--------|:---------:|:---:|:---:|:----------:|:------:|
| **Foundation** | A | A | N/A | A | **A** |
| **Domain Services** | B | B | C+ | B | **B** |
| **Application Bundles** | B- | B- | C | B- | **B-** |

---

## 5. Synthèse et recommandations

### 5.1 Ce qui fonctionne très bien

1. **Architecture 3 couches** claire et documentée avec règles de dépendance respectées
2. **32 ports** dans `Contracts/` — abstraction complète de WordPress
3. **DI Container** Symfony scoped — zéro conflit, Extensions + Compiler Passes exemplaires
4. **PSR compliance** systématique (7 PSR implémentés)
5. **Entité `Job`** — machine à états avec invariants protégés, modèle DDD riche
6. **Specifications pattern** bien implémenté pour les requêtes
7. **900+ tests** avec bonne couverture
8. **Foundation layer** quasi parfaite (Hooks, Compose, Cache, Clock = 95/100)
9. **`MetaCompare`/`SortDirection`** correctement déplacés dans `Query/` (Foundation)
10. **`RequestContextInterface`/`ResponseEmitterInterface`** — superglobales correctement abstraites

### 5.2 Plan d'action priorité

#### Sprint 1 — Clean Architecture (impact maximal)

| # | Action | Fichiers | Impact |
|---|--------|----------|--------|
| 1 | Déplacer `PostQueryBuilder` et `TermQueryBuilder` dans `Infrastructure/` | 2 fichiers | Élimine le couplage WP dans le domaine |
| 2 | Injecter `ResponseEmitterInterface` dans `ServePageCache` | 1 fichier | Élimine `header()` + `exit` |
| 3 | Refactorer `AuditLogCsvExporter` : séparer génération CSV et transport HTTP | 1 fichier | SRP + Clean Arch |
| 4 | Renommer `TransientStoreInterface` → `CacheStoreInterface` | ~10 fichiers | Nommage agnostique |

#### Sprint 2 — DDD (modèle de domaine)

| # | Action | Fichiers | Impact |
|---|--------|----------|--------|
| 5 | Enrichir `Term` avec logique métier (validation, `isHierarchical()`, `hasChildren()`) | 1-2 fichiers | Entité non-anémique |
| 6 | Ajouter validation dans les setters de `Post` (postType non vide, title non vide) | 1 fichier | Invariants protégés |
| 7 | Injecter `ClockInterface` dans `Job` au lieu de `new \DateTimeImmutable()` | 1 fichier + factory | Testabilité |
| 8 | Créer Value Objects prioritaires : `JobKey`, `IPAddress`, `MetaKey` | 3 nouveaux fichiers | Type safety |
| 9 | Formaliser l'agrégat `Post` → `PostMeta` | 2-3 fichiers | Cohérence transactionnelle |

#### Sprint 3 — SRP (responsabilités)

| # | Action | Fichiers | Impact |
|---|--------|----------|--------|
| 10 | Extraire `PostTypeActivationHandler` de `RegisterPostType` | 2 fichiers | SRP |
| 11 | Découper `AdminUrlObfuscation` : URL rewriting vs cookie vs 404 handling | 3 fichiers | SRP |
| 12 | Introduire Domain Events (`JobCompleted`, `PostPublished`) | 3-5 fichiers | DDD événementiel |

#### Sprint 4 — Modernisation PHP 8.2+

| # | Action | Impact |
|---|--------|--------|
| 13 | `readonly` properties sur toutes les dépendances injectées | Immutabilité |
| 14 | Constructor promotion systématique | Réduction boilerplate |
| 15 | `final` sur toutes les classes concrètes leaf | API claire |
| 16 | Remplacer `protected` → `private` dans les entités `final` | Encapsulation |

---

## 6. Scores globaux

| Critère | Score | Commentaire |
|---------|:-----:|-------------|
| **SRP** | **B+** (7.5/10) | Bon dans l'ensemble. Quelques classes multi-responsabilités dans Security et Performance |
| **DDD** | **C+** (6/10) | Ports/Adapters et Specifications excellents. Entités semi-anémiques, pas d'Aggregate Roots formels, pas de Domain Events |
| **Clean Architecture** | **B+** (8/10) | 3 couches bien définies, 32 ports, PSR compliance forte. Quelques fuites d'infrastructure (QueryBuilders, `header()`) |
| **Global** | **B** (7.2/10) | Framework mature avec une base architecturale solide. Les améliorations DDD et les quelques violations Clean Arch sont les axes de progression principaux |
