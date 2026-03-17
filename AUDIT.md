# Audit des bonnes pratiques - BackTo Framework 4.0.0

**Date :** 2026-03-17
**Branche :** 4.0.0
**PHP minimum :** 8.2+

---

## Synthese globale

| Module | Score | Fichiers | Strict Types | Type Hints | Final | Readonly | Promo. Constr. | Interfaces | Tests | DI |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Queue** | **9/10** | 22 | 100% | Excellent | - | - | - | Excellent | 8 | Excellent |
| **Performance** | **9/10** | 39 | 100% | Excellent | - | - | - | Excellent | 12 | Excellent |
| **Cache** | **9/10** | 15 | 91% | Excellent | - | - | - | Excellent | 4 | Excellent |
| **Contracts** | **8.8/10** | 15 | 100% | Excellent | N/A | N/A | N/A | Excellent | Indirect | N/A |
| **Admin** | **8.5/10** | 13 | 100% | Excellent | - | - | - | Excellent | 4 | Excellent |
| **Gdpr** | **8.5/10** | 34 | 100% | Excellent | Partiel | Excellent | Excellent | Excellent | 13 | Excellent |
| **Observability** | **8.5/10** | 24 | 100% | Excellent | - | - | - | Excellent | 7 | Excellent |
| **RestApi** | **8.5/10** | 12 | 100% | Excellent | - | - | - | Excellent | 3 | Excellent |
| **Seo** | **8.2/10** | 57 | 100% | Excellent | Partiel | - | - | Excellent | 20 | Excellent |
| **Security** | **8.2/10** | 124 | 100% | Tres bon | - | - | - | Excellent | 50 | Excellent |
| **Blocks** | **8/10** | 17 | 100% | Tres bon | - | - | - | Excellent | 3 | Excellent |
| **Hooks** | **8/10** | 6 | 100% | Excellent | - | - | - | Excellent | 1 | Excellent |
| **PostMeta** | **8/10** | 18 | 100% | Tres bon | - | - | - | Excellent | 4 | Excellent |
| **Taxonomy** | **7.8/10** | 14 | 100% | Bon | - | - | - | Excellent | 5 | Excellent |
| **PostType** | **7.5/10** | 23 | 100% | Tres bon | - | - | - | Excellent | 6 | Excellent |
| **Exception** | **7.5/10** | 7 | 100% | Excellent | - | N/A | N/A | - | Indirect | N/A |
| **Cli** | **7.5/10** | 13 | 88% | Excellent | - | - | - | Partiel | 5 | Faible |
| **Assets** | **7.2/10** | 13 | 100% | Bon | - | - | - | Bon | 3 | Bon |
| **Compose** | **7.2/10** | 12 | 100% | Excellent | - | - | - | Bon | 1 | Excellent |
| **Options** | **7/10** | 6 | 100% | Moyen | - | - | - | Bon | 1 | Bon |
| **Theme** | **6.5/10** | 8 | 100% | Faible | - | - | - | Minimal | 1 | Bon |
| **Plugin** | **6/10** | 4 | 100% | Faible | - | - | - | Bon | 0 | Bon |

**Moyenne globale : 7.9/10**

---

## Points forts du framework

### Architecture exemplaire
- **Port/Adapter pattern** applique systematiquement (Infrastructure/ + Contracts/)
- **Dependency Injection** via Symfony DI Container avec compiler passes
- **Single Responsibility Principle** respecte dans la quasi-totalite des modules
- **PSR-4** 100% conforme sur l'ensemble du framework
- **`declare(strict_types=1)`** present dans 99%+ des fichiers

### Modules d'excellence
- **Queue** : securite (sanitization des erreurs), enums (JobStatus), retry logic, tests complets
- **Performance** : 39 fichiers avec architecture exemplaire, tests d'integration inclus
- **Gdpr** : seul module utilisant massivement `readonly` et constructor promotion (PHP 8.2+)
- **Security** : 74 classes avec 22 interfaces, traits pour cross-cutting concerns

---

## Problemes recurrents identifies

### 1. Absence de `final` sur les classes concretes (Impact: Moyen)
**Modules concernes :** Tous sauf Gdpr (partiel) et Seo (partiel)

Les classes concretes non concues pour l'heritage ne sont pas marquees `final`. Cela empeche la detection d'heritage accidentel et reduit la clarte de l'API.

**Recommandation :** Ajouter `final` sur toutes les classes concretes leaf (non abstraites, non destinees a l'extension).

### 2. Absence de `readonly` properties (Impact: Moyen)
**Modules concernes :** Tous sauf Gdpr

Malgre le requirement PHP 8.2+, seul le module Gdpr utilise `readonly` sur les proprietes injectees en constructeur. Les dependances injectees ne changent jamais apres construction.

**Recommandation :** Ajouter `readonly` sur toutes les proprietes assignees uniquement dans le constructeur.

### 3. Absence de constructor promotion (Impact: Faible)
**Modules concernes :** Tous sauf Gdpr

Le pattern suivant est repete dans presque chaque classe :
```php
private Type $prop;
public function __construct(Type $prop) {
    $this->prop = $prop;
}
```
Au lieu de :
```php
public function __construct(private readonly Type $prop) {}
```

**Recommandation :** Migrer vers constructor promotion + readonly pour reduire le boilerplate.

### 4. Type hints manquants sur certaines proprietes (Impact: Eleve)
**Modules concernes :** Plugin, Theme, Assets

- `Plugin/I18n/LoadPluginTextDomain.php` : proprietes sans type (`protected $pluginDirectory;`)
- `Theme/I18n/LoadThemeTextDomain.php` : meme probleme
- `Assets/ReplaceImgTagBySvgTag.php` : methode sans visibilite explicite

**Recommandation :** Corriger immediatement ces fichiers - ce sont les seuls non conformes PHP 8.2.

### 5. PHPDoc redondants avec les type hints natifs (Impact: Faible)
**Modules concernes :** Blocks, PostType, Cli, Assets

Nombreux `@param string $name` et `@return string` deja exprimes par les type hints natifs.

**Recommandation :** Supprimer les PHPDoc redondants, ne garder que ceux apportant de l'information supplementaire (`@param array<string, mixed>`, `@return Type[]`, etc.).

### 6. Couverture de tests inegale (Impact: Moyen)
**Modules avec peu/pas de tests :**
- **Plugin** : 0 fichier de test
- **Theme** : 1 fichier de test
- **Options** : 1 fichier de test
- **Compose** : 1 fichier de test

**Modules bien testes :**
- **Security** : 50 fichiers de tests
- **Seo** : 20 fichiers de tests
- **Gdpr** : 13 fichiers de tests
- **Performance** : 12 fichiers de tests

### 7. Enums non utilises la ou ils seraient pertinents (Impact: Faible)
**Opportunites identifiees :**
- `Security/CookieHardening` : SameSite ('Strict'|'Lax'|'None') -> enum
- `Observability/HealthCheckResult` : status constants -> enum
- `Assets` : version strategies -> enum
- `Cache/Strategy` : cache strategies -> enum

---

## Bugs potentiels detectes

### Bug critique - PostType/RegisterPostType.php
**Fichier :** `src/PostType/RegisterPostType.php:49`
**Probleme :** `return;` au lieu de `continue;` dans la boucle de registration des post types. Seul le premier post type est enregistre.

### Bug logique - Options/HasArrayOptions.php
**Fichier :** `src/Options/HasArrayOptions.php:22`
**Probleme :** `in_array($key, $this->options)` verifie les valeurs au lieu des cles. Devrait utiliser `array_key_exists($key, $this->options)`.

### Risque - Compose/ResolveInstanceOfConditionalPassWithVendorPrefix.php
**Fichier :** `src/Compose/DependencyInjection/Compiler/ResolveInstanceOfConditionalPassWithVendorPrefix.php:122-131`
**Probleme :** Manipulation de serialisation avec magic numbers hardcodes, fragile aux changements de version PHP.

---

## Plan d'action recommande

### Priorite 1 - Corrections critiques
1. Fixer le bug `return` -> `continue` dans `RegisterPostType.php`
2. Fixer le bug `in_array` -> `array_key_exists` dans `HasArrayOptions.php`
3. Ajouter les type hints manquants dans Plugin et Theme

### Priorite 2 - Modernisation PHP 8.2+
4. Ajouter `final` sur toutes les classes concretes leaf
5. Migrer vers `readonly` properties sur les dependances injectees
6. Adopter constructor promotion partout

### Priorite 3 - Qualite
7. Augmenter la couverture de tests sur Plugin, Theme, Options, Compose
8. Supprimer les PHPDoc redondants
9. Introduire des enums la ou pertinent (SameSite, HealthStatus, etc.)
10. Ajouter `declare(strict_types=1)` dans les fichiers manquants (CacheExtension, cli-bootstrap)

---

## Module par module - Details

### Queue (9/10)
Meilleur module du framework. Architecture exemplaire avec enum JobStatus, securite (sanitization des messages d'erreur), retry logic, job scheduling, database indexes optimises, et 8 fichiers de tests.

### Performance (9/10)
39 fichiers avec separation parfaite Contracts/Infrastructure/Hooks. Configuration fluent, integration WordPress propre, 12 fichiers de tests. Seul point : operateur `@` sur `unserialize()`.

### Cache (9/10)
Strategy pattern bien implemente (Filesystem, Memory, Transient). PSR-16 conforme. `declare(strict_types=1)` manquant dans CacheExtension.php.

### Contracts (8.8/10)
Module d'interfaces pur, excellente conception. Union types modernes, array shapes documentes. Architecture hexagonale bien definie.

### Admin (8.5/10)
Bonne architecture avec registre, compiler pass et adapter WordPress. Methode sans visibilite dans AddReusableBlockMenu.php.

### Gdpr (8.5/10)
**Reference pour les bonnes pratiques PHP 8.2+** : seul module utilisant massivement `readonly`, constructor promotion et `final` sur les entites. 13 fichiers de tests. Manque : enums pour les locations de scripts.

### Observability (8.5/10)
Decorator pattern propre (ObservableHookDispatcher). PSR-3 compatible. HealthCheckResult pourrait utiliser un enum pour les statuts.

### RestApi (8.5/10)
Module compact et bien structure. Fluent configuration, bonne couverture de tests. Manque constructor promotion.

### Seo (8.2/10)
Module le plus riche (57 fichiers). Systeme de Schema JSON-LD complet avec 28 types. Providers extensibles. 20 fichiers de tests. Schema.php correctement `final`.

### Security (8.2/10)
Module massif (74 classes). 22 interfaces, traits pour IP et HTML escaping. Tests complets (50 fichiers). Typage `mixed` a resserrer sur certains parametres WordPress.

### Blocks (8/10)
Port/Adapter excellent. Strategy pattern pour les registries. PHPDoc redondants a nettoyer.

### Hooks (8/10)
Module coeur compact. Port/Adapter pour WordPress hooks. `runHooks()` pourrait utiliser un strategy pattern au lieu de multiples instanceof.

### PostMeta (8/10)
Bonne architecture Entity/Factory/Repository. Fluent interface. Types `callable` stockes en `mixed`.

### Taxonomy (7.8/10)
Similaire a PostType. QueryBuilder fluent. Variable mal nommee dans TermFactory (`$wpPost` au lieu de `$wpTerm`). Imports inutilises.

### PostType (7.5/10)
Bug critique (`return` au lieu de `continue`). Proprietes protected au lieu de private dans Post entity. Bons enums (MetaCompare, SortDirection).

### Exception (7.5/10)
Classes simples et bien structurees. Factory methods propres. ContainerBuildException inutilisee. Manque `final` partout.

### Cli (7.5/10)
Generator pattern fonctionnel. `declare(strict_types=1)` manquant dans bootstrap. DI faible (ClassGenerator hardcode).

### Assets (7.2/10)
Methode sans visibilite explicite. PHPDoc redondants. ReplaceImgTagBySvgTag non teste. 40% des classes sans tests.

### Compose (7.2/10)
Module noyau complexe. WordPressContainer (428 lignes) viole SRP. Hack de serialisation fragile. 1 seul test.

### Options (7/10)
Bug logique dans HasArrayOptions. Types de retour manquants sur le trait. Couverture de tests faible.

### Theme (6.5/10)
Proprietes sans type hints dans LoadThemeTextDomain. Visibilite `protected` injustifiee. 1 seul test.

### Plugin (6/10)
Score le plus bas. Proprietes sans type hints. 0 test. Memes problemes que Theme pour le TextDomain.
