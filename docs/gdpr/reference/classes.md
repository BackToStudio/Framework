# Classes

| Class | Namespace | Description |
|---|---|---|
| `ConsentCategoryRegistry` | `Gdpr` | Registry of categories (indexed by key) |
| `TrackingScriptRegistry` | `Gdpr` | Registry of tracking scripts |
| `ConsentBanner` | `Gdpr` | Generates the full banner HTML |
| `ConsentBannerRenderer` | `Gdpr` | Generates checkboxes, CSS, and JavaScript |
| `RegisterGdpr` | `Gdpr` | Orchestrates rendering: head/footer scripts + banner |
| `CookieConsentStorage` | `Infrastructure` | Reads the `gdpr_consent` cookie (JSON, max 4 KB) |

**Base namespace:** `BackTo\Framework\Bundle\Gdpr`
