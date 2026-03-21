# Les piliers d'un site WordPress : Build & Run

*Explanation — Understanding-oriented*

Ce document identifie les **pans essentiels** d'un site Internet WordPress pour garantir un **build reussi** (creation/refonte) et un **run economique** (suivi/evolution). Chaque pilier est mis en correspondance avec les modules du BackTo Framework qui le couvrent.

---

## Vue d'ensemble

```
BUILD (Creation/Refonte)                    RUN (Suivi/Evolution)
━━━━━━━━━━━━━━━━━━━━━━━                    ━━━━━━━━━━━━━━━━━━━━━
1. Architecture & Structure                 1. Monitoring & Observabilite
2. Performance                              2. Securite continue
3. Securite                                 3. Mises a jour & Compatibilite
4. SEO Technique                            4. Performance continue
5. RGPD / Conformite legale                 5. Sauvegardes & Restauration
6. Accessibilite (a11y)                     6. Gestion de contenu (editorial)
7. Contenu & Taxonomies                     7. Evolution fonctionnelle
8. UX / Design System                       8. Support & Documentation
```

---

## PHASE BUILD — Les 8 piliers de la creation

### 1. Architecture & Structure du code

**Pourquoi c'est critique :** Un mauvais choix d'architecture au build coute 10x plus cher a corriger en run.

| Decision | Bonne pratique | Anti-pattern |
|---|---|---|
| Theme vs Plugin | Separer logique metier (plugin) et presentation (theme) | Theme fourre-tout avec logique metier |
| Custom Post Types | Modeliser le domaine metier avec des CPT dedies | Tout mettre dans les pages/articles |
| Architecture PHP | Clean Architecture, DI, interfaces | Code procedural dans `functions.php` |
| Gestion des dependances | Composer + autoloading PSR-4 | `require_once` manuels |
| Environnements | Dev / Staging / Prod distincts | Developper directement en production |

**Couverture BackTo Framework :**
- `Compose/` — Kernel DI (Symfony), extensions modulaires, PSR-11
- `PostType/`, `Taxonomy/`, `PostMeta/` — Modelisation metier propre
- `Contracts/` — 75 interfaces port/adapter (aucun couplage direct a WordPress)
- `Plugin/`, `Theme/` — Separation nette plugin vs theme

### 2. Performance

**Pourquoi c'est critique :** Un site lent = taux de rebond eleve + penalite SEO. Les fondations performance se posent au build.

| Pan | Elements cles |
|---|---|
| **Cache** | Object cache (Redis/Memcached), page cache, browser cache headers |
| **Assets** | Minification CSS/JS, chargement differe (defer/async), critical CSS |
| **Images** | WebP/AVIF, lazy loading natif, srcset responsive |
| **Base de donnees** | Index optimises, requetes preparees, transients pour les requetes lourdes |
| **Hebergement** | PHP 8.2+, HTTP/2+, CDN, OPcache |

**Couverture BackTo Framework :**
- `Performance/` — Page cache, preloading, minification, .htaccess
- `Cache/` — Abstraction PSR-16 (Transient, Filesystem, Object Cache)
- `Assets/` — Gestion scripts/styles avec defer, versioning
- `Queue/` — Taches asynchrones via WP-Cron

### 3. Securite

**Pourquoi c'est critique :** WordPress represente ~43% du web = cible n1. La securite se construit, elle ne se rajoute pas.

| Pan | Elements cles |
|---|---|
| **Hardening** | Desactiver XML-RPC, masquer la version WP, limiter REST API publique |
| **Authentification** | 2FA, politique de mots de passe, limitation tentatives connexion |
| **Donnees** | Echappement systematique en sortie, `prepare()` pour les requetes SQL |
| **Headers** | Content-Security-Policy, X-Frame-Options, HSTS |
| **Fichiers** | Validation uploads (magic bytes), permissions restrictives |
| **Audit** | Journal des actions sensibles, detection de modifications |

**Couverture BackTo Framework :**
- `Security/` — 14 326 LOC, 95 classes : hardening, 2FA, rate limiting, CSP, CORS, audit log, malware scanner, upload validation, file integrity monitoring
- 0 `echo $var` sans echappement dans tout le framework
- 0 requete SQL sans `prepare()` en production

### 4. SEO Technique

**Pourquoi c'est critique :** Un site invisible sur Google n'a pas de valeur business. Le SEO technique se pose au build.

| Pan | Elements cles |
|---|---|
| **Structure** | Hierarchie de titres (H1-H6), URLs propres (permalinks), fil d'Ariane |
| **Donnees structurees** | Schema.org JSON-LD (Organization, BreadcrumbList, Article, Product...) |
| **Meta** | Title, description, Open Graph, Twitter Cards |
| **Technique** | Sitemap XML, robots.txt, canonical URLs, hreflang |
| **Core Web Vitals** | LCP < 2.5s, FID < 100ms, CLS < 0.1 |

**Couverture BackTo Framework :**
- `Seo/` — 6 566 LOC : Schema.org JSON-LD auto-genere, breadcrumbs, integration Yoast/SEOPress
- `Performance/` — Contribue aux Core Web Vitals (cache, preload, CSS critique)

### 5. RGPD / Conformite legale

**Pourquoi c'est critique :** Amendes jusqu'a 4% du CA mondial. La conformite doit etre native, pas un ajout.

| Pan | Elements cles |
|---|---|
| **Consentement** | Bandeau cookies conforme, granularite par categorie |
| **Tracking** | Scripts tiers bloques avant consentement |
| **Donnees personnelles** | Export/suppression des donnees, registre des traitements |
| **Mentions legales** | CGU, politique de confidentialite, mentions legales |

**Couverture BackTo Framework :**
- `Gdpr/` — Bandeau de consentement, gestion des categories de cookies, blocage conditionnel des scripts de tracking

### 6. Accessibilite (a11y)

**Pourquoi c'est critique :** Obligation legale (RGAA en France), elargissement de l'audience, amelioration UX globale.

| Pan | Elements cles |
|---|---|
| **Semantique HTML** | Landmarks ARIA, roles, etats |
| **Navigation clavier** | Focus visible, skip links, tab order logique |
| **Contraste** | Ratio minimum WCAG AA (4.5:1 texte, 3:1 grands textes) |
| **Medias** | Alt text images, sous-titres videos, transcriptions |
| **Formulaires** | Labels associes, messages d'erreur explicites |

**Couverture BackTo Framework :** L'accessibilite releve principalement du theme. Le framework fournit une base propre (HTML semantique dans les blocks Gutenberg, breadcrumbs accessibles) mais le gros du travail a11y est cote implementation theme.

### 7. Contenu & Taxonomies

**Pourquoi c'est critique :** Une mauvaise modelisation du contenu rend le run cauchemardesque (migration, recherche, filtres).

| Pan | Elements cles |
|---|---|
| **Modelisation** | CPT pour chaque type de contenu metier, taxonomies pour le classement |
| **Champs personnalises** | Meta structurees (pas de champs texte fourre-tout) |
| **Relations** | Taxonomies partagees, post-to-post via meta |
| **Medias** | Bibliotheque organisee, conventions de nommage |
| **Migration** | Import/export structure, WP-CLI pour les operations en masse |

**Couverture BackTo Framework :**
- `PostType/` — Registration fluent, repository pattern, tri/filtrage
- `Taxonomy/` — Registration avec validation, hierarchie, compteurs
- `PostMeta/` — Metadonnees structurees avec types stricts
- `Cli/` — Commandes `make:post-type`, `make:taxonomy` pour le scaffolding

### 8. UX / Design System

**Pourquoi c'est critique :** La coherence visuelle reduit les couts de production en run et ameliore la conversion.

| Pan | Elements cles |
|---|---|
| **Design tokens** | Variables CSS (couleurs, typo, espacement) |
| **Composants** | Blocks Gutenberg reutilisables, patterns |
| **Responsive** | Mobile-first, breakpoints coherents |
| **Typographie** | Echelle typographique, web fonts optimisees |
| **Theme.json** | Configuration centralisee WordPress (couleurs, typo, layout) |

**Couverture BackTo Framework :**
- `Blocks/` — Registration de blocks Gutenberg avec validation, block styles
- `Theme/` — Gestion des supports theme, assets, menus, sidebars
- `Assets/` — Chargement optimise des scripts et styles

---

## PHASE RUN — Les 8 piliers du suivi

### 1. Monitoring & Observabilite

**Objectif :** Detecter les problemes avant les utilisateurs.

| Pan | Elements cles | Frequence |
|---|---|---|
| **Uptime** | Monitoring HTTP, alertes down | Continu |
| **Logs** | Erreurs PHP, logs applicatifs structures | Continu |
| **Metriques** | Temps de reponse, taux d'erreur, usage memoire | Continu |
| **Health checks** | Etat des services (BDD, cache, SMTP, APIs) | Toutes les 5 min |

**Couverture BackTo Framework :**
- `Observability/` — PSR-3 logging, metriques, health checks avec statuts (Healthy/Degraded/Unhealthy)

### 2. Securite continue

**Objectif :** Maintenir la posture de securite dans le temps.

| Pan | Elements cles | Frequence |
|---|---|---|
| **Mises a jour** | Core, themes, plugins (patch de securite) | Hebdomadaire |
| **Audit** | Revue des logs, scan malware | Quotidien |
| **Sauvegardes** | Verification d'integrite des backups | Quotidien |
| **Comptes** | Revue des utilisateurs, rotation mots de passe | Mensuel |

**Couverture BackTo Framework :**
- `Security/` — Audit log, file integrity monitoring, malware scanner, rate limiting

### 3. Mises a jour & Compatibilite

**Objectif :** Garder le site a jour sans casser la production.

| Pan | Elements cles |
|---|---|
| **Workflow** | Tester en staging avant de deployer en prod |
| **WordPress Core** | Mises a jour mineures auto, majeures manuelles apres test |
| **PHP** | Suivre les versions supportees (actuellement 8.2+) |
| **Dependances** | `composer outdated`, alertes de securite Dependabot/Snyk |

### 4. Performance continue

**Objectif :** Empecher la degradation progressive des performances.

| Pan | Elements cles | Frequence |
|---|---|---|
| **Core Web Vitals** | Mesure LCP/FID/CLS via Google Search Console | Mensuel |
| **Base de donnees** | Nettoyage revisions, transients expires, optimisation tables | Mensuel |
| **Cache** | Verification hit ratio, purge selective | Hebdomadaire |
| **Assets** | Audit taille des pages, nombre de requetes | Trimestriel |

### 5. Sauvegardes & Restauration

**Objectif :** Pouvoir restaurer le site en < 1h en cas de sinistre.

| Pan | Elements cles |
|---|---|
| **Strategie 3-2-1** | 3 copies, 2 supports differents, 1 hors site |
| **Contenu** | BDD (mysqldump) + uploads (wp-content/uploads) |
| **Code** | Git (le code ne doit JAMAIS etre modifie en prod directement) |
| **Test de restauration** | Verifier la restauration effective au moins 1x par trimestre |

### 6. Gestion de contenu (editorial)

**Objectif :** Permettre au client de gerer son contenu sans support technique.

| Pan | Elements cles |
|---|---|
| **Roles & permissions** | Roles WordPress adaptes (pas de compte admin partage) |
| **Guide editorial** | Documentation du process de publication |
| **Templates** | Patterns/blocs predefinis pour garantir la coherence |
| **Medias** | Compression auto, conventions de nommage |

**Couverture BackTo Framework :**
- `Admin/` — Pages admin, menus, capabilities personnalises
- `Blocks/` — Blocks et patterns reutilisables

### 7. Evolution fonctionnelle

**Objectif :** Faire evoluer le site au moindre cout.

| Pan | Elements cles |
|---|---|
| **Architecture modulaire** | Ajouter des fonctionnalites sans toucher a l'existant |
| **Tests** | Suite de tests automatises pour eviter les regressions |
| **CI/CD** | Pipeline de deploiement automatise (lint, test, deploy) |
| **Documentation** | Architecture et decisions documentees |

**Couverture BackTo Framework :**
- Architecture 3 couches (Foundation → Domain → Application)
- 1 341 tests, 2 519 assertions
- CI GitLab (PHP 8.2/8.3/8.4), PHPStan niveau 8
- Documentation Diataxis complete (tutorials, how-to, reference, explanation)

### 8. Support & Documentation

**Objectif :** Reduire la dependance au developpeur initial.

| Pan | Elements cles |
|---|---|
| **Documentation technique** | Architecture, conventions, ADRs |
| **Documentation utilisateur** | Guide d'utilisation du back-office |
| **Runbook** | Procedures operationnelles (deploiement, rollback, incident) |
| **Formation** | Transfert de competences au client/equipe |

---

## Matrice de couverture BackTo Framework

| Pilier Build | Module(s) | Couverture |
|---|---|---|
| Architecture | Compose, Contracts, PostType, Taxonomy | Excellente |
| Performance | Performance, Cache, Assets, Queue | Excellente |
| Securite | Security (14 326 LOC) | Excellente |
| SEO Technique | Seo | Bonne |
| RGPD | Gdpr | Bonne |
| Accessibilite | Blocks, Theme | Partielle (cote theme) |
| Contenu & Taxonomies | PostType, Taxonomy, PostMeta, Cli | Excellente |
| UX / Design System | Blocks, Theme, Assets | Bonne |

| Pilier Run | Module(s) | Couverture |
|---|---|---|
| Monitoring | Observability | Bonne |
| Securite continue | Security | Excellente |
| Mises a jour | (process, hors framework) | N/A |
| Performance continue | Performance, Cache | Bonne |
| Sauvegardes | (infrastructure, hors framework) | N/A |
| Gestion de contenu | Admin, Blocks | Bonne |
| Evolution fonctionnelle | Architecture 3 couches + CI + Tests | Excellente |
| Documentation | docs/ (Diataxis) | Excellente |

---

## Checklist Build rapide

```
[ ] Architecture : Separation theme/plugin, DI, PSR-4
[ ] Performance : Cache, assets optimises, images WebP, CDN
[ ] Securite : Hardening, 2FA, CSP, echappement, prepare()
[ ] SEO : Schema.org, sitemap, permalinks, Core Web Vitals
[ ] RGPD : Bandeau cookies, blocage scripts, export donnees
[ ] a11y : Semantique HTML, navigation clavier, contrastes
[ ] Contenu : CPT modelises, taxonomies, meta structurees
[ ] UX : Design system, blocks reutilisables, responsive
[ ] Tests : PHPUnit, PHPStan, E2E Playwright
[ ] CI/CD : Pipeline lint + test + deploy
[ ] Environnements : Dev / Staging / Prod
[ ] Documentation : Architecture, conventions, guide utilisateur
```

---

## Checklist Run rapide

```
[ ] Monitoring : Uptime, logs, metriques, health checks
[ ] Mises a jour : Core WP, PHP, plugins (staging d'abord)
[ ] Sauvegardes : Strategie 3-2-1, test de restauration trimestriel
[ ] Securite : Audit log, scan malware, revue comptes
[ ] Performance : Core Web Vitals mensuels, nettoyage BDD
[ ] Editorial : Roles adaptes, guide de publication
[ ] Evolution : Tests automatises, deploi CI/CD
[ ] Documentation : A jour, runbook operationnel
```
