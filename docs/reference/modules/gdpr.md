# Gdpr

GDPR consent management with cookie-based storage, consent banner, and conditional script loading. Built-in presets: GTM, gtag.js, GA4, Google Ads, Hotjar, HubSpot.

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
