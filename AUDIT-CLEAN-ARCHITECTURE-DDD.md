# Audit Clean Architecture & DDD — Framework complet

## Tableau de synthèse par module

| Module | Score | Critique | Problème principal |
|--------|-------|----------|-------------------|
| **Hooks** | 95/100 | - | Aucun |
| **Options** | 95/100 | - | Aucun |
| **Plugin** | 95/100 | - | Aucun |
| **Theme** | 95/100 | - | Aucun |
| **Blocks** | 95/100 | - | Aucun |
| **CLI** | 95/100 | - | Aucun |
| **Compose** | 95/100 | - | Error handling dans load() |
| **GDPR** | 90/100 | - | JSON encoding dans business logic |
| **Observability** | 90/100 | - | hrtime() non abstrait |
| **Queue** | 85/100 | 3 | Entity anémique, infra dépend de Factory |
| **Cache** | 85/100 | - | Noms d'interfaces WordPress-spécifiques |
| **Security** | 75/100 | 3 | Fonctions WP dans le domaine (header, $_POST, $_SERVER) |
| **Admin** | 75/100 | 1 | WP_Admin_Bar type hint dans le domaine |
| **Assets** | 70/100 | 1 | Naming conventions incohérentes |
| **Performance** | 65/100 | 2 | PreloadUrlCollector couplé à WP, ServePageCache SRP |
| **Seo** | 65/100 | 1 | file_exists/require dans le domaine |
| **RestApi** | 60/100 | 2 | is_user_logged_in() dans le domaine |
| **PostType** | 46/100 | 3 | Modèles anémiques, repos couplés WP |
| **PostMeta** | 46/100 | 3 | Modèles anémiques, interface trop large (24 méthodes) |
| **Taxonomy** | 46/100 | 3 | Couplage cross-module, repos couplés WP |

---

## Issues CRITIQUES (à traiter en priorité)

### 1. Modèles de domaine anémiques (PostType, PostMeta, Taxonomy, Queue)

**Fichiers :** `Post.php`, `PostType.php`, `PostMeta.php`, `Term.php`, `Taxonomy.php`, `Job.php`

Les entités sont des conteneurs de données purs (getters/setters) sans logique métier ni validation d'invariants. Cela viole le principe fondamental de DDD : les entités doivent encapsuler comportements et règles métier.

**Exemple — Job.php :**
```php
// Actuel : setter sans validation
public function setKey(string $key): self {
    $this->key = $key; // accepte chaîne vide !
    return $this;
}

// Recommandé : invariants protégés
public function setKey(string $key): self {
    if ($key === '') throw new InvalidArgumentException('Job key cannot be empty.');
    $this->key = $key;
    return $this;
}
```

**Actions requises :**
- Ajouter validation dans les setters des entités
- Ajouter des méthodes de comportement (ex: `Job::markAsRunning()`, `Job::markAsCompleted()`)
- Considérer un constructeur privé avec factory obligatoire
- Définir clairement les Aggregate Roots (Post = aggregate root avec PostMeta comme enfant)

---

### 2. Repositories appellent directement WordPress (PostType, PostMeta, Taxonomy)

**Fichiers :** `PostRepository.php`, `PostQueryBuilder.php`, `TermRepository.php`, `TermQueryBuilder.php`, `PostMetaRepository.php`

```php
// Violation — appel direct à WordPress dans le Repository
use function get_posts;
$wpPosts = get_posts(array_merge($defaults, $this->args));
```

Les repositories devraient déléguer les appels WP à une couche Infrastructure via des interfaces.

**Actions requises :**
- Créer `WordPressPostGatewayInterface` dans Contracts
- Implémenter `WordPressPostGateway` dans Infrastructure
- Injecter le gateway dans les repositories

---

### 3. Couplage cross-module (Taxonomy → PostType)

**Fichier :** `src/Taxonomy/Repository/TermQueryBuilder.php:7-8`

```php
use BackTo\Framework\PostType\Repository\MetaCompare;
use BackTo\Framework\PostType\Repository\SortDirection;
```

**Action requise :** Déplacer `MetaCompare` et `SortDirection` dans un module partagé (ex: `src/Shared/Repository/`)

---

### 4. Fonctions WordPress dans la couche domaine (Security, Performance, RestApi, Seo)

| Fichier | Fonction WP | Ligne |
|---------|------------|-------|
| CorsManager.php | `header()`, `exit` | 231, 253 |
| SessionManager.php | `WP_Session_Tokens::get_instance()` | 118 |
| ClientIpTrait.php | `$_SERVER` | 22-54 |
| PasswordPolicy.php | `$_POST` | 119 |
| TwoFactorAuthentication.php | `$_POST` | 236 |
| PreloadUrlCollector.php | 15+ fonctions WP | 25-184 |
| ServePageCache.php | `$_SERVER`, `is_admin()` | 108-151 |
| RegisterRestRoute.php | `is_user_logged_in()` | 34-47 |
| SeoConfig.php | `file_exists()`, `require` | 34-36 |

**Action requise :** Créer des interfaces d'abstraction :
- `ServerRequestInterface` (remplace `$_SERVER`, `$_POST`)
- `HttpHeaderInterface` (remplace `header()`, `headers_sent()`)
- `UserAuthenticationPort` (remplace `is_user_logged_in()`)
- `ConfigLoaderPort` (remplace `file_exists()`/`require`)

---

### 5. Infrastructure dépend de Factory (Queue)

**Fichier :** `WordPressQueueRepository.php:10`

```php
use BackTo\Framework\Queue\Factory\JobFactory;
```

Le repository d'infrastructure importe et dépend de `JobFactory` (couche application). Cela crée une dépendance inversée.

**Action requise :** Séparer l'hydratation des entités du repository. Le repository ne devrait retourner que des données brutes, l'application layer hydrate via la factory.

---

## Issues HIGH (sprint suivant)

### 6. Interfaces trop larges

| Interface | Méthodes | Problème |
|-----------|----------|----------|
| PostMetaStructureInterface | 24 | Mélange données + enregistrement WP |
| LoginThrottleInterface | 8 | 3 responsabilités mélangées |
| SecurityNotifierInterface | 3 | Notification + configuration |

**Action :** Découper en interfaces plus ciblées (Interface Segregation Principle)

### 7. Classes multi-responsabilités (SRP violations)

| Classe | Responsabilités | Action |
|--------|----------------|--------|
| RegisterQueue | 7+ (lifecycle, cron, processing, cleanup, locking) | Découper en 5 classes |
| SecurityNotifier | 4 (routing, formatting, recipients, thresholds) | Extraire formatter + resolver |
| ServePageCache | 6 (serve, buffer, capture, cacheability, URL, host) | Découper en 4 classes |
| FileIntegrityMonitor | 4 (checks, hashing, baselines, logging) | Extraire hasher + baseline manager |
| RemoveUnusedCss | 5 (hooks, buffering, parsing, filtering, matching) | Extraire CssAnalyzer + SelectorExtractor |
| RegisterPostType | 6 (hooks, activation, registry, factory, registrar, logging) | Découper |
| WordPressHtmlOptimizer | 4 (HTML, CSS, JS minification + preservation) | Composition |

### 8. Absence de Value Objects

Aucun module n'utilise de Value Objects. Candidats prioritaires :

| Value Object | Remplace | Module |
|-------------|----------|--------|
| `JobKey` | `string` | Queue |
| `QueueGroup` | `string` | Queue |
| `IPAddress` | `string` | Security |
| `CIDR` | `string` | Security |
| `Slug` | `string` | PostType/Taxonomy |
| `MetaKey` | `string` | PostMeta |
| `CacheKey` | `string` | Cache |
| `ConfigKey` | `string` | Seo |

### 9. Noms d'interfaces avec terminologie WordPress dans Contracts

| Interface | Devrait être |
|-----------|-------------|
| `TransientStoreInterface` | `CacheStoreInterface` |
| `TransientCleanerInterface` | `CacheCleanerInterface` |

### 10. Dépendances concrètes au lieu d'interfaces

| Classe | Dépendance concrète | Devrait être |
|--------|-------------------|-------------|
| QueueDispatcher | `QueueRegistry` | `QueueRegistryInterface` |
| QueueWorker | `JobFactory` | Extraire `JobRecurrenceService` |
| RegisterQueue | `QueueRegistry` | `QueueRegistryInterface` |

---

## Issues MEDIUM (refactoring progressif)

### 11. Factory mélange création et logique métier

- `PostTypeFactory::prepareHierarchicalArgs()` — règle métier dans factory
- `TaxonomyFactory::prepareArgs()` — idem
- `JobFactory::sanitizeError()` — méthode statique, devrait être un service injectable

### 12. Sérialisation non abstraite

`serialize()`/`unserialize()` utilisés directement dans Cache et Performance. Devrait être abstrait pour permettre JSON, msgpack, etc.

### 13. Naming conventions incohérentes

- `SvgFactory::$image_id` — snake_case au lieu de camelCase
- `ReplaceImgTagBySvgTag::fromHtml()` — manque `public`
- Noms de classes impératifs : `DisableXmlRpc`, `HideWordPressVersion` → préférer des noms descriptifs

### 14. `file_get_contents()` contourne le Filesystem injecté

**Fichier :** `WordPressPageCache.php:59` — utilise `file_get_contents()` alors que `$this->filesystem` est injecté.

---

## Points forts du Framework

1. **Excellente organisation des fichiers** — Contracts/, Entity/, Factory/, Infrastructure/ systématiquement séparés
2. **DI Container bien implémenté** — Extensions, Compiler Passes, auto-wiring
3. **Bonne couverture de tests** — 900+ tests, mocks bien utilisés
4. **Enums PHP 8.1** — JobStatus, MetaCompare, SortDirection
5. **Hook system propre** — HookDispatcherInterface abstrait WordPress
6. **Modules fondamentaux exemplaires** — Hooks, Options, Plugin, Theme, Blocks, CLI sont à 95/100
7. **Infrastructure WordPress correctement isolée** dans la majorité des modules
8. **Pattern port/adapter** bien appliqué pour les adaptateurs WordPress

---

## Plan d'action recommandé

### Sprint 1 — Critiques
1. Enrichir les entités avec logique métier et validation d'invariants
2. Extraire les appels WordPress des repositories vers des gateways
3. Déplacer MetaCompare/SortDirection dans un module partagé
4. Créer ServerRequestInterface et HttpHeaderInterface

### Sprint 2 — High
5. Découper les interfaces trop larges
6. Refactorer les classes multi-responsabilités (RegisterQueue, ServePageCache)
7. Créer les Value Objects prioritaires (JobKey, IPAddress, Slug)
8. Remplacer les dépendances concrètes par des interfaces

### Sprint 3 — Medium
9. Séparer logique métier des factories
10. Abstraire la sérialisation
11. Corriger les conventions de nommage
12. Renommer les interfaces Transient* vers Cache*
