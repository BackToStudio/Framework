# Seo

Unified SEO plugin integration.

## Classes

| Class | Role |
|-------|------|
| `SeoManager` | Resolves first active provider, provides shortcuts |
| `Provider\YoastProvider` | Yoast SEO adapter |
| `Provider\SeoPressProvider` | SEOPress adapter |
| `Contracts\SeoProviderInterface` | Unified provider interface |
| `Contracts\SocialLinksProviderInterface` | Social links methods |
| `Contracts\MetaProviderInterface` | Meta data methods |
| `Actions\CleanYoastFootprint` | Removes Yoast debug output |
| `Hooks\AddSocialLinksToTimberContext` | Injects social links into Timber |

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
