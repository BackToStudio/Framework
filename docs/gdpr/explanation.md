# GDPR Bundle — Architecture & Design

*Explanation — Understanding-oriented*

---

## Consent flow

The consent lifecycle has three phases:

1. **First visit** — No `gdpr_consent` cookie exists. `CookieConsentStorage::isConsentGiven()` returns `false`. The banner is displayed automatically via the `wp_footer` hook.
2. **User choice** — The client-side JavaScript writes a `gdpr_consent` cookie containing a JSON object (e.g. `{"analytics": true, "marketing": false}`) and reloads the page.
3. **Subsequent visits** — `RegisterGdpr` reads the consent via `CookieConsentStorage` and only loads scripts whose category was accepted (or that belong to a `required` category).

---

## Ports & adapters

The bundle follows the hexagonal architecture pattern:

- **Port:** `ConsentStorageInterface` abstracts the storage mechanism. Consumer code depends on this interface, not on cookies directly.
- **Adapter:** `CookieConsentStorage` implements the port using `$_COOKIE`.

Replacing the storage mechanism (e.g. using a database or local storage API) requires only a new adapter implementing `ConsentStorageInterface`.

The registries (`ConsentCategoryRegistry`, `TrackingScriptRegistry`) are populated automatically by the compiler passes `RegisterConsentCategoryPass` and `RegisterTrackingScriptPass`, which collect services tagged `wordpress.consent_category` and `wordpress.tracking_script`.

---

## Rendering separation

`ConsentBanner` orchestrates the HTML construction but delegates the actual rendering to `ConsentBannerRenderer` (Single Responsibility Principle). The renderer handles:

- Checkbox markup for each category
- CSS styles for the banner
- JavaScript for consent interaction

This separation allows replacing the visual presentation (custom design, framework-specific markup) without touching the banner logic.

---

## Cookie security

`CookieConsentStorage` applies several protections when reading the `gdpr_consent` cookie:

- **Type check** — Verifies the value is a string before processing.
- **Size limit** — Rejects values exceeding 4,096 bytes (browser cookie size limit).
- **JSON validation** — Decodes the JSON and validates the returned type is an array.
- **SameSite** — The cookie is written client-side with `SameSite=Lax` to prevent CSRF-based consent manipulation.

---

## Why presets?

Analytics and marketing tools share common integration patterns (inline vs external, head vs footer, priority ordering). The preset classes (`GoogleAnalyticsScript`, `GoogleTagManagerScript`, `HotjarScript`, etc.) encapsulate these patterns so developers only need to provide their tracking ID. Each preset extends `TrackingScript` with the correct defaults for its service.
