# Plan : WordPress Performance Bundle

## Contexte

Le framework `BackTo\Framework` est un framework WordPress basé sur les composants Symfony (DI Container, Config). Il utilise une architecture Clean Architecture (Ports & Adapters) avec :
- Des **interfaces/contrats** (ports)
- Des **implémentations WordPress** (adapters)
- Un système de **hooks auto-configurés** via des tags DI + CompilerPass
- Un système de **cache PSR-16** existant (Memory, Transient, Filesystem)
- Un **PerformanceCollector** existant dans Observability

Le nouveau bundle `Performance` viendra s'intégrer naturellement dans cette architecture.

---

## Architecture du bundle : `src/Performance/`

```
src/Performance/
├── Contracts/
│   ├── AssetOptimizerInterface.php          # Port - optimisation des assets
│   ├── DatabaseOptimizerInterface.php       # Port - optimisation BDD
│   ├── HtmlOptimizerInterface.php           # Port - minification HTML
│   ├── ImageOptimizerInterface.php          # Port - optimisation lazy-load images
│   ├── CacheManagerInterface.php            # Port - gestion centralisée des caches
│   └── PreloadStrategyInterface.php         # Port - stratégies de preload/prefetch
├── Infrastructure/
│   ├── WordPressAssetOptimizer.php           # Adapter - defer/async scripts, CSS critical path
│   ├── WordPressDatabaseOptimizer.php        # Adapter - nettoyage BDD WordPress
│   ├── WordPressHtmlOptimizer.php            # Adapter - minification output buffer
│   ├── WordPressImageOptimizer.php           # Adapter - lazy loading natif + dimensions
│   ├── WordPressCacheManager.php             # Adapter - object cache, transient, page cache
│   └── WordPressPreloadStrategy.php          # Adapter - preload/prefetch/preconnect headers
├── Hooks/
│   ├── DisableEmojis.php                     # Supprime les scripts/styles emojis WP
│   ├── DisableEmbeds.php                     # Supprime oEmbed inutile
│   ├── CleanHead.php                         # Nettoie wp_head (RSD, WLW, shortlinks, etc.)
│   ├── DisableXMLRPC.php                     # Désactive XML-RPC
│   ├── LimitPostRevisions.php                # Limite les révisions en BDD
│   ├── DisableHeartbeat.php                  # Contrôle/désactive l'API Heartbeat
│   ├── DeferScripts.php                      # Ajoute defer/async aux scripts
│   ├── OptimizeImages.php                    # Lazy loading + attributs dimensions
│   ├── MinifyHtml.php                        # Minification HTML output
│   ├── AddResourceHints.php                  # Preload/prefetch/preconnect
│   ├── OptimizeWooCommerce.php               # Désactive WC scripts/styles sur pages non-WC
│   └── CleanDashboard.php                    # Supprime widgets dashboard inutiles (admin)
├── Actions/
│   ├── CleanupDatabase.php                   # Action one-shot : nettoyage BDD
│   ├── FlushObjectCache.php                  # Action one-shot : vide object cache
│   └── PurgeTransients.php                   # Action one-shot : purge transients expirés
├── DependencyInjection/
│   └── Compiler/
│       └── RegisterPerformanceHookPass.php   # CompilerPass pour les hooks perf
├── PerformanceRegistry.php                   # Registry des optimisations actives
├── Tests/
│   ├── DisableEmojisTest.php
│   ├── CleanHeadTest.php
│   ├── DeferScriptsTest.php
│   ├── MinifyHtmlTest.php
│   ├── WordPressDatabaseOptimizerTest.php
│   ├── WordPressCacheManagerTest.php
│   └── WordPressAssetOptimizerTest.php
└── Resources/
    └── config/
        └── services.php                      # Configuration DI du bundle
```

---

## Détail des optimisations par catégorie

### 1. Nettoyage du head WordPress (`CleanHead`)
**Impact estimé : -50-200 KB par page, -3 à 8 requêtes HTTP**

- Suppression de `wp_generator` (version WP exposée = risque sécurité)
- Suppression des liens RSD (Really Simple Discovery)
- Suppression des liens WLW (Windows Live Writer)
- Suppression des shortlinks
- Suppression des liens REST API du head
- Suppression des liens `prev/next` pour les posts
- Suppression du DNS prefetch vers `s.w.org`

### 2. Désactivation des Emojis (`DisableEmojis`)
**Impact estimé : -25 KB JS + -5 KB CSS par page**

- Suppression du script inline `wp-emoji-release.min.js`
- Suppression du style inline emoji
- Suppression du DNS prefetch pour les CDN emoji
- Filtrage TinyMCE pour retirer le plugin emoji
- Suppression des styles SVG emoji

### 3. Désactivation des Embeds (`DisableEmbeds`)
**Impact estimé : -7 KB JS par page**

- Dé-enregistrement du script `wp-embed`
- Suppression de l'action `wp_oembed_add_discovery_links`
- Suppression du filtre oEmbed
- Suppression du endpoint REST oEmbed

### 4. Contrôle du Heartbeat (`DisableHeartbeat`)
**Impact estimé : réduction significative des requêtes AJAX admin**

- Désactivation complète sur le front-end
- Réduction de la fréquence en admin (60s par défaut, configurable)
- Option de garder le heartbeat uniquement sur post.php/post-new.php

### 5. Optimisation des Assets (`DeferScripts`, `AssetOptimizer`)
**Impact estimé : amélioration FCP/LCP de 20-40%**

- Ajout de l'attribut `defer` aux scripts non-critiques
- Ajout de l'attribut `async` aux scripts tiers
- Liste configurable de scripts à exclure du defer
- Suppression des query strings de version (`?ver=x.x.x`) pour améliorer le cache CDN
- Inline des petits fichiers CSS critiques (< 1KB configurable)

### 6. Optimisation des Images (`OptimizeImages`, `ImageOptimizer`)
**Impact estimé : amélioration LCP de 15-30%, réduction bande passante**

- Activation forcée du lazy loading natif (`loading="lazy"`)
- Exclusion des images above-the-fold (premières N images configurables)
- Ajout automatique des attributs `width`/`height` manquants
- Ajout de `decoding="async"` sur les images
- Ajout de `fetchpriority="high"` sur l'image LCP

### 7. Minification HTML (`MinifyHtml`)
**Impact estimé : réduction de 15-25% de la taille HTML**

- Suppression des commentaires HTML (sauf IE conditionnels)
- Suppression des espaces blancs multiples
- Suppression des attributs optionnels (type="text/javascript", etc.)
- Activation via output buffering sur `template_redirect`

### 8. Resource Hints (`AddResourceHints`)
**Impact estimé : amélioration TTFB et FCP de 100-300ms**

- `dns-prefetch` pour les domaines tiers fréquents (Google Fonts, analytics, CDN)
- `preconnect` pour les ressources critiques
- `preload` pour les fonts et CSS critiques
- `prefetch` pour les pages probables (navigation anticipée)
- Configuration déclarative via paramètres DI

### 9. Nettoyage Base de Données (`DatabaseOptimizer`, `CleanupDatabase`)
**Impact estimé : réduction de 30-70% de la taille BDD sur sites existants**

- Suppression des révisions de posts (avec limite configurable)
- Suppression des auto-drafts
- Suppression des posts et commentaires dans la corbeille
- Suppression des transients expirés
- Suppression des commentaires spam
- Optimisation des tables (OPTIMIZE TABLE)
- Nettoyage des options `autoload` non nécessaires
- Nettoyage des métadonnées orphelines (`postmeta`, `usermeta`, `commentmeta`)

### 10. Gestion du Cache (`CacheManager`)
**Impact estimé : réduction TTFB de 50-90%**

- Intégration avec le système de cache PSR-16 existant
- Page cache pour les visiteurs non-connectés (via transients ou filesystem)
- Object cache awareness (détection Redis/Memcached)
- Cache des queries WP_Query fréquentes
- Invalidation intelligente à la mise à jour d'un contenu

### 11. Optimisation WooCommerce (`OptimizeWooCommerce`)
**Impact estimé : -200-500 KB sur les pages non-shop**

- Désactivation des scripts/styles WC sur les pages non-WooCommerce
- Désactivation des fragments cart AJAX sur les pages sans panier
- Désactivation du widget cart sur les pages non-pertinentes
- Détection automatique de la présence de WooCommerce

### 12. Désactivation XML-RPC (`DisableXMLRPC`)
**Impact estimé : sécurité + réduction surface d'attaque**

- Désactivation complète de `xmlrpc.php`
- Suppression du lien pingback du head
- Filtrage des headers X-Pingback

### 13. Limitation des Révisions (`LimitPostRevisions`)
**Impact estimé : prévention de la croissance BDD**

- Limite configurable du nombre de révisions par post (défaut : 5)
- Fonctionne via le filtre `wp_revisions_to_keep`

### 14. Nettoyage Dashboard Admin (`CleanDashboard`)
**Impact estimé : amélioration UX admin + réduction requêtes**

- Suppression des widgets dashboard par défaut non essentiels
- Suppression du nag de mise à jour WordPress (optionnel)
- Suppression des meta boxes inutiles sur l'éditeur

---

## Configuration par défaut (FrameworkConfiguration)

```php
// Performance
'framework.performance.enabled' => true,

// Head cleanup
'framework.performance.clean_head' => true,
'framework.performance.disable_emojis' => true,
'framework.performance.disable_embeds' => true,
'framework.performance.disable_xmlrpc' => true,

// Heartbeat
'framework.performance.heartbeat.disable_frontend' => true,
'framework.performance.heartbeat.admin_interval' => 60,

// Assets
'framework.performance.defer_scripts' => true,
'framework.performance.defer_exclude' => ['jquery-core'],
'framework.performance.remove_query_strings' => true,
'framework.performance.inline_small_css' => true,
'framework.performance.inline_css_max_size' => 1024,

// Images
'framework.performance.lazy_load_images' => true,
'framework.performance.lazy_load_skip_first' => 1,
'framework.performance.add_missing_dimensions' => false,
'framework.performance.add_decoding_async' => true,
'framework.performance.add_fetchpriority' => true,

// HTML
'framework.performance.minify_html' => false,

// Resource hints
'framework.performance.resource_hints.preconnect' => [],
'framework.performance.resource_hints.dns_prefetch' => [],
'framework.performance.resource_hints.preload' => [],

// Database
'framework.performance.db_cleanup.revisions_limit' => 5,

// WooCommerce
'framework.performance.woocommerce_optimize' => true,

// Cache
'framework.performance.page_cache.enabled' => false,
'framework.performance.page_cache.ttl' => 3600,
'framework.performance.query_cache.enabled' => true,
'framework.performance.query_cache.ttl' => 300,
```

---

## Intégration dans le Framework

### 1. Enregistrement DI (`WordPressExtension`)

Ajouter dans `registerPortBindings()` :
```php
$containerBuilder->register(AssetOptimizerInterface::class, WordPressAssetOptimizer::class)->setAutowired(true);
$containerBuilder->register(DatabaseOptimizerInterface::class, WordPressDatabaseOptimizer::class)->setAutowired(true);
$containerBuilder->register(HtmlOptimizerInterface::class, WordPressHtmlOptimizer::class)->setAutowired(true);
$containerBuilder->register(ImageOptimizerInterface::class, WordPressImageOptimizer::class)->setAutowired(true);
$containerBuilder->register(CacheManagerInterface::class, WordPressCacheManager::class)->setAutowired(true);
$containerBuilder->register(PreloadStrategyInterface::class, WordPressPreloadStrategy::class)->setAutowired(true);
```

### 2. Autoconfiguration

Les hooks Performance implémentent `HookInterface` existant, donc ils seront automatiquement taggés `wordpress.hook` et enregistrés via `RegisterHookPass`.

### 3. Configuration

Ajouter les paramètres par défaut dans `FrameworkConfiguration::getDefaults()`.

---

## Ordre d'implémentation recommandé

### Phase 1 - Quick wins (impact immédiat, faible complexité)
1. `CleanHead` - nettoyage wp_head
2. `DisableEmojis` - suppression emojis
3. `DisableEmbeds` - suppression embeds
4. `DisableXMLRPC` - désactivation XML-RPC
5. `LimitPostRevisions` - limitation révisions

### Phase 2 - Optimisation assets (impact fort sur les Core Web Vitals)
6. `DeferScripts` + `AssetOptimizer` - defer/async scripts
7. `OptimizeImages` + `ImageOptimizer` - lazy loading + dimensions
8. `AddResourceHints` + `PreloadStrategy` - preconnect/preload

### Phase 3 - Optimisations avancées
9. `MinifyHtml` + `HtmlOptimizer` - minification HTML
10. `DisableHeartbeat` - contrôle heartbeat
11. `OptimizeWooCommerce` - optimisation conditionnelle WC
12. `CleanDashboard` - nettoyage admin

### Phase 4 - Base de données & Cache
13. `DatabaseOptimizer` + `CleanupDatabase` + `PurgeTransients`
14. `CacheManager` + `FlushObjectCache` - page cache & query cache

### Phase 5 - Tests & Documentation
15. Tests unitaires pour chaque hook/optimizer
16. Configuration par défaut dans FrameworkConfiguration

---

## Résumé de l'impact attendu

| Catégorie | Métriques améliorées | Gain estimé |
|---|---|---|
| Clean Head + Emojis + Embeds | Taille page, requêtes HTTP | -80 KB, -5 requêtes |
| Defer/Async Scripts | FCP, LCP, TBT | 20-40% amélioration |
| Lazy Load Images | LCP, bande passante | 15-30% amélioration |
| Resource Hints | TTFB, FCP | 100-300ms gain |
| Minification HTML | Taille transfert | 15-25% réduction |
| Page Cache | TTFB | 50-90% réduction |
| DB Cleanup | Temps requêtes BDD | 30-70% réduction taille |
| WooCommerce | Taille page hors-shop | -200-500 KB |
