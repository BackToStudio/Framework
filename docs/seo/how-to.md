# SEO Bundle -- How-to guides

*How-to -- Task-oriented*

Practical recipes for common SEO bundle use cases.

---

## Add a schema for a custom post type

Create a generator class and register it in the configuration.

### 1. Create the generator

```php
<?php

namespace MyTheme\Seo;

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

### 2. Register in configuration

```php
<?php

// config/seo.php
return [
    'schema' => [
        'post_type_map' => [
            'post'  => \BackTo\Framework\Bundle\Seo\Schema\Generator\ArticleSchemaGenerator::class,
            'event' => \MyTheme\Seo\EventSchemaGenerator::class,
        ],
    ],
];
```

The `PostTypeSchemaResolver` will automatically use your generator on singular pages of the `event` post type.

---

## Add Product schema with offers and reviews

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $product = Schema::product()
        ->name('Wireless Headphones')
        ->description('Noise-cancelling wireless headphones with 30-hour battery life.')
        ->image('https://example.com/images/headphones.jpg')
        ->sku('WH-PRO-001')
        ->gtin13('3614271234567')
        ->brand(Schema::brand()->name('AudioTech'))
        ->offers([
            Schema::offer()
                ->price(199.99)
                ->priceCurrency('USD')
                ->availability('https://schema.org/InStock')
                ->url('https://example.com/shop/headphones'),
        ])
        ->aggregateRating(
            Schema::aggregateRating()
                ->ratingValue(4.7)
                ->bestRating(5)
                ->reviewCount(128)
        )
        ->review([
            Schema::review()
                ->author(Schema::person()->name('Alice Smith'))
                ->reviewRating(Schema::rating()->ratingValue(5)->bestRating(5))
                ->reviewBody('Excellent sound quality.')
                ->datePublished('2025-03-10')
                ->itemReviewed(Schema::type('Product')->set('name', 'Wireless Headphones')),
        ]);

    $manager->add($product);
});
```

---

## Add FAQ schema

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $faq = Schema::faqPage()->mainEntity([
        Schema::question()
            ->name('What are the shipping times?')
            ->acceptedAnswer(
                Schema::answer()->text('Standard shipping takes 3-5 business days.')
            ),
        Schema::question()
            ->name('Can I return a product?')
            ->acceptedAnswer(
                Schema::answer()->text('Yes, you have 30 days to return items in original packaging.')
            ),
    ]);

    $manager->add($faq);
});
```

### Dynamic FAQ from ACF fields

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $items = get_field('faq_items', get_the_ID());

    if (empty($items)) {
        return;
    }

    $questions = array_map(fn (array $item) =>
        Schema::question()
            ->name($item['question'])
            ->acceptedAnswer(Schema::answer()->text($item['answer'])),
        $items
    );

    $manager->add(Schema::faqPage()->mainEntity($questions));
});
```

---

## Customize breadcrumbs

Implement `BreadcrumbSchemaGeneratorInterface` to replace the default breadcrumb generation:

```php
<?php

namespace MyTheme\Seo;

use BackTo\Framework\Bundle\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class ShopBreadcrumbGenerator implements BreadcrumbSchemaGeneratorInterface
{
    public function generate(): ?SchemaType
    {
        return Schema::breadcrumbList()->items([
            Schema::listItem()->position(1)->name('Home')->url(home_url('/')),
            Schema::listItem()->position(2)->name('Shop')->url(home_url('/shop/')),
            Schema::listItem()->position(3)->name(get_the_title()),
        ]);
    }
}
```

Register your implementation in the DI container to override the default:

```php
<?php

use BackTo\Framework\Bundle\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;

$containerBuilder->register(BreadcrumbSchemaGeneratorInterface::class, ShopBreadcrumbGenerator::class);
```

---

## Access social links in Twig templates

The bundle injects social links from the active SEO plugin (Yoast or SEOPress) into the Timber context. Available variables: `facebook`, `twitter`, `instagram`, `linkedin`, `pinterest`, `youtube`.

```twig
<nav class="social-links" aria-label="Social media">
    {% if facebook %}
        <a href="{{ facebook }}" target="_blank" rel="noopener">Facebook</a>
    {% endif %}
    {% if twitter %}
        <a href="{{ twitter }}" target="_blank" rel="noopener">Twitter</a>
    {% endif %}
    {% if instagram %}
        <a href="{{ instagram }}" target="_blank" rel="noopener">Instagram</a>
    {% endif %}
</nav>
```

These links are extracted from Yoast's `wpseo_social` option or SEOPress's `seopress_social_option_name` option, without the template needing to know which plugin is installed.

---

## Disable plugin-native schema output

By default, the framework disables JSON-LD output from Yoast SEO and SEOPress to prevent duplicates. To keep the plugin's schema:

```php
<?php

// config/seo.php
return [
    'schema' => [
        'disable_plugin_schema' => false,
    ],
];
```

---

## Add a VideoObject schema

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $video = Schema::videoObject()
        ->name('Product Overview')
        ->description('A walkthrough of our latest product features.')
        ->thumbnailUrl('https://example.com/images/video-thumb.jpg')
        ->uploadDate('2025-03-01T10:00:00+00:00')
        ->duration('PT5M30S')
        ->contentUrl('https://example.com/videos/overview.mp4')
        ->embedUrl('https://www.youtube.com/embed/abc123')
        ->hasPart([
            Schema::clip()
                ->name('Introduction')
                ->startOffset(0)
                ->endOffset(30)
                ->url('https://example.com/videos/overview.mp4?t=0'),
            Schema::clip()
                ->name('Demo')
                ->startOffset(30)
                ->endOffset(180)
                ->url('https://example.com/videos/overview.mp4?t=30'),
        ]);

    $manager->add($video);
});
```

---

## Add a JobPosting schema

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $job = Schema::jobPosting()
        ->title('Senior PHP Developer')
        ->description('We are looking for an experienced PHP developer...')
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
                    ->streetAddress('123 Main Street')
                    ->addressLocality('New York')
                    ->postalCode('10001')
                    ->addressCountry('US')
            )
        )
        ->baseSalary(
            Schema::monetaryAmount()
                ->currency('USD')
                ->minValue(80000)
                ->maxValue(120000)
        );

    $manager->add($job);
});
```

---

## Use a generic schema type

For schema.org types that do not have a dedicated class, use `Schema::type()`:

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $recipe = Schema::type('Recipe')
        ->set('name', 'Apple Pie')
        ->set('author', Schema::person()->name('Chef Pierre'))
        ->set('prepTime', 'PT30M')
        ->set('cookTime', 'PT45M')
        ->set('recipeYield', '8 servings')
        ->set('recipeIngredient', ['6 apples', '200g sugar', '1 pie crust']);

    $manager->add($recipe);
});
```

---

## Configure SEO parameters

Use the `SeoConfigurator` in your `config/seo.php` to adjust title separator and default robots directive:

```php
<?php

// config/seo.php
use BackTo\Framework\Bundle\Seo\SeoConfigurator;

return static function (SeoConfigurator $seo): void {
    $seo
        ->titleSeparator('-')
        ->robotsDefault('noindex, nofollow');
};
```

---

## Render schema in a Twig template

The `SchemaManager` is available in the Timber context as `schema`. Use it to render JSON-LD at a specific location in your template instead of relying on `wp_head`:

```twig
{{ schema.render()|raw }}
```

Both mechanisms coexist: `InjectSchemaInHead` writes to `wp_head`, and `schema.render()` can be used in templates.
