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
