# Presets

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
