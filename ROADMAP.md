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

## PHASE 3 : Reduction de complexite (Sprint 3-4 - 4 semaines)

**Objectif :** Reduire la complexite cyclomatique des fichiers critiques.
**Impact score :** 8.8 -> 9.0

### 3.1 Refactoriser les 6 fichiers a haute complexite

| Fichier | Complexite actuelle | Cible |
|---|---|---|
| RemoveUnusedCss.php | 44 | < 15 |
| PreloadPageCache.php | 32 | < 15 |
| BreadcrumbSchemaGenerator.php | 27 | < 15 |
| DatabaseHardening.php | 26 | < 15 |
| UploadSecurity.php | 25 | < 15 |
| WordPressContainer.php | 25 | < 15 |

**Strategies de refactoring :**
- **Extract Method** : decomposer les methodes longues en sous-methodes
- **Strategy Pattern** : remplacer les longues chaines if/else par des strategies
- **Early Return** : reduire l'imbrication avec des guard clauses
- **Extract Class** : si une methode a trop de responsabilites

**Validation :** `grep -cE "\b(if|else|elseif|switch|case|for|foreach|while|catch)\b" <file>` -> cible < 15 pour chaque fichier.

### 3.2 Decomposer les 5 God classes potentielles

| Classe | Methodes | Action |
|---|---|---|
| Job.php | 35 | Extraire un `JobStatus` value object et un `JobMetadata` |
| PerformanceConfigurator.php | 31 | Builder pattern avec sous-configurateurs |
| AuditLogAdminPage.php | 28 | Separer rendering (View) et logic (Controller) |
| PostMetaStructure.php | 28 | Interface large -> interfaces specifiques (ISP) |
| WordPressContainer.php | 428 LOC | Extraire ServiceCompiler et DefinitionResolver |

**Validation :** Aucune classe non-test ne depasse 25 methodes. Aucun fichier ne depasse 300 LOC.

### 3.3 Reduire les 100 usages de `mixed`

**Mesure actuelle :** 100 occurrences de `: mixed` ou `mixed `.
**Cible :** < 30 (uniquement la ou `mixed` est semantiquement correct).

**Action :**
- Remplacer par des union types (`string|int|array`)
- Utiliser des generics PHPDoc (`@param array<string, string>`)
- Introduire des value objects pour les structures complexes

**Validation :** `grep -rn ": mixed\|mixed " src/ | wc -l` -> cible < 30

---

## PHASE 4 : Couverture de tests (Sprint 5-6 - 4 semaines)

**Objectif :** Chaque module a un ratio test/src minimum.
**Impact score :** 9.0 -> 9.2

### 4.1 Modules critiques sans tests

| Module | LOC src | Tests actuels | Tests cible |
|---|---|---|---|
| Plugin | 155 | 0 | 3+ |
| Compose | 1 305 | 1 | 8+ |
| Hooks | 340 | 1 | 3+ |
| Theme | 284 | 1 | 3+ |
| Options | 216 | 1 | 3+ |

**Priorite :** Compose en premier (module noyau, 1 305 LOC, 1 seul test).

### 4.2 Tests pour les fichiers a haute complexite

Chaque fichier refactorise en Phase 3 doit avoir un test unitaire couvrant les chemins principaux **avant** le refactoring (test de non-regression).

### 4.3 Tests de securite

Ajouter des tests specifiques pour :
- Les 21 requetes SQL (injection attempts)
- Les 51 operations fichiers (path traversal)
- Les 4 appels `unserialize()` (object injection)
- Les 4 verifications nonce (CSRF)

**Validation :** `find src/ tests/ -name "*Test.php" | wc -l` -> cible : +25 fichiers de tests minimum.

---

## PHASE 5 : Architecture (Sprint 7+ - continu)

**Objectif :** Renforcer les patterns architecturaux.

### 5.1 Introduire des enums la ou pertinent

**Mesure actuelle :** 4 enums, 5 constantes candidates.
**Candidats identifies :**
- SameSite cookie policy (`Strict`|`Lax`|`None`)
- HealthCheck status
- Cache strategy type
- Asset version strategy
- Log level (si non PSR-3)

### 5.2 Reduire le couplage service locator

**Mesure actuelle :** 64 appels `->get(` qui pourraient etre du service location.
**Action :** Auditer chaque occurrence. Remplacer le service location par de l'injection constructeur quand c'est possible.

### 5.3 Auditer les 51 operations fichiers

**Action :** Verifier la protection contre le path traversal sur chaque appel `file_get_contents`/`file_put_contents`/`fopen`/`fwrite`. S'assurer que les chemins sont valides avec `realpath()` ou une whitelist de repertoires.

---

## Criteres de validation par phase

| Phase | Metrique de succes | Outil de mesure |
|---|---|---|
| Phase 1 | TERMINEE - 3 SQL durcis, 0 risque reel identifie | grep |
| Phase 2 | >90% classes final, >250 readonly, 0 strict_types manquant | grep + wc |
| Phase 3 | 0 fichier >15 branches, 0 classe >25 methodes, <30 mixed | grep -cE |
| Phase 4 | 0 module sans test, +25 fichiers test | find + wc |
| Phase 5 | >8 enums, <20 service location calls | grep |

---

## Timeline estimee

```
Phase 1 - Securisation .............. TERMINEE (aucun risque critique reel)
Phase 2 - Modernisation PHP 8.2+ ... Semaines 1-4
Phase 3 - Reduction complexite ..... Semaines 5-8
Phase 4 - Couverture tests ......... Semaines 9-12
Phase 5 - Architecture ............. Continu
```

**Score projete apres chaque phase :**
- Baseline : **7.9/10**
- Phase 1 : **8.1/10** (+0.2) - TERMINEE (impact moindre car peu de risques reels)
- Phase 2 : **8.8/10** (+0.7)
- Phase 3 : **9.0/10** (+0.2)
- Phase 4 : **9.2/10** (+0.2)
- Phase 5 : **9.5/10** (+0.3)
