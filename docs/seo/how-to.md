# Recettes SEO

Guides pratiques pour les cas d'utilisation courants du bundle SEO.

---

## Ajouter un schema personnalise pour un Custom Post Type

Pour associer un generateur de schema a un CPT (par exemple `evenement`), creez un generateur et declarez-le dans la configuration.

### 1. Creer le generateur

```php
// src/Seo/EventSchemaGenerator.php
namespace App\Seo;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class EventSchemaGenerator
{
    public function __construct(
        private readonly ContentQueryInterface $contentQuery,
    ) {}

    public function generate(?int $postId = null): ?SchemaType
    {
        $post = $this->contentQuery->getPost($postId);

        if ($post === null) {
            return null;
        }

        return Schema::event()
            ->name($post->post_title)
            ->description($this->contentQuery->getTheExcerpt($post))
            ->startDate(get_post_meta($post->ID, 'event_start', true))
            ->endDate(get_post_meta($post->ID, 'event_end', true))
            ->location(
                Schema::place()
                    ->name(get_post_meta($post->ID, 'event_venue', true))
                    ->address(get_post_meta($post->ID, 'event_address', true))
            )
            ->url($this->contentQuery->getPermalink($post->ID));
    }
}
```

### 2. Enregistrer dans la configuration

```php
// config/seo.php
return [
    'schema' => [
        'post_type_map' => [
            'post'      => \BackTo\Framework\Bundle\Seo\Schema\Generator\ArticleSchemaGenerator::class,
            'evenement' => \App\Seo\EventSchemaGenerator::class,
        ],
    ],
];
```

Le `PostTypeSchemaResolver` utilisera automatiquement votre generateur sur les pages singulaires du CPT `evenement`.

---

## Creer un schema Product avec offres et avis

```php
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $product = Schema::product()
        ->name('Casque Audio Pro')
        ->description('Casque audio sans fil avec reduction de bruit active.')
        ->image('https://example.com/images/casque.jpg')
        ->sku('CASQUE-PRO-001')
        ->gtin13('3614271234567')
        ->brand(Schema::brand()->name('AudioTech'))
        ->color('Noir')
        ->offers([
            Schema::offer()
                ->price(199.99)
                ->priceCurrency('EUR')
                ->availability('https://schema.org/InStock')
                ->url('https://example.com/boutique/casque-pro')
                ->validFrom('2025-01-01'),
            Schema::offer()
                ->price(179.99)
                ->priceCurrency('EUR')
                ->availability('https://schema.org/PreOrder')
                ->category('Tarif early-bird'),
        ])
        ->aggregateRating(
            Schema::aggregateRating()
                ->ratingValue(4.7)
                ->bestRating(5)
                ->reviewCount(128)
        )
        ->review([
            Schema::review()
                ->author(Schema::person()->name('Marie Martin'))
                ->reviewRating(Schema::rating()->ratingValue(5)->bestRating(5))
                ->reviewBody('Excellent casque, le son est incroyable.')
                ->datePublished('2025-03-10')
                ->itemReviewed(Schema::type('Product')->set('name', 'Casque Audio Pro')),
            Schema::review()
                ->author(Schema::person()->name('Paul Durand'))
                ->reviewRating(Schema::rating()->ratingValue(4)->bestRating(5))
                ->reviewBody('Tres bon rapport qualite-prix.')
                ->datePublished('2025-02-28')
                ->itemReviewed(Schema::type('Product')->set('name', 'Casque Audio Pro')),
        ]);

    $manager->add($product);
});
```

---

## Construire un schema FAQ

```php
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $faq = Schema::faqPage()->mainEntity([
        Schema::question()
            ->name('Quels sont les delais de livraison ?')
            ->acceptedAnswer(
                Schema::answer()->text('La livraison standard prend 3 a 5 jours ouvrables en France metropolitaine.')
            ),
        Schema::question()
            ->name('Puis-je retourner un produit ?')
            ->acceptedAnswer(
                Schema::answer()->text('Oui, vous disposez de 30 jours pour retourner un produit dans son emballage d\'origine.')
            ),
        Schema::question()
            ->name('Proposez-vous un support technique ?')
            ->acceptedAnswer(
                Schema::answer()->text('Notre equipe support est disponible du lundi au vendredi de 9h a 18h par email et telephone.')
            ),
    ]);

    $manager->add($faq);
});
```

### FAQ dynamique depuis des champs ACF

```php
add_action('framework/seo/schema', function (SchemaManager $manager) {
    $postId = get_the_ID();
    $items = get_field('faq_items', $postId);

    if (empty($items)) {
        return;
    }

    $questions = array_map(fn (array $item) =>
        Schema::question()
            ->name($item['question'])
            ->acceptedAnswer(Schema::answer()->text($item['reponse'])),
        $items
    );

    $manager->add(Schema::faqPage()->mainEntity($questions));
});
```

---

## Ajouter des breadcrumbs personnalises

Le framework genere automatiquement un `BreadcrumbList` via `WordPressBreadcrumbSchemaGenerator`. Pour le personnaliser, implementez `BreadcrumbSchemaGeneratorInterface` :

```php
namespace App\Seo;

use BackTo\Framework\Bundle\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class CustomBreadcrumbGenerator implements BreadcrumbSchemaGeneratorInterface
{
    public function generate(): ?SchemaType
    {
        // Exemple : breadcrumbs pour une boutique
        return Schema::breadcrumbList()->items([
            Schema::listItem()->position(1)->name('Accueil')->url(home_url('/')),
            Schema::listItem()->position(2)->name('Boutique')->url(home_url('/boutique/')),
            Schema::listItem()->position(3)->name(get_the_title()),
        ]);
    }
}
```

Enregistrez votre implementation dans le conteneur de services pour qu'elle remplace le generateur par defaut.

---

## Acceder aux liens sociaux dans les templates Twig

Le hook `AddSocialLinksToTimberContext` injecte les liens sociaux du plugin SEO actif dans le contexte Timber. Les cles disponibles sont : `facebook`, `twitter`, `instagram`, `linkedin`, `pinterest`, `youtube`.

```twig
{# templates/partials/social-links.twig #}
<nav class="social-links" aria-label="Reseaux sociaux">
    {% set socials = {
        facebook: { icon: 'facebook', label: 'Facebook' },
        twitter: { icon: 'twitter', label: 'Twitter' },
        instagram: { icon: 'instagram', label: 'Instagram' },
        linkedin: { icon: 'linkedin', label: 'LinkedIn' },
        pinterest: { icon: 'pinterest', label: 'Pinterest' },
        youtube: { icon: 'youtube', label: 'YouTube' },
    } %}

    {% for key, social in socials %}
        {% set url = attribute(_context, key) %}
        {% if url %}
            <a href="{{ url }}" target="_blank" rel="noopener" aria-label="{{ social.label }}">
                <svg class="icon icon-{{ social.icon }}"><use href="#icon-{{ social.icon }}"></use></svg>
            </a>
        {% endif %}
    {% endfor %}
</nav>
```

Ces liens sont extraits automatiquement des reglages Yoast SEO (`wpseo_social`) ou SEOPress (`seopress_social_option_name`), sans que le template ait besoin de connaitre le plugin utilise.

---

## Desactiver le schema natif du plugin SEO

Par defaut, le framework desactive le schema JSON-LD genere par Yoast SEO et SEOPress pour eviter les doublons. Pour conserver le schema du plugin :

```php
// config/seo.php
return [
    'schema' => [
        'disable_plugin_schema' => false,
    ],
];
```

Filtres WordPress utilises en interne :

| Plugin | Filtre | Valeur |
|---|---|---|
| Yoast SEO | `wpseo_json_ld_output` | `__return_empty_array` |
| SEOPress | `seopress_schemas_auto_enabled` | `__return_false` |

---

## Nettoyer l'empreinte Yoast

Le framework supprime automatiquement les marqueurs de debug et informations de version Yoast SEO dans le HTML. Cela se fait via deux filtres :

- `wpseo_debug_markers` -> `__return_false` (supprime les commentaires HTML `<!-- Yoast SEO ... -->`)
- `wpseo_hide_version` -> `__return_true` (masque le numero de version)

Cette action est geree par `CleanYoastFootprint` et est activee automatiquement. Aucune configuration n'est necessaire.

---

## Ajouter un schema VideoObject

```php
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $video = Schema::videoObject()
        ->name('Presentation du produit')
        ->description('Decouvrez notre nouveau produit en video.')
        ->thumbnailUrl('https://example.com/images/video-thumb.jpg')
        ->uploadDate('2025-03-01T10:00:00+01:00')
        ->duration('PT5M30S')
        ->contentUrl('https://example.com/videos/presentation.mp4')
        ->embedUrl('https://www.youtube.com/embed/abc123')
        ->hasPart([
            Schema::clip()
                ->name('Introduction')
                ->startOffset(0)
                ->endOffset(30)
                ->url('https://example.com/videos/presentation.mp4?t=0'),
            Schema::clip()
                ->name('Demonstration')
                ->startOffset(30)
                ->endOffset(180)
                ->url('https://example.com/videos/presentation.mp4?t=30'),
        ]);

    $manager->add($video);
});
```

---

## Creer un schema JobPosting

```php
add_action('framework/seo/schema', function (SchemaManager $manager) {
    $job = Schema::jobPosting()
        ->title('Developpeur PHP Senior')
        ->description('Nous recherchons un developpeur PHP senior pour rejoindre notre equipe...')
        ->datePosted('2025-03-01')
        ->validThrough('2025-06-01')
        ->employmentType('FULL_TIME')
        ->hiringOrganization(
            Schema::organization()
                ->name('Acme Corp')
                ->url('https://acme.com')
                ->logo('https://acme.com/logo.png')
        )
        ->jobLocation(
            Schema::place()->address(
                Schema::postalAddress()
                    ->streetAddress('10 Rue de la Paix')
                    ->addressLocality('Paris')
                    ->postalCode('75002')
                    ->addressCountry('FR')
            )
        )
        ->baseSalary(
            Schema::monetaryAmount()
                ->currency('EUR')
                ->minValue(50000)
                ->maxValue(70000)
        );

    $manager->add($job);
});
```

---

## Utiliser un type Schema.org generique

Pour un type Schema.org qui n'a pas de classe dediee, utilisez `Schema::type()` :

```php
$recipe = Schema::type('Recipe')
    ->set('name', 'Tarte aux pommes')
    ->set('author', Schema::person()->name('Chef Pierre'))
    ->set('prepTime', 'PT30M')
    ->set('cookTime', 'PT45M')
    ->set('recipeYield', '8 parts')
    ->set('recipeIngredient', ['6 pommes', '200g de sucre', '1 pate feuilletee']);

$manager->add($recipe);
```
