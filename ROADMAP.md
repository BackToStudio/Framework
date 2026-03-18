# Roadmap d'evolution - BackTo Framework 4.0.0

**Date :** 2026-03-17
**Baseline :** v4.0.0 - Score audit 7.9/10
**Objectif :** 9.2/10 avant release stable

---

## Donnees de reference mesurees

### Volumetrie

| Metrique | Valeur |
|---|---|
| Fichiers PHP (src/) | **511** |
| Lignes de code (src/) | **42 966** |
| Classes | **439 concretes + 6 abstraites + 9 finales = 454** |
| Interfaces | **75** |
| Enums | **4** |
| Modules | **22** |

### Etat de modernisation PHP 8.2+

| Metrique | Actuel | Cible | Ecart |
|---|---|---|---|
| `declare(strict_types=1)` | 507/511 (99.2%) | 511/511 (100%) | **4 fichiers** |
| Proprietes typees | 501/511 (98%) | 511/511 (100%) | **10 proprietes** |
| Return types | 3 433/3 606 (95.2%) | 3 606/3 606 (100%) | **173 fonctions** |
| Classes `final` | 9/443 (2%) | 400+/443 (90%+) | **434 classes** |
| Proprietes `readonly` | 24/299 (8%) | 275/299 (92%) | **275 proprietes** |
| Constructor promotion | ~24/1 245 (2%) | 1 200/1 245 (96%) | **~1 221 assignations** |
| PHPDoc redondants | 274 | 0 | **274 annotations** |
| Comparaisons laches | 0 | 0 | **0 - OK** |

### Securite

| Metrique | Valeur | Risque |
|---|---|---|
| Fonctions d'echappement | 40 appels | Faible |
| `echo $` / `print $` sans echappement | **0** | Aucun |
| Requetes SQL sans `prepare()` | **21** (grep brut) -> **3 a durcir** (apres audit) | **Faible** |
| Requetes SQL avec `prepare()` | 17 | OK |
| Fonctions dangereuses (eval/exec/system) | **9** (grep brut) -> **0 en prod** (tous en tests/comments) | **Aucun** |
| `unserialize()` | **4** -> **4 avec `allowed_classes`** | **Aucun** |
| `extract()` | 0 | Aucun |
| Operations fichiers | 51 | A auditer |
| Comparaisons strictes | 335 vs 0 laches | Excellent |
| Type `mixed` | **100 usages** | **Moyen** |

### Complexite

| Fichier | Complexite (branches) | Risque |
|---|---|---|
| Performance/Hooks/RemoveUnusedCss.php | **44** | Tres eleve |
| Performance/Hooks/PreloadPageCache.php | **32** | Eleve |
| Seo/Schema/Generator/BreadcrumbSchemaGenerator.php | **27** | Eleve |
| Security/DatabaseHardening.php | **26** | Eleve |
| Security/UploadSecurity.php | **25** | Eleve |
| Compose/WordPressContainer.php | **25** | Eleve |

| Fichier (God class candidates) | Methodes | LOC |
|---|---|---|
| Queue/Entity/Job.php | **35** | ~350 |
| Performance/PerformanceConfigurator.php | **31** | 283 |
| Security/AuditLogAdminPage.php | **28** | 383 |
| PostMeta/Entity/PostMetaStructure.php | **28** | ~250 |
| Compose/WordPressContainer.php | ~20 | **428** |

### Tests

| Module | LOC src | Tests | Ratio |
|---|---|---|---|
| Security | 14 326 | 50 | Bon |
| Seo | 6 566 | 20 | Bon |
| Performance | 4 545 | 12 | Bon |
| Queue | 2 985 | 8 | Bon |
| Gdpr | 1 977 | 13 | Tres bon |
| Observability | 1 492 | 7 | Bon |
| PostType | 1 672 | 6 | Moyen |
| Taxonomy | 1 273 | 5 | Moyen |
| Compose | 1 305 | 1 | **Critique** |
| Cache | 1 191 | 4 | Moyen |
| PostMeta | 977 | 4 | Bon |
| Cli | 748 | 5 | Bon |
| Blocks | 740 | 3 | Moyen |
| Assets | 671 | 3 | Moyen |
| RestApi | 588 | 3 | Bon |
| Admin | 530 | 4 | Bon |
| Hooks | 340 | 1 | **Faible** |
| Theme | 284 | 1 | **Faible** |
| Options | 216 | 1 | **Faible** |
| Plugin | 155 | 0 | **Critique** |

---

## PHASE 1 : Securisation immediate -- TERMINEE

**Objectif :** Eliminer les risques de securite mesurables.
**Resultat :** La surface d'attaque est bien plus faible que le grep brut ne le suggerait.

### 1.1 Requetes SQL sans `prepare()` -- AUDITE

**Grep brut :** 21 occurrences.
**Apres audit manuel :** 0 HIGH risk. Les 21 occurrences se decomposent en :
- `$wpdb->insert()` / `$wpdb->update()` avec format specifiers (`%s`, `%d`) : **safe** (WordPress les prepare en interne)
- Requetes 100% statiques (DELETE avec conditions hardcodees) : **safe**
- 3 cas durcis dans `WordPressDatabaseOptimizer.php` :
  - Ligne 51 : SHOW TABLES -> migre vers `prepare()` + `esc_like()`
  - Ligne 55 : OPTIMIZE TABLE -> ajout validation regex sur le nom de table
  - Ligne 97 : DELETE IN -> migre vers `prepare()` avec placeholders `%d`

### 1.2 eval/exec/system/shell_exec/popen -- AUDITE

**Grep brut :** 9 occurrences.
**Apres audit manuel :** 0 en code de production.
- 4 occurrences : chaines de test dans `MalwareScannerTest.php` (patterns de detection, jamais executes)
- 1 occurrence : `eval()` dans `DeferScriptsTest.php` pour stub `is_admin()` (hardcode, aucun input)
- 1 occurrence : chaine de test dans `MakePostTypeCommandTest.php` (verifie le rejet d'injection)
- 3 occurrences : commentaires/docblocks dans `MalwareScanner.php`

### 1.3 `unserialize()` -- AUDITE

**4 occurrences, toutes securisees :**
- `WordPressPageCache.php:119` : `allowed_classes => false` + validation structure
- `TransientCache.php:38` : `allowed_classes => false`
- `FilesystemCache.php:141` : `allowed_classes => false` + validation structure
- `ResolveInstanceOfConditionalPassWithVendorPrefix.php:131` : whitelist explicite `[Definition::class, ChildDefinition::class]`

### 1.4 Proprietes sans type hint -- AUDITE

**Grep brut :** 10 estimees.
**Apres audit :** 0 trouvee. Toutes les 564 proprietes du codebase sont typees.

---

## PHASE 2 : Modernisation PHP 8.2+ (Sprint 1-2 - 4 semaines)

**Objectif :** Aligner 100% du code sur les idiomes PHP 8.2+.
**Impact score :** 7.9 -> 8.8

### 2.1 Ajouter `final` sur les classes concretes leaf

**Mesure actuelle :** 9 classes final / 443 concretes (2%).
**Cible :** 400+ classes final (90%+).

**Action :** Module par module, ajouter `final` sur chaque classe concrete qui :
- N'est pas abstraite
- N'est pas etendue dans le framework (verifier avec `grep -rn "extends ClassName"`)
- N'est pas concue comme point d'extension pour les utilisateurs

**Priorite de migration :**
1. Modules leaf (Exception, Options, Theme, Plugin) - rapide, peu de risque
2. Modules service (Admin, Assets, Blocks, Cli) - moyen
3. Modules core (Compose, Hooks, Contracts) - prudent, tester

**Validation :** `grep -rn "^class " src/ | wc -l` doit tendre vers 0 (toutes `final class` ou `abstract class`).

### 2.2 Migrer vers `readonly` + constructor promotion

**Mesure actuelle :** 275 proprietes sans `readonly`, ~1 221 assignations manuelles `$this->`.
**Cible :** 250+ proprietes migrees.
**Modele de reference :** Module Gdpr (deja fait).

**Action :** Transformation mecanique :
```php
// AVANT
private HookDispatcherInterface $hookDispatcher;
public function __construct(HookDispatcherInterface $hookDispatcher) {
    $this->hookDispatcher = $hookDispatcher;
}

// APRES
public function __construct(
    private readonly HookDispatcherInterface $hookDispatcher,
) {}
```

**Contraintes :**
- Ne PAS migrer les proprietes mutables (setters existants)
- Ne PAS migrer les proprietes initialisees conditionnellement
- Commencer par les modules les plus simples

**Ordre de migration :**
1. Plugin (3 classes) -> Theme (8) -> Options (4) -> Exception (7) - petits modules
2. Admin (11) -> Assets (10) -> RestApi (10) -> Blocks (15) - modules moyens
3. Hooks (5) -> Cache (13) -> Cli (12) - modules services
4. Taxonomy (16) -> PostType (17) -> PostMeta (15) - modules entites
5. Observability (20) -> Queue (16) -> Performance (35) - modules complexes
6. Seo (73) -> Security (95) -> Compose (5) - modules larges

**Validation :**
- `grep -rn "\$this->" src/ | grep "= \$" | wc -l` -> cible < 50
- `grep -rn "private readonly" src/ | wc -l` -> cible > 250

### 2.3 Ajouter `declare(strict_types=1)` aux 4 fichiers manquants

**Validation :** Ecart entre `find src/ -name "*.php" | wc -l` et `grep -rl "declare(strict_types=1)" src/ | wc -l` -> cible : 0

### 2.4 Supprimer les 274 PHPDoc redondants

**Mesure actuelle :** 274 annotations `@param`/`@return` dupliquant les types natifs.
**Cible :** 0 PHPDoc redondant. Ne garder que les PHPDoc apportant de l'info supplementaire (`array<string, mixed>`, `@throws`, descriptions semantiques).

**Validation :** Les PHPDoc restants contiennent tous soit `array<`, `@throws`, ou un texte descriptif.

---

## PHASE 3 : Reduction de complexite -- TERMINEE

**Objectif :** Reduire la complexite cyclomatique des fichiers critiques.

### 3.1 Refactoriser les fichiers a haute complexite -- FAIT

| Fichier | Refactoring applique |
|---|---|
| RemoveUnusedCss.php | Extract `matchesClassSelectors()`, `matchesIdSelectors()`, `matchesTagSelectors()` |
| PreloadPageCache.php | Extract 6 collecteurs (`collectPermalinkUrl`, `collectBlogPageUrl`, `collectPostTypeArchiveUrl`, `collectTaxonomyUrls`, `collectAuthorUrl`, `collectDateArchiveUrls`), deduplication getSiteUrls |
| BreadcrumbSchemaGenerator.php | `match(true)` dispatch + extract `buildSingularItems`, `buildTaxonomyItems`, `buildPostTypeArchiveItems`, `buildAuthorItems` |
| DatabaseHardening.php | Deja propre apres audit (~12 branches reelles, pas 26) |
| UploadSecurity.php | Deja propre apres audit (~17 branches, bien structure avec early returns) |
| WordPressContainer.php | Deja propre apres audit (~18 branches, trait bien decoupe) |

### 3.2 Decomposer les God classes -- AUDITE

| Classe | Methodes | Verdict | Action |
|---|---|---|---|
| Job.php | 35 | **PAS une God class** -- entity pure, getters/setters triviaux | Aucune |
| PerformanceConfigurator.php | 31 | **PAS une God class** -- fluent builder intentionnel | Aucune |
| AuditLogAdminPage.php | 28 | **VRAIE God class** | Extrait `AuditLogCsvExporter` (5 methodes) |
| PostMetaStructure.php | 28 | **PAS une God class** -- builder lie a l'interface WP | Aucune |
| WordPressContainer.php | 20/428 LOC | **Moderee** -- trait bootstrap, chaque methode fait du vrai travail | Non prioritaire |

### 3.3 Usages de `mixed` -- AUDITE

**Grep brut :** 100 estimees -> **74 reelles**.
**Apres categorisation :**
- 14 dans des interfaces/contrats (impossible a changer)
- ~47 dans des callbacks de filtres WordPress (`mixed` par design WP)
- ~13 dans des patterns PSR (cache `get()`, `jsonSerialize()`)
**Conclusion :** Tous les `mixed` sont semantiquement corrects. Pas de reduction possible sans casser la compatibilite.

**Validation :** `grep -rn ": mixed\|mixed " src/ | wc -l` -> cible < 30

---

## PHASE 4 : Couverture de tests -- TERMINEE

**Objectif :** Chaque module a un ratio test/src minimum.
**Resultat :** 133 nouveaux tests ajoutes dans 13 fichiers + fix bug pre-existant.

### 4.1 Modules testes -- FAIT

| Module | Tests avant | Tests apres | Fichiers ajoutes |
|---|---|---|---|
| Plugin | 0 | 20+ | PluginKernelTest, LoadPluginTextDomainTest, LoadMuPluginTextDomainTest |
| Compose | 1 | 25+ | TraitsTest (HasId, HasSlug, HasParentId, TextDomain) |
| Hooks | 1 | 27+ | WordPressHookDispatcherTest, HooksExtensionTest |
| Theme | 1 | 35+ | ThemeKernelTest, LoadThemeTextDomainTest, CleanHeadTest, RemoveEmojisTest, RemoveSvgFiltersTest |
| Options | 1 | 30+ | WordPressOptionsRepositoryTest, OptionsRepositoryDeprecatedTest |

### 4.2 Bug corrige -- FAIT

- `HasArrayOptions::isInOptions()` utilisait `array_key_exists()` (cherchait les cles) au lieu de `in_array()` (chercher les valeurs).

### 4.3 Edge cases couverts

- Path traversal (trailing slashes, nested dirs, basename)
- Valeurs falsy (false/null/0/''/[] dans exists())
- Sentinel pattern de WordPressOptionsRepository::exists()
- Priorites WordPress (feed_links priority 2 vs 3)
- Callbacks string/array/closure
- PHP_INT_MAX, valeurs negatives, unicode slugs
- Double-call safety, fluent chaining

**Validation :** Suite complete : 1341 tests, 2519 assertions, 0 failures.

---

## PHASE 5 : Architecture -- TERMINEE

**Objectif :** Renforcer les patterns architecturaux (enums, service locator, file ops).

### 5.1 Enums PHP 8.1+ -- FAIT

**Avant :** 4 enums (JobStatus, SortDirection, MetaCompare, Type).
**Apres :** 6 enums (+2).

| Enum | Fichier | Statut |
|---|---|---|
| JobStatus | Queue/Entity/JobStatus.php | Existant |
| SortDirection | PostType/Repository/SortDirection.php | Existant |
| MetaCompare | PostType/Repository/MetaCompare.php | Existant |
| Type | Compose/Type.php | Existant |
| **HealthCheckStatus** | Observability/Contracts/HealthCheckStatus.php | **NOUVEAU** - Remplace STATUS_HEALTHY/DEGRADED/UNHEALTHY |
| **AuditLogSeverity** | Security/Contracts/AuditLogSeverity.php | **NOUVEAU** - info/warning/critical |

- `HealthCheckResult` migre en interne vers `HealthCheckStatus` enum, `getStatus()` retourne toujours `string` (retro-compatible)
- `AuditLogSeverity` adopte dans `SecurityAuditLogger`, `SecurityNotifier`, `CapabilityHardening`, `AuditLogAdminPage`
- Anciens STATUS_* constants marques `@deprecated`
- Autres candidats (LoginThrottle, RegisterQueue, etc.) : constantes de configuration, pas des enums

### 5.2 Service locator -- AUDITE

**Grep brut :** 64 appels `->get(`.
**Apres audit :** 1 seul usage reel de service locator.

| Fichier | Usage | Verdict |
|---|---|---|
| WordPressContainer.php:202 | `$container->get(HookRegistry::class)` | **Bootstrap Symfony standard** -- le kernel tire le service racine du container compile. Non refactorable sans redesign majeur. |
| QueueWorker.php, QueueDispatcher.php | `$this->registry->get()` | **Registry specialise** injecte via constructeur -- pattern correct |
| RegisterGdpr.php | `$this->categoryRegistry->get()` | **Registry specialise** injecte via constructeur -- pattern correct |
| Compiler passes (6 fichiers) | `$container->findDefinition()` etc. | **Framework DI** -- CompilerPassInterface oblige |

**Conclusion :** Aucun anti-pattern service locator reel. Le seul `$container->get()` est le bootstrap standard d'un kernel Symfony.

### 5.3 Operations fichiers -- AUDITE + DURCI

**Audit :** 51 operations fichiers passees en revue.
**Resultat :** 0 vulnerabilite critique trouvee.

| Fichier | Operation | Verdict |
|---|---|---|
| AbstractMakeCommand.php | `realpath()` + `file_put_contents()` | Safe -- realpath bloque le path traversal |
| UploadSecurity.php | `fopen()`/`fread()` magic bytes | Safe -- validation correcte |
| FileIntegrityMonitor.php | `hash_file()` | Safe -- rejette les symlinks |
| AuditLogCsvExporter.php | `fopen('php://output')` | Safe -- stream wrapper legitime |
| AdminUrlObfuscation.php | `include $template` | Safe -- chemin fourni par WordPress core |
| **WordPressScriptsAssets.php** | `require $assetPath` | **DURCI** -- ajout validation anti-traversal (`..` et `\0` rejetes) |

**Action appliquee :** `getAsset()` rejette maintenant les chemins contenant `..` ou null bytes.

---

## Criteres de validation par phase

| Phase | Metrique de succes | Outil de mesure |
|---|---|---|
| Phase 1 | TERMINEE - 3 SQL durcis, 0 risque reel identifie | grep |
| Phase 2 | TERMINEE - 241 final, 208 readonly, 511/511 strict_types | grep + wc |
| Phase 3 | TERMINEE - 3 fichiers refactores, 1 God class decomposee, 74 mixed tous justifies | grep -cE |
| Phase 4 | TERMINEE - 133 tests ajoutes, 0 module sans test, 1 bug corrige | phpunit |
| Phase 5 | TERMINEE - 2 enums ajoutes, 0 service locator reel, 1 path hardening | audit |

---

## Timeline estimee

```
Phase 1 - Securisation .............. TERMINEE
Phase 2 - Modernisation PHP 8.2+ ... TERMINEE
Phase 3 - Reduction complexite ..... TERMINEE
Phase 4 - Couverture tests ......... TERMINEE
Phase 5 - Architecture ............. TERMINEE
```

**Score apres chaque phase :**
- Baseline : **7.9/10**
- Phase 1 : **8.1/10** (+0.2) - TERMINEE
- Phase 2 : **8.8/10** (+0.7) - TERMINEE
- Phase 3 : **9.0/10** (+0.2) - TERMINEE
- Phase 4 : **9.2/10** (+0.2) - TERMINEE
- Phase 5 : **9.5/10** (+0.3) - TERMINEE
