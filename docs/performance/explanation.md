# Explication : Architecture du bundle Performance

Ce document explique les choix de conception et le fonctionnement interne des principaux mecanismes du bundle Performance.

## Flux du cache de pages

Le cache de pages suit un cycle en cinq phases : **servir**, **capturer**, **stocker**, **invalider** et **precharger**.

### Phase 1 : Servir (ServePageCache)

Le hook `init` est enregistre a la priorite 0, la plus haute possible. C'est le tout premier code execute dans le cycle de vie WordPress apres le bootstrap.

```
Requete HTTP
    |
    v
[init priorite 0] → CacheableRequestChecker.isCacheable() ?
    |                       |
    | non                   | oui
    v                       v
(flux normal WP)      RequestUrlResolver.getCurrentUrl()
                            |
                            v
                      PageCacheInterface.get(url)
                            |
                    --------+--------
                    |               |
                    v               v
                  null           HTML cache
               (MISS)             (HIT)
                    |               |
                    v               v
            (flux normal)    header("X-Page-Cache: HIT")
                             echo $html
                             exit
```

L'appel a `exit` court-circuite l'integralite du cycle WordPress. Le temps de reponse passe de centaines de millisecondes a quelques millisecondes, car ni la base de donnees ni le theme ne sont charges.

### Phase 2 : Capturer (output buffering)

Si le cache est vide (MISS), le flux WordPress s'execute normalement. Sur `template_redirect`, un output buffer est demarre via `ob_start`. Les pages 404 et les resultats de recherche sont exclues car leur contenu est trop variable pour etre cache efficacement.

### Phase 3 : Stocker (captureOutput)

Le callback de l'output buffer recoit le HTML complet a la fin du rendu. Avant de le stocker, deux verifications sont effectuees :

1. Le HTML n'est pas vide
2. Le HTML ne contient pas `Fatal error` (pour eviter de cacher des pages d'erreur PHP)

Le fichier HTML est ecrit sur le disque avec un commentaire horodatage. Un fichier `.meta` associe contient l'URL originale, le timestamp de creation et le timestamp d'expiration (`time() + $ttl`).

### Phase 4 : Invalider (InvalidatePageCache)

L'invalidation est granulaire. Lorsqu'un article est modifie, seul son cache est supprime via le permalink. Un flush complet est declenche dans trois cas :

- **Changement de statut publication/depublication** : les archives, menus et listes d'articles changent
- **Changement de theme** : tout le rendu HTML est potentiellement different
- **Sauvegarde du Customizer** : les styles et la mise en page peuvent avoir change

Pour les commentaires, l'article parent est identifie via `ContentQueryInterface::getComment()` et son cache est invalide.

### Phase 5 : Precharger (PreloadPageCache)

Apres l'invalidation, le cache est froid. Le preloading le rechauffe proactivement via WP-Cron.

```
save_post / transition_post_status
    |
    v
schedulePostPreload(postId)
    |
    v
CronScheduler.scheduleSingle(
    'btf_preload_page_cache',
    time() + delay,
    [postId]
)
    |
    v
CronScheduler.spawn()  ← declenche WP-Cron immediatement
    |
    v
[cron execute]
    |
    v
PreloadUrlCollector.getPostRelatedUrls(postId)
    → permalink de l'article
    → page d'accueil
    → page du blog
    → archive du post type
    → archives de taxonomies (categories, tags)
    → page auteur
    → archives par date (annee, mois)
    |
    v
PreloadExecutor.preload(urls)
    → requetes HTTP non-bloquantes en loopback
    → header X-Cache-Preload: 1
    → chaque requete declenche ServePageCache
      qui genere et stocke le HTML
```

Le delai configurable (`$delay`, defaut 5 secondes) evite de surcharger le serveur immediatement apres une sauvegarde. La taille de lot (`$batchSize`, defaut 50) limite le nombre d'URLs preloadees en une seule execution.

Pour un preload complet (changement de theme, Customizer), `getSiteUrls()` collecte les articles recents, les pages, les archives de categories et les tags les plus populaires.

### Logique du CacheableRequestChecker

Le `CacheableRequestChecker` determine l'eligibilite d'une requete au cache selon une cascade de regles eliminatoires :

```
1. isAdmin() ?              → NON cacheable (page d'administration)
2. method !== 'GET' ?       → NON cacheable (POST, PUT, DELETE...)
3. isLoggedIn() ?           → NON cacheable (contenu personnalise)
4. hasQueryParams() ?       → NON cacheable (resultats variables)
5. URL commence par un
   prefixe exclu ?          → NON cacheable (/wp-admin, /wp-json, etc.)
6. Aucune exclusion         → CACHEABLE
```

L'ordre des verifications est optimise : les conditions les moins couteuses (admin, methode) sont testees en premier. La verification de l'utilisateur connecte est plus couteuse car elle implique la lecture d'un cookie de session.

Les prefixes exclus par defaut (`/wp-admin`, `/wp-json`, `/wp-login.php`, `/wp-cron.php`, `/xmlrpc.php`) couvrent tous les endpoints non-frontend de WordPress. Ils sont configurables via l'injection de dependances.

### Resolution d'URL (RequestUrlResolver)

La cle de cache est une URL canonique construite a partir de la requete courante :

1. Le schema est determine par `isSecure()` (HTTPS ou HTTP)
2. Le host est valide contre le `site_url` configure pour empecher le cache poisoning via l'en-tete `Host`
3. La query string est supprimee (les requetes avec parametres ne sont pas cachees)

Cette normalisation garantit qu'une meme page produit toujours la meme cle de cache, meme si les en-tetes HTTP varient.

## Pipeline de minification HTML

La minification HTML est orchestree par `WordPressHtmlOptimizer`, qui delegue aux minificateurs specialises `CssMinifier` et `JsMinifier`.

### Strategie de preservation

Le defi principal de la minification HTML est de ne pas alterer le contenu semantique. La strategie adoptee est une approche **extract-process-restore** :

1. **Extraction** : Les blocs sensibles (`<pre>`, `<code>`, `<textarea>`, `<style>`, `<script>`) sont remplaces par des placeholders (`<!--PRESERVED_0-->`, `<!--PRESERVED_1-->`, etc.)
2. **Traitement** : Le HTML restant est minifie agressivement (suppression commentaires, reduction espaces)
3. **Restauration** : Les placeholders sont remplaces par le contenu original (pre/code/textarea) ou le contenu minifie (style/script)

Cette approche garantit que :
- Le contenu pre-formatte conserve ses espaces et retours a la ligne
- Les chaines JavaScript ne sont pas corrompues par la reduction d'espaces
- Les selecteurs CSS ne sont pas alteres

### Minification CSS inline (CssMinifier)

Le `CssMinifier` applique une serie de transformations regex sur le CSS :

1. Suppression des commentaires CSS (`/* ... */`)
2. Reduction des espaces multiples en un seul espace
3. Suppression des espaces autour de `{ } ; : , > ~ +`
4. Suppression du dernier `;` avant `}`
5. Reduction de `0px` en `0` (toutes les unites : px, em, rem, %, vh, vw, etc.)
6. Reduction de `0.5` en `.5`
7. Reduction des couleurs hexadecimales : `#aabbcc` en `#abc`

### Minification JavaScript inline (JsMinifier)

Le `JsMinifier` utilise une approche conservative pour eviter de casser le code :

1. **Extraction des chaines** : Les littieraux entre guillemets sont extraits et remplaces par des placeholders (`\x00STR_0\x00`). Cela protege les chaines contenant des operateurs ou des mots-cles.
2. **Suppression des commentaires** : Commentaires mono-ligne (`//`) et multi-ligne (`/* */`), sauf les commentaires de licence (`/*! */`).
3. **Reduction des espaces** : Espaces et tabulations reduits, lignes vides supprimees.
4. **Suppression des espaces autour des operateurs** : `{ } ; , = : ? < > ! & | + - * / ^ ~ % ( )`
5. **Restauration des espaces apres les mots-cles** : `var`, `let`, `const`, `return`, `typeof`, `instanceof`, `new`, `function`, `class`, etc.
6. **Restauration des chaines** : Les placeholders sont remplaces par les litteraux originaux.

Les scripts JSON-LD (`type="application/ld+json"`), les importmaps (`type="importmap"`) et les scripts de type `application/json` ne sont pas minifies car ils contiennent des donnees structurees, pas du code executable.

### Distinction entre balises de bloc et inline

Le minificateur distingue les elements de bloc (ou les espaces entre balises fermantes et ouvrantes sont insignifiants) des elements inline (ou un espace peut etre significatif). Les espaces entre balises de bloc sont entierement supprimes (`></div><div>`) tandis que les espaces entre balises inline sont reduits a un seul espace (`> <`).

## Approche du tree-shaking CSS

Le tree-shaking CSS est une technique qui supprime les regles CSS dont les selecteurs ne correspondent a aucun element du HTML. L'implementation suit une architecture en trois classes, respectant le principe de responsabilite unique :

### 1. HtmlSelectorExtractor

Analyse le HTML (sans les blocs `<style>`) et extrait trois types d'identifiants :

- **Classes** : via l'attribut `class="..."` (chaque classe est separee)
- **IDs** : via l'attribut `id="..."`
- **Balises** : chaque balise HTML ouverte (`<div>`, `<p>`, `<span>`, etc.)

Le resultat est une structure de donnees avec trois maps `array<string, true>` pour des lookups en O(1).

### 2. SelectorMatcher

Determine si un selecteur CSS est "utilise" dans le HTML. Les regles de correspondance sont volontairement conservatrices pour eviter les faux positifs (supprimer du CSS necessaire) :

- **Selecteurs universels** (`*`, `:root`, `html`, `body`) : toujours conserves
- **Custom properties** (`--variable`) : toujours conservees
- **Selecteurs multiples** (`a, .b, #c`) : conserves si au moins un sous-selecteur correspond
- **Selecteurs composes** (`.foo.bar`) : toutes les classes doivent etre presentes dans le HTML
- **Selecteurs descendants** (`.parent .child`) : seul le sujet (le dernier element) est verifie
- **Pseudo-classes et pseudo-elements** (`:hover`, `::before`) : ignores pour le matching (l'element de base est verifie)

Cette approche privilegie la precision au detriment de l'agressivite. Il est preferable de conserver quelques regles inutiles plutot que de supprimer une regle necessaire qui causerait un defaut visuel.

### 3. CssRuleFilter

Parcourt le CSS regle par regle et applique `SelectorMatcher::isSelectorUsed()` a chaque selecteur. Les `@-rules` (`@media`, `@supports`, `@keyframes`, `@font-face`, `@import`, `@charset`) sont toujours conservees, car leur suppression pourrait casser des declarations critiques dans des contextes conditionnels.

Le parsing CSS est fait manuellement (sans regex) avec un compteur de profondeur d'accolades pour gerer correctement les blocs imbriques (`@media { .class { ... } }`).

### Priorite d'execution

`RemoveUnusedCss` est enregistre a la priorite 9 sur `template_redirect`, tandis que `MinifyHtml` utilise la priorite par defaut (10). Cela garantit que le tree-shaking CSS s'execute en premier sur le HTML brut, puis la minification reduit l'ensemble du resultat.

## Strategie des directives .htaccess

### Approche par marqueurs

Les directives sont injectees dans le `.htaccess` via la fonction WordPress `insert_with_markers()`, qui gere un bloc delimite par :

```
# BEGIN BackTo Performance
...directives...
# END BackTo Performance
```

Cette approche garantit que :
- Les directives du framework ne perturbent pas celles de WordPress ou d'autres plugins
- Les mises a jour ecrasent uniquement le bloc BackTo Performance
- La desactivation du plugin supprime proprement les directives

### Modules Apache cibles

Chaque groupe de directives est encapsule dans un `<IfModule>` pour une degradation gracieuse sur les serveurs ou le module n'est pas disponible :

**`mod_deflate`** : Compression gzip des ressources textuelles. Les fichiers deja compresses (images, videos, polices woff2) sont exclus via `SetEnvIfNoCase` pour eviter une double compression.

**`mod_expires`** : Cache navigateur avec des TTL differencies :
- HTML : TTL 0 (le cache de pages serveur gere ce cas)
- Assets statiques (CSS, JS, images, polices) : TTL configurable (defaut 1 an)
- Donnees (JSON, XML) : TTL 0 (reponses dynamiques)

**`mod_headers`** : Trois utilisations :
1. `Cache-Control: public, max-age=..., immutable` pour les assets statiques (l'option `immutable` evite les requetes conditionnelles)
2. `Cache-Control: no-cache, no-store, must-revalidate` pour HTML et donnees
3. Suppression de l'en-tete `ETag` pour eviter les revalidations inutiles
4. `Connection: keep-alive` pour les connexions persistantes

### Optimisation des ecritures

L'ecriture dans le `.htaccess` est une operation couteuse (lecture, parsing, ecriture). Pour eviter des ecritures redondantes a chaque `admin_init`, un hash MD5 des directives est stocke comme transient WordPress (cle : `backto_htaccess_hash`, TTL : 24h).

Le flux est :

```
admin_init
    |
    v
buildDirectives() → calcul MD5
    |
    v
MD5 identique au transient stocke ?
    |           |
    | oui       | non
    v           v
  (skip)    insert_with_markers()
            storeDirectivesHash()
```

Cela signifie qu'apres le premier chargement admin, les requetes suivantes ne declenchent aucune ecriture fichier tant que la configuration ne change pas.
