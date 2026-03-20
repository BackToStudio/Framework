# Tutoriel : Demarrer avec le bundle Performance

Ce tutoriel vous guide pas a pas pour activer et configurer les principales optimisations de performance du BackTo Framework. A la fin, votre site beneficiera du cache de pages, de la minification HTML, du chargement differe des scripts et de l'optimisation serveur Apache.

## Pre-requis

- BackTo Framework installe et fonctionnel
- Acces au fichier `config/performance.php` de votre projet
- Serveur Apache avec `mod_rewrite` active (pour les optimisations .htaccess)

## Etape 1 : Creer le fichier de configuration

Creez le fichier `config/performance.php` a la racine de votre theme ou plugin :

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    // Les optimisations seront ajoutees ici
};
```

> **Note** : Par defaut, de nombreuses optimisations sont deja actives (nettoyage du head, desactivation des emojis, defer des scripts, etc.). Ce fichier sert a personnaliser les valeurs par defaut ou a activer les fonctionnalites opt-in.

## Etape 2 : Activer le cache de pages

Le cache de pages stocke le HTML genere sur le systeme de fichiers et le sert directement aux visiteurs non connectes, sans executer PHP ni WordPress.

```php
return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(3600);   // 1 heure (valeur par defaut)
};
```

Verifiez que le cache fonctionne :

1. Deconnectez-vous de WordPress
2. Visitez une page de votre site
3. Rechargez la page
4. Inspectez les en-tetes HTTP : vous devriez voir `X-Page-Cache: HIT`
5. Dans le code source, un commentaire `<!-- Cached by BackTo Framework at ... UTC -->` confirme la mise en cache

Le cache est automatiquement invalide lorsque :

- Un article est publie, modifie ou supprime
- Un commentaire est poste ou modifie
- Le theme est change
- Le Customizer est sauvegarde

### Activer le preloading

Le preloading rechauffe le cache en arriere-plan apres chaque modification de contenu :

```php
$performance
    ->pageCacheEnabled(true)
    ->cachePreloadEnabled(true)
    ->cachePreloadDelay(5)       // Delai avant le preload (secondes)
    ->cachePreloadBatchSize(50); // Nombre max d'URLs par lot
```

## Etape 3 : Configurer l'optimisation .htaccess

L'optimisation .htaccess est activee par defaut. Elle injecte automatiquement des directives Apache pour la compression gzip, le cache navigateur, la suppression des ETags et le Keep-Alive.

Pour personnaliser chaque directive individuellement :

```php
$performance
    ->htaccessGzip(true)           // Compression gzip via mod_deflate
    ->htaccessBrowserCache(true)   // Cache navigateur via mod_expires
    ->htaccessRemoveEtags(true)    // Suppression des ETags
    ->htaccessKeepAlive(true)      // Connexions persistantes
    ->htaccessStaticTtl(31536000); // TTL assets statiques (1 an par defaut)
```

Les directives sont ecrites dans un bloc `# BEGIN BackTo Performance` / `# END BackTo Performance` du fichier `.htaccess`. Elles sont re-appliquees automatiquement sur `admin_init` et lors de l'activation du plugin.

Pour verifier, ouvrez votre fichier `.htaccess` a la racine du site et cherchez le bloc `BackTo Performance`.

## Etape 4 : Activer le chargement differe des scripts

Le chargement differe (`defer`) est active par defaut. Il ajoute l'attribut `defer` a toutes les balises `<script>` du frontend, sauf celles exclues :

```php
$performance
    ->deferScripts(true)
    ->deferExclude(['jquery-core', 'jquery-migrate']); // Exclusions par defaut
```

La suppression des query strings de version (`?ver=...`) est egalement activee par defaut pour ameliorer le taux de cache des CDN :

```php
$performance->removeQueryStrings(true);
```

Pour verifier, inspectez le code source d'une page et constatez l'attribut `defer` sur les balises `<script>`.

## Etape 5 : Activer la minification HTML

La minification HTML est desactivee par defaut car elle modifie le rendu de sortie. Activez-la explicitement :

```php
$performance->minifyHtml(true);
```

La minification effectue les operations suivantes :

1. Preserve le contenu des balises `<pre>`, `<code>` et `<textarea>`
2. Minifie le CSS inline (blocs `<style>`)
3. Minifie le JavaScript inline (blocs `<script>`, sauf JSON-LD et importmaps)
4. Supprime les commentaires HTML (sauf les conditionnels IE)
5. Reduit les espaces blancs entre les balises de bloc
6. Supprime les attributs `type="text/javascript"` et `type="text/css"`

Pour verifier, comparez la taille du code source avant et apres activation. La reduction typique est de 15 a 25%.

## Etape 6 : Activer la suppression du CSS inutilise

Cette fonctionnalite analyse le HTML genere et supprime les regles CSS des blocs `<style>` inline qui ne correspondent a aucun element de la page :

```php
$performance->removeUnusedCss(true);
```

> **Note** : Cette optimisation est particulierement efficace avec les themes a blocs (comme Twenty Twenty-Five) qui generent du CSS inline par bloc. Le bloc `global-styles-inline-css` est preserve par defaut.

## Configuration finale

Voici la configuration complete recommandee :

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        // Cache de pages
        ->pageCacheEnabled(true)
        ->pageCacheTtl(3600)
        ->cachePreloadEnabled(true)

        // Minification
        ->minifyHtml(true)
        ->removeUnusedCss(true)

        // Scripts
        ->deferScripts(true)
        ->removeQueryStrings(true)

        // Images
        ->lazyLoadSkipFirst(1)
        ->addDecodingAsync(true)
        ->addFetchpriority(true)

        // Resource hints
        ->preconnect(['https://fonts.googleapis.com', 'https://fonts.gstatic.com'])

        // .htaccess
        ->htaccessGzip(true)
        ->htaccessBrowserCache(true)
        ->htaccessRemoveEtags(true)
        ->htaccessKeepAlive(true)

        // Base de donnees
        ->revisionsLimit(5);
};
```

## Prochaines etapes

- Consultez les [guides pratiques](how-to.md) pour des recettes specifiques (resource hints, optimisation WooCommerce, configuration avancee du cache)
- Consultez la [reference](reference.md) pour la liste complete des classes, hooks et options
- Consultez l'[explication de l'architecture](explanation.md) pour comprendre le fonctionnement interne du cache et de la minification
