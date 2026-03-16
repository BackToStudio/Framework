# GDPR Consent Management Design

## The problem

European privacy regulations (GDPR, ePrivacy) require websites to obtain user consent before loading non-essential tracking scripts. Most WordPress solutions rely on heavyweight plugins that inject global JavaScript, conflict with caching layers, and couple the application to a specific vendor.

A framework-level solution should be lightweight, follow the same architectural patterns as the rest of the framework, and give developers full control over which scripts load under which conditions.

## Cookie-based consent storage

Consent state is stored in a single JSON cookie (`gdpr_consent`):

```json
{"analytics": true, "marketing": false, "functional": true}
```

This design has three advantages:

1. **No database dependency.** Consent state is read from `$_COOKIE` on every request. No `wp_options` query, no user meta lookup.
2. **Immediate availability.** PHP reads the cookie at request start. Server-side code can gate script output before any HTML is sent.
3. **No user account required.** Anonymous visitors get the same consent mechanism as logged-in users.

The tradeoff is that consent does not survive cookie deletion and cannot be audited server-side. For sites requiring an audit trail, a custom `ConsentStorageInterface` adapter can persist consent to a database while still reading from the cookie for performance.

## Consent categories vs. individual scripts

The module separates **categories** (analytics, marketing, functional) from **scripts** (Google Analytics, HubSpot, Hotjar). Each script declares which category it belongs to via `getCategoryKey()`.

This separation means:

- Users consent to categories, not to individual scripts. The banner stays simple.
- Developers can add or remove scripts without changing consent logic.
- Required categories (e.g., functional cookies) are always loaded regardless of user choice.

## Script loading flow

```
Request arrives
    │
    ├── CookieConsentStorage reads $_COOKIE['gdpr_consent']
    │
    ├── wp_head fires
    │   └── RegisterGdpr::renderHeadScripts()
    │       ├── For each script with location='head', sorted by priority:
    │       │   ├── Is category required? → output script
    │       │   └── Has user consented? → output script
    │       └── Skip otherwise
    │
    ├── wp_footer fires
    │   ├── RegisterGdpr::renderFooterScripts() (same logic, location='footer')
    │   └── RegisterGdpr::renderConsentBanner()
    │       └── Renders banner HTML/CSS/JS inline
    │
    └── User interacts with banner
        └── JavaScript sets cookie, page reloads
            └── Next request picks up new consent state
```

The page reload after consent is intentional. It ensures PHP sees the updated cookie values and can gate scripts server-side. This prevents the flash of tracking scripts that client-only solutions suffer from.

## Presets: reducing boilerplate

Common tracking services (Google Analytics, GTM, Hotjar, HubSpot, Google Ads) follow well-known patterns. The `Preset/` namespace provides ready-to-use `TrackingScriptInterface` implementations that only require a tracking ID:

```php
new GoogleAnalyticsScript('G-XXXXXXX')
```

Each preset encodes the correct snippet, location, priority, and consent category. Developers register them in their DI configuration with a single `->tag('wordpress.tracking_script')` call.

Presets are not special — they are regular `TrackingScriptInterface` implementations. Developers can create their own presets for any tracking service by following the same pattern.

## Why not `wp_enqueue_script`?

WordPress's asset pipeline (`wp_enqueue_script` / `wp_register_script`) is designed for theme and plugin JavaScript files. Tracking scripts have different requirements:

- They must render **inline snippets**, not just external files.
- They must execute **before** any other script (GTM wants priority 1 in `<head>`).
- They must be **conditionally gated** by consent state, not by page context.
- They often combine an external loader (`gtag.js`) with an inline configuration block.

Direct `<script>` output via `wp_head` / `wp_footer` hooks gives full control over ordering and format without fighting the enqueue system.

## Extending the module

The module follows the same extension patterns as the rest of the framework:

- **New consent categories**: Implement `ConsentCategoryInterface` and tag with `wordpress.consent_category`.
- **New tracking scripts**: Implement `TrackingScriptInterface` and tag with `wordpress.tracking_script`.
- **Custom storage**: Implement `ConsentStorageInterface` and override the port binding in `WordPressExtension`.
- **Custom banner**: Replace or extend `ConsentBanner` via DI service decoration.
