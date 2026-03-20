# Architecture du bundle SEO

Ce document explique les choix de conception, les patterns utilises et le fonctionnement interne du bundle SEO.

---

## Le pattern JSON-LD @graph

### Pourquoi un graphe ?

Les donnees structurees Schema.org peuvent etre exprimees de differentes manieres en JSON-LD. L'approche la plus simple consiste a emettre un objet JSON independant par entite. Le bundle SEO adopte une approche differente : il regroupe toutes les entites dans un unique objet `@graph`.

```json
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "WebSite", "@id": "https://example.com/#website", "..." : "..." },
    { "@type": "Organization", "@id": "https://example.com/#organization", "..." : "..." },
    { "@type": "Article", "@id": "https://example.com/article/#article", "..." : "..." },
    { "@type": "BreadcrumbList", "..." : "..." }
  ]
}
```

Cette approche presente plusieurs avantages :

1. **Une seule balise `<script>`** -- un seul point d'injection dans le `<head>`, plus facile a inspecter et a deboguer
2. **Conformite Google** -- Google recommande le format `@graph` pour les entites liees sur une meme page
3. **Pas de duplication** -- les entites partagees (Organization, WebSite) sont declarees une seule fois et referencees partout

### Le mecanisme @id

Chaque noeud du graphe peut recevoir un identifiant unique via la propriete `@id`. Les autres noeuds referencent cet identifiant au lieu de dupliquer les donnees.

Convention d'identifiants utilisee par le framework :

| Noeud | @id |
|---|---|
| WebSite | `{siteUrl}/#website` |
| Organization | `{siteUrl}/#organization` |
| Article | `{permalink}/#article` |

Lorsqu'un Article doit indiquer son editeur, il ne duplique pas l'Organisation complete. Il utilise une reference :

```json
{
  "@type": "Article",
  "publisher": { "@id": "https://example.com/#organization" }
}
```

Google et les autres moteurs de recherche resolvent automatiquement ces references dans le graphe.

La classe `SchemaRef` encapsule cette mecanique. L'appel `Schema::ref('#organization')` produit un objet qui se serialise en `{"@id": "#organization"}`. Les generateurs internes utilisent cette classe pour creer les liens entre WebSite, Organization et Article.

### Un ou plusieurs schemas ?

Le `SchemaManager` adapte automatiquement le format de sortie :

- **Un seul schema** : l'objet est emis directement avec `@context`, sans `@graph`
- **Plusieurs schemas** : un objet englobant avec `@context` et `@graph` est genere

Ce comportement est transparent pour le developpeur.

---

## Pipeline de generation des schemas

La generation des donnees structurees suit un pipeline en trois etapes, orchestre par les hooks WordPress.

### Etape 1 : RegisterDefaultSchemas (hook `wp`)

Le hook `RegisterDefaultSchemas` s'execute sur l'action `wp`, quand le contexte de la requete est connu (page courante, post type, etc.). Il effectue quatre operations dans l'ordre :

1. **WebSiteSchemaGenerator** -- genere un noeud `WebSite` avec le nom du site, l'URL, la description et un `SearchAction` pour le moteur de recherche interne
2. **OrganizationSchemaGenerator** -- genere un noeud `Organization` avec le nom du site, le logo (`custom_logo`) et les liens sociaux `sameAs` extraits du provider SEO
3. **PostTypeSchemaResolver** -- consulte le mapping `post_type_map` de la configuration pour trouver un generateur associe au post type courant (par defaut : `ArticleSchemaGenerator` pour `post`). S'execute uniquement sur les pages singulaires (`is_singular()`)
4. **BreadcrumbSchemaGeneratorInterface** -- genere un `BreadcrumbList` contextuel. L'implementation par defaut (`WordPressBreadcrumbSchemaGenerator`) construit le fil d'Ariane selon le type de page

Apres ces quatre etapes, l'action `framework/seo/schema` est declenchee, passant le `SchemaManager` comme parametre. C'est le point d'extension principal pour les themes : ils peuvent ajouter des schemas supplementaires (FAQ, Product, Event, etc.) ou modifier ceux existants.

### Etape 2 : SchemaManager (registre)

Le `SchemaManager` est un registre central. Chaque generateur y ajoute ses schemas via `add()`. Il accumule les `SchemaType` sans les transformer.

A cette etape, les schemas sont des objets PHP avec des proprietes typees. Aucun JSON n'a encore ete genere.

### Etape 3 : InjectSchemaInHead (hook `wp_head`)

Le hook `InjectSchemaInHead` s'execute sur `wp_head` avec la priorite 1 (parmi les premiers). Il appelle `SchemaManager::render()` qui :

1. Convertit chaque `SchemaType` en tableau via `toArray()`, resolvant recursivement les schemas imbriques et les `SchemaRef`
2. Construit l'enveloppe `@context` / `@graph`
3. Encode en JSON avec `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
4. Enveloppe le JSON dans une balise `<script type="application/ld+json">`

Le resultat est ecrit directement dans le `<head>` via `echo`.

### Diagramme du pipeline

```
wp (action WordPress)
  └── RegisterDefaultSchemas::register()
        ├── WebSiteSchemaGenerator::generate()     ──► SchemaManager::add()
        ├── OrganizationSchemaGenerator::generate() ──► SchemaManager::add()
        ├── PostTypeSchemaResolver::resolve()       ──► SchemaManager::add()
        ├── BreadcrumbSchemaGenerator::generate()   ──► SchemaManager::add()
        └── do_action('framework/seo/schema')       ──► [theme/plugins ajoutent des schemas]

wp_head (action WordPress, priorite 1)
  └── InjectSchemaInHead::render()
        └── SchemaManager::render()
              ├── SchemaType::toArray()  (pour chaque schema)
              ├── Construction @graph
              └── json_encode() + <script>
```

---

## Mapping post type vers generateur

Le `PostTypeSchemaResolver` permet d'associer un generateur de schema a chaque post type via le fichier `config/seo.php` :

```php
'post_type_map' => [
    'post'       => ArticleSchemaGenerator::class,
    'formation'  => CourseSchemaGenerator::class,
    'evenement'  => EventSchemaGenerator::class,
    'produit'    => ProductSchemaGenerator::class,
],
```

Chaque generateur doit exposer une methode `generate(?int $postId): ?SchemaType`. Le resolver :

1. Verifie que la page courante est singuliere (`is_singular()`)
2. Recupere le `post_type` du post courant
3. Cherche un generateur correspondant dans le mapping
4. Appelle `generate($postId)` et ajoute le resultat au `SchemaManager`

Cette approche permet aux themes de mapper n'importe quel CPT a n'importe quel type Schema.org sans modifier le code du framework. Le generateur est un simple objet PHP, sans interface imposee (duck typing sur la methode `generate`).

---

## Abstraction des providers SEO

### Le probleme

Les plugins SEO WordPress (Yoast SEO, SEOPress, Rank Math, etc.) stockent leurs donnees de manieres radicalement differentes :

| Donnee | Yoast SEO | SEOPress |
|---|---|---|
| Titre SEO | `_yoast_wpseo_title` | `_seopress_titles_title` |
| Description | `_yoast_wpseo_metadesc` | `_seopress_titles_desc` |
| URL canonique | `_yoast_wpseo_canonical` | `_seopress_robots_canonical` |
| Facebook URL | `wpseo_social[facebook_site]` | `seopress_social_option_name[..._facebook]` |
| Schema JSON-LD | filtre `wpseo_json_ld_output` | filtre `seopress_schemas_auto_enabled` |

Un theme qui accede directement a ces meta-cles se couple a un plugin specifique, rendant le changement de plugin couteux.

### La solution : SeoProviderInterface

Le framework definit une interface unifiee `SeoProviderInterface` qui herite de `MetaProviderInterface` (titre, description, Open Graph) et `SocialLinksProviderInterface` (liens sociaux). Chaque plugin SEO est encapsule dans un provider :

- `YoastProvider` -- lit `wpseo_social` et `_yoast_wpseo_*`
- `SeoPressProvider` -- lit `seopress_social_option_name` et `_seopress_*`

Le `SeoManager` itere sur les providers enregistres et retourne le premier qui repond `true` a `isActive()`. Le theme n'a jamais besoin de savoir quel plugin est installe.

### Schema de resolution

```
SeoManager
  ├── YoastProvider::isActive()?  ──► wordpress-seo/wp-seo.php actif?
  ├── SeoPressProvider::isActive()? ──► wp-seopress/seopress.php actif?
  └── null (aucun plugin SEO)
```

### Detection des plugins

Les providers utilisent `PluginCheckerInterface::isActive()` pour verifier la presence du plugin par son fichier (`wordpress-seo/wp-seo.php`, `wp-seopress/seopress.php`). Cette verification est basee sur la liste des plugins actifs WordPress, sans charger de code du plugin.

### Extensibilite

Pour supporter un nouveau plugin SEO (par exemple Rank Math), il suffit de :

1. Creer une classe implementant `SeoProviderInterface`
2. L'enregistrer via `SeoManager::addProvider()`

Le reste du framework (liens sociaux dans Twig, `sameAs` de l'Organization, desactivation du schema natif) fonctionnera automatiquement.

---

## Integration Timber/Twig

Le bundle expose deux types de donnees dans le contexte Timber :

### SchemaManager dans le contexte

Le hook `AddSchemaToTimberContext` ajoute le `SchemaManager` sous la cle `schema`. Dans un template Twig :

```twig
{{ schema.render()|raw }}
```

Cela permet d'injecter le JSON-LD a un endroit precis du template plutot que via `wp_head`. Les deux mecanismes coexistent : `InjectSchemaInHead` ecrit dans `wp_head`, et `schema.render()` peut etre utilise dans un template.

### Liens sociaux dans le contexte

Le hook `AddSocialLinksToTimberContext` ajoute les liens sociaux comme variables de premier niveau : `facebook`, `twitter`, `instagram`, `linkedin`, `pinterest`, `youtube`. Chaque variable contient soit l'URL du reseau social (chaine), soit `null`.

Cette approche a ete choisie pour simplifier l'utilisation dans les templates : `{% if facebook %}` est plus lisible que `{% if social_links.facebook %}`.

---

## Validation des schemas

Chaque type Schema.org declare ses proprietes requises pour les resultats enrichis Google via la methode protegee `getRequiredProperties()`. Par exemple, `Product` requiert `name`, `image` et `offers`.

La validation est optionnelle et non bloquante : un schema incomplet sera quand meme rendu en JSON-LD. La methode `validate()` retourne la liste des proprietes manquantes, permettant au developpeur de verifier la conformite pendant le developpement.

Le `SchemaManager` offre une validation globale via `validate()`, qui retourne un tableau associatif `[type => [proprietes manquantes]]` pour tous les schemas enregistres.

---

## Desactivation du schema natif des plugins

Les plugins SEO generent leur propre schema JSON-LD, ce qui creerait des doublons avec le schema du framework. L'action `DisablePluginSchema` neutralise cette sortie :

- **Yoast SEO** : filtre `wpseo_json_ld_output` avec `__return_empty_array`
- **SEOPress** : filtre `seopress_schemas_auto_enabled` avec `__return_false`

Cette desactivation est activee par defaut (`schema.disable_plugin_schema` vaut `true`). Le theme peut la desactiver dans `config/seo.php` s'il souhaite conserver le schema du plugin.

L'action verifie d'abord qu'un provider SEO est actif avant de tenter de desactiver quoi que ce soit, evitant les appels inutiles.

---

## Nettoyage de l'empreinte Yoast

Yoast SEO injecte des commentaires HTML (`<!-- This site is optimized with the Yoast SEO plugin ... -->`) et expose son numero de version dans le code source. Ces informations n'apportent rien a l'utilisateur final et peuvent reveler la stack technique du site.

L'action `CleanYoastFootprint` supprime ces marqueurs via deux filtres WordPress natifs de Yoast. Cette action est automatique et ne necessite aucune configuration.
