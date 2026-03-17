# How to Integrate SEO Plugins

The SEO module provides a unified interface over Yoast SEO and SEOPress, with a `SeoManager` that automatically resolves the active provider.

## Built-in providers

| Provider | Plugin | Detection |
|----------|--------|-----------|
| `YoastProvider` | Yoast SEO | `wordpress-seo/wp-seo.php` |
| `SeoPressProvider` | SEOPress | `wp-seopress/seopress.php` |

## Get social links

```php
use BackTo\Framework\Seo\SeoManager;

class MyService
{
    public function __construct(private SeoManager $seoManager)
    {
    }

    public function getSocials(): array
    {
        if (!$this->seoManager->hasProvider()) {
            return [];
        }

        return $this->seoManager->getSocialLinks();
        // Returns: ['facebook' => 'https://...', 'twitter' => 'https://...', ...]
    }
}
```

## Get meta data

```php
$provider = $this->seoManager->getProvider();

if ($provider !== null) {
    $title = $provider->getTitle($postId);
    $description = $provider->getDescription($postId);
    $ogImage = $provider->getOgImageUrl($postId);
    $canonical = $provider->getCanonicalUrl($postId);
}
```

## Auto-inject social links into Timber context

The `AddSocialLinksToTimberContext` hook is auto-registered. It adds social links from the active SEO provider to every Timber context:

```twig
{# In your Twig template #}
{% if facebook %}
    <a href="{{ facebook }}">Facebook</a>
{% endif %}
{% if twitter %}
    <a href="{{ twitter }}">Twitter</a>
{% endif %}
```

## Clean Yoast footprint

The `CleanYoastFootprint` hook is auto-registered. It removes Yoast debug markers and version numbers from your HTML output.

## Create a custom SEO provider

Implement `SeoProviderInterface`:

```php
<?php

namespace MyTheme\Seo;

use BackTo\Framework\Seo\Contracts\SeoProviderInterface;

class RankMathProvider implements SeoProviderInterface
{
    public function getName(): string
    {
        return 'rankmath';
    }

    public function isActive(): bool
    {
        return \is_plugin_active('seo-by-rank-math/rank-math.php');
    }

    // Implement all social links and meta methods...
}
```

Register it with the `SeoManager` via DI or manually:

```php
$seoManager->addProvider(new RankMathProvider());
```

## Add structured data (JSON-LD)

The module includes a full schema.org system with a fluent API. Schemas are registered via the `SchemaManager` and rendered as a single JSON-LD `<script>` block.

### Build a schema manually

```php
use BackTo\Framework\Seo\Schema;

$article = Schema::article()
    ->set('headline', 'My Article')
    ->set('author', Schema::person()->set('name', 'John Doe'))
    ->set('datePublished', '2026-01-15');

$schemaManager->add($article);
```

### Link schemas with `@id`

Use `Schema::ref()` to reference other nodes by `@id`, avoiding data duplication:

```php
$org = Schema::organization()
    ->id('https://example.com/#organization')
    ->set('name', 'Acme Corp');

$article = Schema::article()
    ->id('https://example.com/post/1#article')
    ->set('headline', 'Hello World')
    ->set('publisher', Schema::ref('https://example.com/#organization'));

$schemaManager->add($org);
$schemaManager->add($article);
```

This renders a `@graph` with linked nodes instead of duplicating the organization data in every article.

### Validate schemas

Concrete types define required properties per Google's rich results guidelines:

```php
$product = Schema::product()->set('name', 'Widget');
$missing = $product->validate(); // ['image', 'offers']

// Or validate all schemas at once:
$errors = $schemaManager->validate();
// ['Product' => ['image', 'offers']]
```

### Map custom post types to schema generators

Create a `config/seo.php` file in your theme to map post types to generators:

```php
<?php

// config/seo.php
return [
    'schema' => [
        'post_type_map' => [
            'post'      => \App\Seo\ArticleSchemaGenerator::class,
            'formation' => \App\Seo\CourseSchemaGenerator::class,
            'evenement' => \App\Seo\EventSchemaGenerator::class,
        ],
        'disable_plugin_schema' => true,
    ],
];
```

Each generator must implement a `generate(?int $postId): ?SchemaType` method:

```php
<?php

namespace App\Seo;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;

class CourseSchemaGenerator
{
    public function generate(?int $postId = null): ?SchemaType
    {
        if ($postId === null) {
            return null;
        }

        $post = get_post($postId);

        return Schema::course()
            ->set('name', $post->post_title)
            ->set('description', get_the_excerpt($post))
            ->set('provider', Schema::ref(get_site_url() . '/#organization'));
    }
}
```

### Disable plugin schema output

By default, the framework disables the native schema output of Yoast SEO and SEOPress to avoid duplication. Control this via `config/seo.php`:

```php
return [
    'schema' => [
        'disable_plugin_schema' => false, // Keep plugin schema output
    ],
];
```
