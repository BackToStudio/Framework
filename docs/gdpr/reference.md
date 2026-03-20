# GDPR Bundle — API Reference

*Reference — Information-oriented*

---

## Interfaces

### `ConsentCategoryInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

Defines a consent category.

| Method | Return | Description |
|---|---|---|
| `getKey()` | `string` | Unique key (e.g. `analytics`, `marketing`) |
| `getLabel()` | `string` | Name displayed in the banner |
| `getDescription()` | `string` | Description displayed in the banner |
| `isRequired()` | `bool` | `true` if the category cannot be disabled |

---

### `TrackingScriptInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

Defines a tracking script.

| Method | Return | Description |
|---|---|---|
| `getHandle()` | `string` | Unique script identifier |
| `getCategoryKey()` | `string` | Associated consent category key |
| `getSource()` | `string` | Script URL or inline code |
| `isInline()` | `bool` | `true` = inline code, `false` = external `src` |
| `getLocation()` | `string` | `'head'` or `'footer'` |
| `getPriority()` | `int` | Load order (lower = earlier) |

---

### `ConsentStorageInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

Port for reading consent state.

| Method | Return | Description |
|---|---|---|
| `getConsent()` | `array<string, bool>` | Consent state per category |
| `hasConsent(string $categoryKey)` | `bool` | Check consent for a category |
| `isConsentGiven()` | `bool` | `true` if the user has made a choice |

---

### `ConsentCategoryRegistryInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

| Method | Return | Description |
|---|---|---|
| `add(ConsentCategoryInterface $category)` | `void` | Add a category |
| `getCategories()` | `array` | Return all categories |
| `get(string $key)` | `ConsentCategoryInterface` | Return a category by key |

---

### `TrackingScriptRegistryInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

| Method | Return | Description |
|---|---|---|
| `add(TrackingScriptInterface $script)` | `void` | Add a script |
| `getScripts()` | `array` | Return all scripts |
| `getScriptsByCategory(string $categoryKey)` | `array` | Filter scripts by category |

---

## Entities

### `ConsentCategory`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Entity`
**Implements:** `ConsentCategoryInterface`

```php
new ConsentCategory(
    key: 'analytics',
    label: 'Analytics',
    description: 'Audience measurement cookies.',
    required: false, // default
);
```

---

### `TrackingScript`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Entity`
**Implements:** `TrackingScriptInterface`

Validates parameters at construction: `$handle` and `$source` must not be empty, `$location` must be `'head'` or `'footer'`.

```php
new TrackingScript(
    handle: 'facebook-pixel',
    categoryKey: 'marketing',
    source: '!function(f,b,e,...)',
    inline: true,
    location: 'head',
    priority: 5,
);
```

---

## Presets

| Class | Handle | Category | Type | Location | Priority | Parameter |
|---|---|---|---|---|---|---|
| `GtagLoaderScript` | `gtag-loader` | `analytics`* | External | `head` | 4 | `$trackingId` |
| `GoogleAnalyticsScript` | `google-analytics` | `analytics` | Inline | `head` | 5 | `$measurementId` |
| `GoogleTagManagerScript` | `google-tag-manager` | `analytics` | Inline | `head` | 1 | `$containerId` |
| `GoogleAdsScript` | `google-ads` | `marketing` | Inline | `head` | 5 | `$conversionId` |
| `HubSpotScript` | `hubspot` | `marketing` | External | `footer` | 10 | `$portalId` |
| `HotjarScript` | `hotjar` | `analytics` | Inline | `head` | 10 | `$siteId` |

\* `GtagLoaderScript` accepts an optional second parameter `$categoryKey` to override the category.

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Preset`

---

## Classes

| Class | Namespace | Description |
|---|---|---|
| `ConsentCategoryRegistry` | `Gdpr` | Registry of categories (indexed by key) |
| `TrackingScriptRegistry` | `Gdpr` | Registry of tracking scripts |
| `ConsentBanner` | `Gdpr` | Generates the full banner HTML |
| `ConsentBannerRenderer` | `Gdpr` | Generates checkboxes, CSS, and JavaScript |
| `RegisterGdpr` | `Gdpr` | Orchestrates rendering: head/footer scripts + banner |
| `CookieConsentStorage` | `Infrastructure` | Reads the `gdpr_consent` cookie (JSON, max 4 KB) |

**Base namespace:** `BackTo\Framework\Bundle\Gdpr`

---

## Compiler Passes

| Class | Tag | Target Registry |
|---|---|---|
| `RegisterConsentCategoryPass` | `wordpress.consent_category` | `ConsentCategoryRegistry` |
| `RegisterTrackingScriptPass` | `wordpress.tracking_script` | `TrackingScriptRegistry` |

**Namespace:** `BackTo\Framework\Bundle\Gdpr\DependencyInjection\Compiler`

---

## DI Extension

### `GdprExtension`

**Namespace:** `BackTo\Framework\Bundle\Gdpr`

Auto-tags classes implementing `ConsentCategoryInterface` with `wordpress.consent_category` and `TrackingScriptInterface` with `wordpress.tracking_script`.
