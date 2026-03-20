# Bundle Performance

Le bundle **Performance** optimise automatiquement les performances d'un site WordPress en agissant sur plusieurs axes : cache de pages, minification HTML, nettoyage du `<head>`, optimisation des assets, des images, du serveur Apache et de la base de donnees.

Namespace : `BackTo\Framework\Bundle\Performance`

## Vue d'ensemble

| Domaine | Fonctionnalite | Classe principale | Active par defaut |
|---|---|---|---|
| **Cache de pages** | Stockage fichier, auto-invalidation, preloading | `ServePageCache`, `InvalidatePageCache`, `PreloadPageCache` | Non |
| **Nettoyage du head** | Suppression RSD, WLW, shortlinks, generateur WP | `CleanHead` | Oui |
| **Emojis** | Suppression scripts/styles emojis (~30 Ko) | `DisableEmojis` | Oui |
| **Embeds** | Suppression oEmbed (~7 Ko) | `DisableEmbeds` | Oui |
| **XML-RPC** | Desactivation XML-RPC et pingback | `DisableXMLRPC` | Oui |
| **Heartbeat** | Desactivation frontend, intervalle admin reduit | `DisableHeartbeat` | Oui |
| **Scripts** | Attribut `defer`, suppression query strings | `DeferScripts` | Oui |
| **Images** | Lazy-load, `decoding=async`, `fetchpriority=high` | `OptimizeImages` | Oui |
| **Minification HTML** | Suppression whitespace, commentaires, CSS/JS inline | `MinifyHtml` | Non |
| **CSS inutilise** | Suppression regles CSS non referencees | `RemoveUnusedCss` | Non |
| **Resource hints** | Preconnect, dns-prefetch, preload | `AddResourceHints` | Configure |
| **Revisions** | Limitation du nombre de revisions par article | `LimitPostRevisions` | Oui (5) |
| **.htaccess** | Gzip, cache navigateur, ETags, Keep-Alive | `OptimizeHtaccess` | Oui |
| **Dashboard** | Suppression widgets inutiles du tableau de bord | `CleanDashboard` | Oui |
| **WooCommerce** | Suppression assets WC sur les pages non-WC | `OptimizeWooCommerce` | Oui |
| **Base de donnees** | Nettoyage revisions, brouillons, transients, spam | `WordPressDatabaseOptimizer` | Configure |

## Documentation

Ce bundle est documente selon le framework [Diataxis](https://diataxis.fr/) :

| Type | Fichier | Description |
|---|---|---|
| **Tutoriel** | [tutorial.md](tutorial.md) | Demarrage rapide : activer le cache, configurer .htaccess, defer, minification |
| **Guides pratiques** | [how-to.md](how-to.md) | Recettes pour des taches specifiques (TTL cache, resource hints, images, CSS, WooCommerce...) |
| **Reference** | [reference.md](reference.md) | Reference complete de toutes les classes, hooks et options de configuration |
| **Explication** | [explanation.md](explanation.md) | Architecture : flux du cache, pipeline de minification, tree-shaking CSS, strategie .htaccess |

## Configuration rapide

La configuration se fait via le fichier `config/performance.php` avec le configurateur fluent :

```php
use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(7200)
        ->minifyHtml(true)
        ->removeUnusedCss(true)
        ->deferScripts(true)
        ->htaccessGzip(true)
        ->htaccessBrowserCache(true);
};
```
