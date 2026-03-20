# Tutoriel : Premiers pas avec le SEO

Ce tutoriel vous guide pas a pas pour decouvrir les donnees structurees generees automatiquement, creer des schemas personnalises avec l'API fluide, et integrer votre plugin SEO.

## Pre-requis

- Un theme WordPress utilisant BackTo Framework
- Yoast SEO ou SEOPress installe et active (optionnel, mais recommande)

## Etape 1 : Decouvrir les schemas auto-generes

Sans aucune configuration, le bundle SEO genere automatiquement quatre schemas sur chaque page :

1. **WebSite** -- nom, URL et moteur de recherche interne du site
2. **Organization** -- nom, logo et liens sociaux de l'entreprise
3. **Article** -- titre, auteur, dates de publication/modification (sur les articles `post`)
4. **BreadcrumbList** -- fil d'Ariane contextuel (pages, articles, archives, taxonomies)

Ouvrez le code source de n'importe quelle page de votre site et cherchez la balise `<script type="application/ld+json">`. Vous verrez un objet JSON contenant un `@graph` avec plusieurs noeuds lies par des `@id` :

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://example.com/#website",
      "name": "Mon Site",
      "url": "https://example.com/",
      "publisher": { "@id": "https://example.com/#organization" },
      "potentialAction": {
        "@type": "SearchAction",
        "target": "https://example.com/?s={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "Organization",
      "@id": "https://example.com/#organization",
      "name": "Mon Site",
      "url": "https://example.com/",
      "logo": "https://example.com/wp-content/uploads/logo.png",
      "sameAs": [
        "https://facebook.com/monsite",
        "https://twitter.com/monsite"
      ]
    },
    {
      "@type": "Article",
      "@id": "https://example.com/mon-article/#article",
      "headline": "Mon premier article",
      "datePublished": "2025-01-15T10:00:00+00:00",
      "author": { "@type": "Person", "name": "Jean Dupont" },
      "isPartOf": { "@id": "https://example.com/#website" },
      "publisher": { "@id": "https://example.com/#organization" }
    },
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Accueil", "item": "https://example.com/" },
        { "@type": "ListItem", "position": 2, "name": "Blog", "item": "https://example.com/blog/" },
        { "@type": "ListItem", "position": 3, "name": "Mon premier article" }
      ]
    }
  ]
}
```

Les noeuds se referencent mutuellement via `@id` : l'Article pointe vers le WebSite (`isPartOf`) et l'Organization (`publisher`) sans dupliquer les donnees.

## Etape 2 : Creer un schema personnalise avec l'API fluide

La classe `Schema` est une factory statique qui expose une methode pour chaque type Schema.org. Chaque methode retourne un objet avec une API fluide (chainage de methodes).

Ajoutez un schema Product en vous connectant au hook `framework/seo/schema` :

```php
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $product = Schema::product()
        ->name('T-shirt BackTo')
        ->description('Un t-shirt en coton bio avec le logo BackTo.')
        ->image('https://example.com/images/tshirt.jpg')
        ->sku('BT-TSHIRT-001')
        ->brand(Schema::brand()->name('BackTo'))
        ->offers(
            Schema::offer()
                ->price(29.90)
                ->priceCurrency('EUR')
                ->availability('https://schema.org/InStock')
                ->url('https://example.com/boutique/tshirt')
        );

    $manager->add($product);
});
```

Rechargez la page et verifiez le JSON-LD : un noeud `Product` apparait dans le `@graph`, avec un `Offer` imbrique et une `Brand`.

### Comprendre l'API fluide

Chaque type Schema.org dispose de methodes dediees correspondant aux proprietes Schema.org :

```php
// Methodes dediees avec auto-completion IDE
Schema::article()
    ->headline('Mon titre')
    ->author(Schema::person()->name('Jean'))
    ->datePublished('2025-01-15T10:00:00+00:00');

// Methode generique set() pour toute propriete
Schema::article()
    ->set('headline', 'Mon titre')
    ->set('copyrightYear', 2025);
```

La methode `set(string $property, mixed $value)` permet d'ajouter n'importe quelle propriete Schema.org, meme celles qui n'ont pas de methode dediee.

### Imbriquer des schemas

Les schemas peuvent etre imbriques librement :

```php
Schema::event()
    ->name('Conference BackTo')
    ->startDate('2025-06-15T09:00:00+02:00')
    ->location(
        Schema::place()
            ->name('Palais des Congres')
            ->address(
                Schema::postalAddress()
                    ->streetAddress('2 Place de la Porte Maillot')
                    ->addressLocality('Paris')
                    ->postalCode('75017')
                    ->addressCountry('FR')
            )
    )
    ->organizer(Schema::organization()->name('BackTo'));
```

### References croisees avec @id

Pour relier des schemas sans les dupliquer, utilisez `Schema::ref()` :

```php
Schema::article()
    ->id('https://example.com/article/#article')
    ->headline('Mon article')
    ->set('publisher', Schema::ref('https://example.com/#organization'));
```

Cela produit `"publisher": { "@id": "https://example.com/#organization" }`, qui pointe vers le noeud Organization deja present dans le `@graph`.

## Etape 3 : Integrer Yoast SEO ou SEOPress

Le framework detecte automatiquement le plugin SEO actif et expose ses donnees via une interface unifiee.

### Donnees automatiquement extraites

Lorsque Yoast SEO ou SEOPress est actif :

- Les **liens sociaux** (Facebook, Twitter, Instagram, LinkedIn, Pinterest, YouTube) sont injectes dans le contexte Timber et utilises pour le `sameAs` de l'Organization
- Le **schema natif du plugin est desactive** pour eviter les doublons (configurable)
- L'**empreinte Yoast** (commentaires HTML de version) est nettoyee

### Acceder aux metadonnees SEO dans Twig

Les liens sociaux sont disponibles directement dans le contexte Timber :

```twig
{% if facebook %}
    <a href="{{ facebook }}">Facebook</a>
{% endif %}
{% if twitter %}
    <a href="{{ twitter }}">Twitter</a>
{% endif %}
{% if instagram %}
    <a href="{{ instagram }}">Instagram</a>
{% endif %}
```

Le SchemaManager est egalement accessible :

```twig
{# Afficher le JSON-LD dans le template (alternative a l'injection automatique dans wp_head) #}
{{ schema.render()|raw }}
```

### Configuration

Creez un fichier `config/seo.php` dans votre theme pour personnaliser le comportement :

```php
// config/seo.php
return [
    'schema' => [
        // Associer un generateur de schema a chaque post type
        'post_type_map' => [
            'post' => \BackTo\Framework\Bundle\Seo\Schema\Generator\ArticleSchemaGenerator::class,
        ],
        // Desactiver le schema natif du plugin SEO (true par defaut)
        'disable_plugin_schema' => true,
    ],
];
```

### Configurer le separateur de titre

Utilisez le `SeoConfigurator` pour ajuster les parametres SEO :

```php
// config/seo.php (format alternatif avec configurateur)
use BackTo\Framework\Bundle\Seo\SeoConfigurator;

return static function (SeoConfigurator $seo): void {
    $seo
        ->titleSeparator('-')
        ->robotsDefault('index, follow');
};
```

## Etape 4 : Valider vos schemas

Chaque type Schema.org declare ses proprietes requises selon les specifications Google. Vous pouvez valider un schema avant de l'enregistrer :

```php
$product = Schema::product()
    ->name('T-shirt')
    ->description('Un t-shirt');

$missing = $product->validate();
// ['image', 'offers'] -- proprietes manquantes pour les resultats enrichis Google

if ($product->isValid()) {
    $manager->add($product);
}
```

Vous pouvez aussi valider l'ensemble des schemas enregistres :

```php
$errors = $manager->validate();
// ['Product' => ['image', 'offers'], 'Article' => ['author']]
```

## Prochaines etapes

- Consultez les [recettes](how-to.md) pour des cas concrets (FAQ, breadcrumbs, Product avec avis)
- Explorez la [reference API](reference.md) pour la liste complete des 31 types et leurs proprietes
- Lisez l'[explication de l'architecture](explanation.md) pour comprendre le pipeline de generation et le pattern `@graph`
