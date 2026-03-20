# Guides pratiques : Performance

Recettes pour accomplir des taches specifiques avec le bundle Performance.

## Configurer le TTL du cache de pages

Par defaut, les pages sont mises en cache pendant 3600 secondes (1 heure). Pour modifier cette duree :

```php
use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(7200); // 2 heures
};
```

Le TTL est verifie a la lecture du cache. Lorsqu'un fichier `.meta` indique une expiration depassee, le cache est automatiquement invalide et la page est regeneree lors de la prochaine visite.

> **Conseil** : Pour un site avec du contenu rarement modifie, un TTL eleve (86400 = 24h) est recommande. L'auto-invalidation garantit que les modifications de contenu sont immediatement prises en compte.

## Configurer les prefixes d'URL exclus du cache

Le `CacheableRequestChecker` exclut par defaut les prefixes suivants :

- `/wp-admin`
- `/wp-json`
- `/wp-login.php`
- `/wp-cron.php`
- `/xmlrpc.php`

Pour ajouter des prefixes personnalises, enregistrez un decorateur ou une definition de service dans votre extension :

```php
use BackTo\Framework\Bundle\Performance\CacheableRequestChecker;

$containerBuilder->getDefinition(CacheableRequestChecker::class)
    ->setArgument('$excludedPrefixes', [
        '/wp-admin',
        '/wp-json',
        '/wp-login.php',
        '/wp-cron.php',
        '/xmlrpc.php',
        '/mon-espace-prive',
        '/api/custom',
    ]);
```

Le checker refuse egalement la mise en cache pour :

- Les requetes non-GET
- Les utilisateurs connectes
- Les requetes avec parametres de query string
- Les pages d'administration

## Ajouter des resource hints (preconnect / preload)

### Preconnect

Le preconnect etablit une connexion anticipee vers un domaine tiers (DNS + TCP + TLS) :

```php
$performance->preconnect([
    'https://fonts.googleapis.com',
    'https://fonts.gstatic.com',
    'https://cdn.example.com',
]);
```

### DNS Prefetch

Le dns-prefetch effectue uniquement la resolution DNS, moins couteux que le preconnect :

```php
$performance->dnsPrefetch([
    '//analytics.example.com',
    '//pixel.tracking.com',
]);
```

### Preload

Le preload force le chargement anticipe de ressources critiques. Chaque ressource necessite une URL, un type `as` et optionnellement un `type` MIME :

```php
$performance->preload([
    ['url' => '/wp-content/themes/mon-theme/fonts/custom.woff2', 'as' => 'font', 'type' => 'font/woff2'],
    ['url' => '/wp-content/themes/mon-theme/css/critical.css', 'as' => 'style'],
    ['url' => '/wp-content/themes/mon-theme/js/app.js', 'as' => 'script'],
]);
```

> **Note** : L'attribut `crossorigin` est automatiquement ajoute pour les types `font` et `fetch`.

## Optimiser les images (lazy-load, fetchpriority)

### Controler le nombre d'images sans lazy-load

Par defaut, la premiere image (probablement l'element LCP) est exclue du lazy-load et recoit `fetchpriority="high"`. Pour exclure les N premieres images :

```php
$performance->lazyLoadSkipFirst(2); // Les 2 premieres images ne sont pas lazy-loaded
```

### Activer/desactiver le decodage asynchrone

L'attribut `decoding="async"` est ajoute par defaut aux images d'attachment :

```php
$performance->addDecodingAsync(true);  // Actif par defaut
```

### Activer/desactiver fetchpriority

L'attribut `fetchpriority="high"` est ajoute a la premiere image du contenu :

```php
$performance->addFetchpriority(true);  // Actif par defaut
```

Lorsque `fetchpriority="high"` est applique, l'attribut `loading="lazy"` est automatiquement retire de cette image pour eviter tout conflit.

## Supprimer le CSS inutilise

La suppression du CSS inutilise analyse le HTML genere et retire les regles CSS des blocs `<style>` inline qui ne correspondent a aucun element de la page :

```php
$performance->removeUnusedCss(true);
```

### Fonctionnement

1. Le HTML est intercepte via `ob_start` sur `template_redirect` (priorite 9, avant la minification)
2. Les blocs `<style>` sont temporairement retires pour analyser le balisage pur
3. `HtmlSelectorExtractor` extrait les classes, IDs et balises presentes dans le HTML
4. `CssRuleFilter` parcourt chaque bloc `<style>` et ne conserve que les regles dont les selecteurs correspondent a des elements trouves
5. Les blocs vides sont entierement supprimes

### Blocs preserves

Le bloc `global-styles-inline-css` est preserve par defaut. Les `@-rules` (`@media`, `@supports`, `@keyframes`, `@import`, etc.) sont toujours conservees.

## Limiter les revisions d'articles

Pour limiter le nombre de revisions conservees par article :

```php
$performance->revisionsLimit(3); // Conserver 3 revisions max par article
```

Le filtre `wp_revisions_to_keep` est utilise pour appliquer cette limite. La valeur par defaut est 5.

## Nettoyer le tableau de bord

Le `CleanDashboard` est automatiquement actif. Il supprime les widgets suivants du tableau de bord WordPress :

- Liens entrants (`dashboard_incoming_links`)
- Extensions (`dashboard_plugins`)
- Actualites WordPress (`dashboard_primary`, `dashboard_secondary`)
- Publication rapide (`dashboard_quick_press`)
- Brouillons recents (`dashboard_recent_drafts`)
- Panneau de bienvenue (`wp_welcome_panel`)

Cette optimisation est toujours active et ne necessite aucune configuration.

## Optimiser WooCommerce

La suppression des assets WooCommerce sur les pages non-WC est activee par defaut :

```php
$performance->woocommerceOptimize(true); // Actif par defaut
```

### Assets supprimes sur les pages non-WooCommerce

**Styles :**
- `woocommerce-general`
- `woocommerce-layout`
- `woocommerce-smallscreen`
- `wc-blocks-style`

**Scripts :**
- `wc-cart-fragments`
- `woocommerce`
- `wc-add-to-cart`

### Pages WooCommerce detectees

Les assets sont conserves sur les pages suivantes :

- Pages detectees par `is_woocommerce()`
- Panier (`is_cart()`)
- Commande (`is_checkout()`)
- Mon compte (`is_account_page()`)

> **Note** : Cette optimisation economise entre 200 et 500 Ko d'assets sur les pages qui n'utilisent pas WooCommerce. Elle n'est active que si la classe `WooCommerce` est chargee.

## Configurer le Heartbeat

### Desactiver le Heartbeat sur le frontend

Par defaut, le Heartbeat est desactive sur le frontend pour economiser les requetes AJAX :

```php
$performance->heartbeatDisableFrontend(true); // Actif par defaut
```

### Modifier l'intervalle admin

L'intervalle du Heartbeat dans l'administration est reduit a 60 secondes par defaut (contre 15 secondes nativement) :

```php
$performance->heartbeatAdminInterval(120); // 120 secondes
```

## Exclure des scripts du defer

Certains scripts ne doivent pas etre differes (par exemple jQuery si des scripts inline en dependent). Utilisez `deferExclude` :

```php
$performance->deferExclude([
    'jquery-core',
    'jquery-migrate',
    'mon-script-critique',
]);
```

## Personnaliser le TTL des assets statiques

Le TTL par defaut pour les assets statiques (CSS, JS, images, polices) dans le `.htaccess` est de 31 536 000 secondes (1 an) :

```php
$performance->htaccessStaticTtl(2592000); // 30 jours
```

> **Conseil** : Un TTL d'un an avec des noms de fichiers versionnes (hash dans le nom) est la strategie recommandee. Les fichiers HTML ne sont jamais mis en cache par le navigateur (`max-age=0`) car c'est le cache de pages serveur qui les gere.
