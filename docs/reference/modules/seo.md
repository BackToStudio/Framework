# Seo

Unified SEO plugin integration. Built-in providers: Yoast SEO, SEOPress.

## Configuration

SEO parameters are managed by `SeoConfiguration` and overridden via the fluent `SeoConfigurator` in `config/seo.php`:

```php
<?php

use BackTo\Framework\Seo\SeoConfigurator;

return static function (SeoConfigurator $seo): void {
    $seo
        ->titleSeparator('-')
        ->robotsDefault('noindex, nofollow');
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `seo.title_separator` | `\|` | `titleSeparator(string)` |
| `seo.robots_default` | `index, follow` | `robotsDefault(string)` |

## Contracts

### `SeoProviderInterface` extends `SocialLinksProviderInterface`, `MetaProviderInterface`

```php
interface SeoProviderInterface
{
    public function getName(): string;
    public function isActive(): bool;
    // + all social links + meta methods
}
```

### `SocialLinksProviderInterface`

Methods: `getFacebookUrl`, `getTwitterUrl`, `getInstagramUrl`, `getLinkedInUrl`, `getPinterestUrl`, `getYouTubeUrl`, `getSocialLinks`.

### `MetaProviderInterface`

Methods: `getTitle`, `getDescription`, `getCanonicalUrl`, `getOgTitle`, `getOgDescription`, `getOgImageUrl` (all with optional `?int $postId`).
