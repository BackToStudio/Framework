# Reference : Bundle Performance

Reference complete de toutes les classes du bundle Performance, organisees par domaine fonctionnel.

Namespace racine : `BackTo\Framework\Bundle\Performance`

---

## Cache de pages

### `Contracts\PageCacheInterface`

Port (interface) pour le cache de pages HTML.

| Methode | Description |
|---|---|
| `get(string $url): ?string` | Recupere une page en cache. Retourne `null` en cas de miss. |
| `put(string $url, string $html, int $ttl): void` | Stocke une page avec un TTL en secondes. |
| `invalidate(string $url): void` | Supprime une page specifique du cache. |
| `flush(): void` | Vide l'integralite du cache. |

### `Infrastructure\WordPressPageCache`

Implementation filesystem du `PageCacheInterface`.

**Stockage :** Chaque URL est hashee en MD5. Deux fichiers sont crees :
- `{cacheDir}/{md5}.html` : le contenu HTML
- `{cacheDir}/{md5}.meta` : metadonnees serialisees (URL, expiry, created)

**Constructeur :**

| Parametre | Type | Description |
|---|---|---|
| `$cacheDir` | `string` | Repertoire de stockage du cache |
| `$filesystem` | `?Filesystem` | Instance Symfony Filesystem (optionnel) |

### `CacheableRequestChecker`

Determine si la requete courante est eligible au cache de pages.

**Constructeur :**

| Parametre | Type | Defaut | Description |
|---|---|---|---|
| `$requestContext` | `RequestContextInterface` | - | Contexte de la requete HTTP |
| `$queryContext` | `QueryContextInterface` | - | Contexte de la requete WordPress |
| `$userContext` | `UserContextInterface` | - | Contexte utilisateur |
| `$excludedPrefixes` | `string[]` | `['/wp-admin', '/wp-json', '/wp-login.php', '/wp-cron.php', '/xmlrpc.php']` | Prefixes d'URL exclus |

**Methode :**

| Methode | Description |
|---|---|
| `isCacheable(): bool` | Retourne `true` si la requete est cacheable |

**Conditions de non-cacheabilite :**
- Page d'administration
- Methode HTTP differente de GET
- Utilisateur connecte
- Parametres de query string presents
- URL correspondant a un prefixe exclu

### `RequestUrlResolver`

Construit l'URL canonique de la requete courante pour servir de cle de cache.

| Methode | Description |
|---|---|
| `getCurrentUrl(): string` | URL canonique (scheme + host valide + path sans query string) |

### `Hooks\ServePageCache`

Sert les pages en cache et capture la sortie pour mise en cache.

**Hooks enregistres :**

| Hook | Callback | Priorite | Description |
|---|---|---|---|
| `init` (action) | `serveCachedPage` | 0 | Sert le cache si disponible (tres tot) |
| `template_redirect` (action) | `startOutputBuffering` | 10 | Demarre `ob_start` pour capturer la sortie |

**Constructeur :**

| Parametre | Type | Defaut | Description |
|---|---|---|---|
| `$ttl` | `int` | `3600` | Duree de vie du cache en secondes |

**En-tetes HTTP :**
- `X-Page-Cache: HIT` : page servie depuis le cache
- Commentaire HTML `<!-- X-Page-Cache: MISS -->` : page fraichement generee et mise en cache

**Pages exclues du buffering :** pages 404, pages de recherche.

### `Hooks\InvalidatePageCache`

Invalide automatiquement le cache lors des modifications de contenu.

**Hooks enregistres :**

| Hook | Callback | Description |
|---|---|---|
| `save_post` (action) | `onPostSaved` | Invalide le cache de l'article modifie |
| `deleted_post` (action) | `onPostDeleted` | Invalide le cache de l'article supprime |
| `transition_post_status` (action) | `onPostStatusChange` | Invalide l'article ; flush complet si publication/depublication |
| `comment_post` (action) | `onCommentChange` | Invalide l'article lie au commentaire |
| `edit_comment` (action) | `onCommentChange` | Invalide l'article lie au commentaire |
| `switch_theme` (action) | `flushAll` | Vide tout le cache |
| `customize_save_after` (action) | `flushAll` | Vide tout le cache |

### `Hooks\PreloadPageCache`

Rechauffe le cache en arriere-plan apres modification de contenu via WP-Cron.

**Hooks enregistres :**

| Hook | Callback | Priorite | Description |
|---|---|---|---|
| `save_post` (action) | `schedulePostPreload` | 20 | Planifie le preload de l'article |
| `transition_post_status` (action) | `scheduleOnPublish` | 20 | Planifie le preload lors de publication |
| `switch_theme` (action) | `scheduleFullPreload` | 10 | Planifie un preload complet du site |
| `customize_save_after` (action) | `scheduleFullPreload` | 10 | Planifie un preload complet du site |
| `btf_preload_page_cache` (action) | `executePostPreload` | 10 | Execute le preload d'un article |
| `btf_preload_page_cache_full` (action) | `executeFullPreload` | 10 | Execute le preload complet |

**Constantes :**

| Constante | Valeur | Description |
|---|---|---|
| `CRON_HOOK` | `btf_preload_page_cache` | Hook cron pour preload article |
| `CRON_FULL_HOOK` | `btf_preload_page_cache_full` | Hook cron pour preload complet |

**Constructeur :**

| Parametre | Type | Defaut | Description |
|---|---|---|---|
| `$delay` | `int` | `5` | Delai en secondes avant le declenchement du cron |

### `PreloadUrlCollector`

Collecte les URLs a rechauffer pour le cache.

| Methode | Description |
|---|---|
| `getPostRelatedUrls(int $postId): string[]` | URLs liees a un article (permalink, archives, taxonomies, auteur, date, accueil) |
| `getSiteUrls(): string[]` | URLs du site (accueil, articles recents, pages, categories, tags) |

**Constructeur :**

| Parametre | Type | Defaut | Description |
|---|---|---|---|
| `$batchSize` | `int` | `50` | Nombre maximum d'URLs par lot |

### `PreloadExecutor`

Execute le preloading via des requetes HTTP non-bloquantes en loopback.

| Methode | Description |
|---|---|
| `preload(string[] $urls): void` | Envoie des requetes GET pour chaque URL non deja en cache |

**En-tetes envoyes :** `X-Cache-Preload: 1`, `Cache-Control: no-cache`.

---

## Nettoyage du head

### `Hooks\CleanHead`

Supprime les balises inutiles du `wp_head`.

**Actions supprimees :**

| Hook | Callback | Description |
|---|---|---|
| `wp_head` | `rsd_link` | Lien RSD (Really Simple Discovery) |
| `wp_head` | `wlwmanifest_link` | Manifeste Windows Live Writer |
| `wp_head` | `wp_shortlink_wp_head` | Shortlink |
| `wp_head` | `rest_output_link_wp_head` | Lien API REST |
| `wp_head` | `wp_oembed_add_discovery_links` | Liens decouverte oEmbed |
| `wp_head` | `adjacent_posts_rel_link_wp_head` | Liens articles adjacents (prev/next) |
| `wp_head` | `wp_generator` | Balise meta generator WordPress |
| `wp_head` | `feed_links` | Liens flux RSS principaux |
| `wp_head` | `feed_links_extra` | Liens flux RSS supplementaires |

**Filtres ajoutes :**

| Hook | Callback | Description |
|---|---|---|
| `wp_resource_hints` | `removeSWOrgDnsPrefetch` | Supprime le dns-prefetch vers `s.w.org` |

**Option de configuration :** `performance.clean_head` (defaut : `true`)

### `Hooks\DisableEmojis`

Supprime les scripts et styles emojis WordPress (~30 Ko).

**Actions supprimees :**

| Hook | Callback | Priorite |
|---|---|---|
| `wp_head` | `print_emoji_detection_script` | 7 |
| `admin_print_scripts` | `print_emoji_detection_script` | - |
| `wp_print_styles` | `print_emoji_styles` | - |
| `admin_print_styles` | `print_emoji_styles` | - |

**Filtres ajoutes :**

| Hook | Callback | Description |
|---|---|---|
| `wp_resource_hints` | `removeEmojiDnsPrefetch` | Supprime dns-prefetch `svn.wordpress.org` et `s.w.org` |
| `tiny_mce_plugins` | `removeTinyMceEmoji` | Supprime le plugin `wpemoji` de TinyMCE |
| `emoji_svg_url` | `__return_false` | Desactive l'URL SVG des emojis |

**Option de configuration :** `performance.disable_emojis` (defaut : `true`)

### `Hooks\DisableEmbeds`

Desactive la fonctionnalite oEmbed de WordPress (~7 Ko).

**Actions et filtres :**

| Hook | Type | Description |
|---|---|---|
| `wp_head` / `wp_oembed_add_discovery_links` | Supprime | Liens decouverte oEmbed |
| `wp_head` / `wp_oembed_add_host_js` | Supprime | Script host oEmbed |
| `wp_footer` | Action | Desenregistre le script `wp-embed` |
| `embed_oembed_discover` | Filtre | Retourne `false` |
| `rewrite_rules_array` | Filtre | Supprime les regles de reecriture `embed=true` |

**Option de configuration :** `performance.disable_embeds` (defaut : `true`)

### `Hooks\DisableXMLRPC`

Desactive XML-RPC et les en-tetes pingback.

**Hooks :**

| Hook | Type | Description |
|---|---|---|
| `xmlrpc_enabled` | Filtre | Retourne `false` |
| `wp_headers` | Filtre | Supprime l'en-tete `X-Pingback` |
| `wp_head` / `rsd_link` | Supprime | Lien RSD |

**Option de configuration :** `performance.disable_xmlrpc` (defaut : `true`)

### `Hooks\DisableHeartbeat`

Controle l'API Heartbeat de WordPress.

**Hooks :**

| Hook | Callback | Description |
|---|---|---|
| `init` (action) | `deregisterHeartbeatOnFrontend` | Desenregistre le script `heartbeat` sur le frontend |
| `heartbeat_settings` (filtre) | `setAdminInterval` | Modifie l'intervalle dans l'admin |

**Options de configuration :**

| Parametre | Defaut | Description |
|---|---|---|
| `performance.heartbeat.disable_frontend` | `true` | Desactiver sur le frontend |
| `performance.heartbeat.admin_interval` | `60` | Intervalle admin (secondes) |

---

## Assets

### `Hooks\DeferScripts`

Ajoute l'attribut `defer` aux scripts et supprime les query strings de version.

**Hooks :**

| Hook | Callback | Description |
|---|---|---|
| `script_loader_tag` (filtre) | `addDeferAttribute` | Ajoute `defer` sauf scripts exclus et admin |
| `script_loader_src` (filtre) | `removeVersionQueryString` | Supprime `?ver=...` des scripts |
| `style_loader_src` (filtre) | `removeVersionQueryString` | Supprime `?ver=...` des styles |

**Options de configuration :**

| Parametre | Defaut | Description |
|---|---|---|
| `performance.defer_scripts` | `true` | Activer le defer |
| `performance.defer_exclude` | `['jquery-core', 'jquery-migrate']` | Handles exclus du defer |
| `performance.remove_query_strings` | `true` | Supprimer les query strings `?ver=` |

### `Hooks\AddResourceHints`

Ajoute des resource hints (preconnect, dns-prefetch, preload).

**Hooks :**

| Hook | Callback | Priorite | Description |
|---|---|---|---|
| `wp_resource_hints` (filtre) | `addHints` | 10 | Ajoute les hints preconnect et dns-prefetch |
| `wp_head` (action) | `addPreloadLinks` | 1 | Injecte les balises `<link rel="preload">` |

**Options de configuration :**

| Parametre | Defaut | Description |
|---|---|---|
| `performance.resource_hints.preconnect` | `[]` | Origines a preconnecter |
| `performance.resource_hints.dns_prefetch` | `[]` | Domaines pour dns-prefetch |
| `performance.resource_hints.preload` | `[]` | Ressources a precharger (`[{url, as, type?}]`) |

---

## Images

### `Hooks\OptimizeImages`

Optimise les attributs de chargement des images.

**Hooks :**

| Hook | Callback | Description |
|---|---|---|
| `wp_get_attachment_image_attributes` (filtre) | `addDecodingAsync` | Ajoute `decoding="async"` |
| `wp_content_img_tag` (filtre) | `addFetchPriorityToLcp` | Ajoute `fetchpriority="high"` aux N premieres images |
| `wp_lazy_loading_enabled` (filtre) | `enableLazyLoading` | Active le lazy-loading natif |

**Options de configuration :**

| Parametre | Defaut | Description |
|---|---|---|
| `performance.lazy_load_skip_first` | `1` | Nombre d'images sans lazy-load (recevant fetchpriority) |
| `performance.add_decoding_async` | `true` | Ajouter `decoding="async"` |
| `performance.add_fetchpriority` | `true` | Ajouter `fetchpriority="high"` |

---

## HTML / CSS

### `Hooks\MinifyHtml`

Minifie la sortie HTML via output buffering.

**Hooks :**

| Hook | Callback | Description |
|---|---|---|
| `template_redirect` (action) | `startBuffering` | Demarre `ob_start` (sauf admin) |

**Option de configuration :** `performance.minify_html` (defaut : `false`)

Delegue a `HtmlOptimizerInterface` pour la minification effective.

### `Contracts\HtmlOptimizerInterface`

| Methode | Description |
|---|---|
| `optimize(string $html): string` | Optimise le contenu HTML |

### `Infrastructure\WordPressHtmlOptimizer`

Implementation de `HtmlOptimizerInterface`. Pipeline de minification en 9 etapes :

1. Preservation des blocs `<pre>`, `<code>`, `<textarea>`
2. Minification du CSS inline via `CssMinifier`
3. Minification du JS inline via `JsMinifier` (sauf JSON-LD, importmaps)
4. Suppression des commentaires HTML (sauf conditionnels IE)
5. Reduction des espaces entre balises de bloc
6. Reduction des espaces entre balises de bloc et placeholders
7. Reduction des espaces multiples entre balises inline
8. Suppression des attributs `type="text/javascript"` et `type="text/css"`
9. Restauration des blocs preserves

### `Infrastructure\CssMinifier`

Minificateur CSS inline.

| Methode | Description |
|---|---|
| `minify(string $css): string` | Minifie le CSS |

**Operations :** suppression commentaires, reduction espaces, suppression espaces autour de `{}:;,>~+`, suppression `;` avant `}`, `0px` vers `0`, `0.5` vers `.5`, `#aabbcc` vers `#abc`.

### `Infrastructure\JsMinifier`

Minificateur JavaScript inline (approche conservative).

| Methode | Description |
|---|---|
| `minify(string $js): string` | Minifie le JS |

**Operations :** extraction/restauration des chaines litterales, suppression commentaires (sauf `/*!`), reduction espaces/tabulations, suppression espaces autour des operateurs, restauration des espaces apres les mots-cles (`var`, `return`, `function`, etc.).

### `Hooks\RemoveUnusedCss`

Supprime les regles CSS inutilisees des blocs `<style>` inline.

**Hooks :**

| Hook | Callback | Priorite | Description |
|---|---|---|---|
| `template_redirect` (action) | `startBuffering` | 9 | Demarre `ob_start` (avant MinifyHtml) |

**Option de configuration :** `performance.remove_unused_css` (defaut : `false`)

**Methode statique :**

| Methode | Description |
|---|---|
| `process(string $html, array $preserveIds): string` | Traite le HTML et supprime le CSS inutilise |

**IDs de blocs preserves par defaut :** `['global-styles-inline-css']`

### `Css\HtmlSelectorExtractor`

Extrait les selecteurs CSS references dans le balisage HTML.

| Methode | Description |
|---|---|
| `extract(string $markup): array` | Retourne `{classes, ids, tags}` avec des maps `<string, true>` |

### `Css\CssRuleFilter`

Filtre les regles CSS en ne conservant que celles dont les selecteurs sont utilises.

| Methode | Description |
|---|---|
| `filter(string $css, array $selectors): string` | Filtre le CSS. Les `@-rules` sont toujours conservees. |

### `Css\SelectorMatcher`

Determine si un selecteur CSS est reference dans le balisage.

| Methode | Description |
|---|---|
| `isSelectorUsed(string $selector, array $selectors): bool` | `true` si le selecteur correspond a des elements trouves |

**Regles de conservation :**
- Selecteur universel `*`, `:root`, `html`, `body` : toujours conserves
- Proprietes custom CSS (`--`) : toujours conservees
- Selecteurs multiples (virgule) : conserves si au moins un sous-selecteur correspond
- Selecteurs composes (`.foo.bar`) : toutes les classes doivent etre presentes
- Selecteurs descendants (`.foo .bar`) : seul le sujet (dernier element) est verifie

---

## Serveur (.htaccess)

### `Hooks\OptimizeHtaccess`

Injecte des directives Apache dans le `.htaccess` racine.

**Hooks :**

| Hook | Callback | Description |
|---|---|---|
| `admin_init` (action) | `applyDirectives` | Re-applique les directives |

**Interface implementee :** `ActivationHooks` (appelle `applyDirectives` a l'activation, `removeDirectives` a la desactivation).

**Constante :** `MARKER = 'BackTo Performance'` (marqueur `.htaccess`)

**Options de configuration :**

| Parametre | Defaut | Description |
|---|---|---|
| `performance.htaccess.gzip` | `true` | Compression gzip via `mod_deflate` |
| `performance.htaccess.browser_cache` | `true` | Cache navigateur via `mod_expires` + `Cache-Control` |
| `performance.htaccess.remove_etags` | `true` | Suppression des ETags |
| `performance.htaccess.keep_alive` | `true` | Connexions persistantes |
| `performance.htaccess.static_ttl` | `31536000` | TTL assets statiques (secondes) |

**Directives gzip :** `mod_deflate` pour text/html, text/css, text/javascript, application/json, fonts, SVG. Exclut les fichiers deja compresses (gif, jpeg, png, webp, avif, woff2, etc.).

**Directives cache navigateur :** `mod_expires` avec TTL par type MIME. HTML a TTL 0 (gere par le cache de pages). Assets statiques avec TTL configurable. En-tetes `Cache-Control: public, max-age=..., immutable` pour les assets statiques.

**Optimisation d'ecriture :** Un hash MD5 des directives est stocke en transient (`backto_htaccess_hash`) pour eviter les ecritures redondantes.

---

## Base de donnees

### `Contracts\DatabaseOptimizerInterface`

| Methode | Description |
|---|---|
| `cleanup(): array<string, int>` | Execute toutes les taches de nettoyage. Retourne le nombre de lignes supprimees par tache. |
| `optimizeTables(): int` | Optimise les tables (`OPTIMIZE TABLE`). Retourne le nombre de tables optimisees. |

### `Infrastructure\WordPressDatabaseOptimizer`

Implementation WordPress du `DatabaseOptimizerInterface`.

**Constructeur :**

| Parametre | Type | Defaut | Description |
|---|---|---|---|
| `$revisionsLimit` | `int` | `5` | Nombre max de revisions a conserver par article |

**Taches de nettoyage :**

| Cle | Description |
|---|---|
| `revisions` | Supprime les revisions excedentaires (au-dela de la limite) |
| `auto_drafts` | Supprime les brouillons automatiques |
| `trashed_posts` | Supprime les articles dans la corbeille |
| `spam_comments` | Supprime les commentaires spam |
| `trashed_comments` | Supprime les commentaires dans la corbeille |
| `expired_transients` | Supprime les transients expires |
| `orphaned_postmeta` | Supprime les metadonnees d'articles orphelines |
| `orphaned_commentmeta` | Supprime les metadonnees de commentaires orphelines |

**Option de configuration :** `performance.db_cleanup.revisions_limit` (defaut : `5`)

### `Hooks\LimitPostRevisions`

Limite le nombre de revisions conservees par article.

**Hooks :**

| Hook | Callback | Description |
|---|---|---|
| `wp_revisions_to_keep` (filtre) | `limitRevisions` | Retourne la limite configuree |

**Option de configuration :** `performance.revisions_limit` (defaut : `5`)

### `Hooks\CleanDashboard`

Supprime les widgets inutiles du tableau de bord WordPress.

**Hooks :**

| Hook | Callback | Description |
|---|---|---|
| `wp_dashboard_setup` (action) | `removeDashboardWidgets` | Supprime 6 meta boxes + panneau de bienvenue |

**Interface implementee :** `AdminHooks` (hooks enregistres uniquement dans le contexte admin).

---

## WooCommerce

### `Hooks\OptimizeWooCommerce`

Desenfile les assets WooCommerce sur les pages non-WC.

**Hooks :**

| Hook | Callback | Priorite | Description |
|---|---|---|---|
| `wp_enqueue_scripts` (action) | `dequeueWooCommerceAssets` | 99 | Desenfile styles et scripts WC |

**Condition :** N'est actif que si la classe `WooCommerce` est chargee.

**Option de configuration :** `performance.woocommerce_optimize` (defaut : `true`)

---

## Configuration

### `PerformanceConfigurator`

Configurateur fluent pour les parametres du bundle. Utilise dans `config/performance.php`.

**Toutes les methodes retournent `self` pour le chainage :**

| Methode | Parametre cle | Defaut |
|---|---|---|
| `cleanHead(bool)` | `performance.clean_head` | `true` |
| `disableEmojis(bool)` | `performance.disable_emojis` | `true` |
| `disableEmbeds(bool)` | `performance.disable_embeds` | `true` |
| `disableXmlrpc(bool)` | `performance.disable_xmlrpc` | `true` |
| `heartbeatDisableFrontend(bool)` | `performance.heartbeat.disable_frontend` | `true` |
| `heartbeatAdminInterval(int)` | `performance.heartbeat.admin_interval` | `60` |
| `deferScripts(bool)` | `performance.defer_scripts` | `true` |
| `deferExclude(array)` | `performance.defer_exclude` | `['jquery-core', 'jquery-migrate']` |
| `removeQueryStrings(bool)` | `performance.remove_query_strings` | `true` |
| `lazyLoadSkipFirst(int)` | `performance.lazy_load_skip_first` | `1` |
| `addDecodingAsync(bool)` | `performance.add_decoding_async` | `true` |
| `addFetchpriority(bool)` | `performance.add_fetchpriority` | `true` |
| `minifyHtml(bool)` | `performance.minify_html` | `false` |
| `removeUnusedCss(bool)` | `performance.remove_unused_css` | `false` |
| `preconnect(array)` | `performance.resource_hints.preconnect` | `[]` |
| `dnsPrefetch(array)` | `performance.resource_hints.dns_prefetch` | `[]` |
| `preload(array)` | `performance.resource_hints.preload` | `[]` |
| `revisionsLimit(int)` | `performance.revisions_limit` | `5` |
| `woocommerceOptimize(bool)` | `performance.woocommerce_optimize` | `true` |
| `pageCacheEnabled(bool)` | `performance.page_cache.enabled` | `false` |
| `pageCacheTtl(int)` | `performance.page_cache.ttl` | `3600` |
| `dbCleanupRevisionsLimit(int)` | `performance.db_cleanup.revisions_limit` | `5` |
| `cachePreloadEnabled(bool)` | `performance.cache_preload.enabled` | `true` |
| `cachePreloadDelay(int)` | `performance.cache_preload.delay` | `5` |
| `cachePreloadBatchSize(int)` | `performance.cache_preload.batch_size` | `50` |
| `htaccessGzip(bool)` | `performance.htaccess.gzip` | `true` |
| `htaccessBrowserCache(bool)` | `performance.htaccess.browser_cache` | `true` |
| `htaccessRemoveEtags(bool)` | `performance.htaccess.remove_etags` | `true` |
| `htaccessKeepAlive(bool)` | `performance.htaccess.keep_alive` | `true` |
| `htaccessStaticTtl(int)` | `performance.htaccess.static_ttl` | `31536000` |

### `PerformanceConfiguration`

Valeurs par defaut des parametres. Appliquees au `ContainerBuilder` si le parametre n'a pas deja ete defini.

| Methode | Description |
|---|---|
| `getDefaults(): array<string, mixed>` | Retourne toutes les valeurs par defaut |
| `apply(ContainerBuilder $containerBuilder): void` | Applique les defauts au conteneur |

### `PerformanceExtension`

Extension du bundle. Enregistre les bindings de ports (interfaces vers implementations) et configure les hooks conditionnels.

**Bindings :**

| Interface | Implementation |
|---|---|
| `HtmlOptimizerInterface` | `WordPressHtmlOptimizer` |
| `DatabaseOptimizerInterface` | `WordPressDatabaseOptimizer` |
| `PageCacheInterface` | `WordPressPageCache` |

**Hooks conditionnels :** `MinifyHtml` et `RemoveUnusedCss` recoivent leur parametre `$enabled` depuis les parametres du conteneur.
