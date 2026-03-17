# Gdpr

GDPR consent management with cookie-based storage, consent banner, and conditional script loading.

## Classes

| Class | Role |
|-------|------|
| `Entity\ConsentCategory` | Immutable value object for a consent category |
| `Entity\TrackingScript` | Immutable value object for a tracking script |
| `ConsentCategoryRegistry` | Collects registered consent categories |
| `TrackingScriptRegistry` | Collects registered tracking scripts |
| `RegisterGdpr` | Application orchestrator (hooks into `wp_head` / `wp_footer`) |
| `ConsentBanner` | Renders the consent banner HTML/CSS/JS |
| `Contracts\ConsentCategoryInterface` | Interface for consent categories |
| `Contracts\TrackingScriptInterface` | Interface for tracking scripts |
| `Contracts\ConsentStorageInterface` | Port for reading user consent |
| `Infrastructure\CookieConsentStorage` | Cookie-based adapter |
| `Preset\GoogleTagManagerScript` | GTM preset (container ID) |
| `Preset\GtagLoaderScript` | gtag.js loader preset (tracking ID) |
| `Preset\GoogleAnalyticsScript` | GA4 preset (measurement ID) |
| `Preset\GoogleAdsScript` | Google Ads preset (conversion ID) |
| `Preset\HotjarScript` | Hotjar preset (site ID) |
| `Preset\HubSpotScript` | HubSpot preset (portal ID) |

## Contracts

### `ConsentCategoryInterface`

```php
interface ConsentCategoryInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getDescription(): string;
    public function isRequired(): bool;
}
```

### `TrackingScriptInterface`

```php
interface TrackingScriptInterface
{
    public function getHandle(): string;
    public function getCategoryKey(): string;
    public function getSource(): string;
    public function isInline(): bool;
    public function getLocation(): string;
    public function getPriority(): int;
}
```

### `ConsentStorageInterface`

Port interface for reading user consent state.

```php
interface ConsentStorageInterface
{
    /** @return array<string, bool> */
    public function getConsent(): array;
    public function hasConsent(string $categoryKey): bool;
    public function isConsentGiven(): bool;
}
```

### `ConsentCategoryRegistryInterface`

```php
interface ConsentCategoryRegistryInterface
{
    public function add(ConsentCategoryInterface $category): self;
    /** @return ConsentCategoryInterface[] */
    public function getCategories(): array;
    public function get(string $key): ?ConsentCategoryInterface;
}
```

### `TrackingScriptRegistryInterface`

```php
interface TrackingScriptRegistryInterface
{
    public function add(TrackingScriptInterface $script): self;
    /** @return TrackingScriptInterface[] */
    public function getScripts(): array;
    /** @return TrackingScriptInterface[] */
    public function getScriptsByCategory(string $categoryKey): array;
}
```
