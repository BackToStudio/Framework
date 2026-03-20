# Consent flow

The consent lifecycle has three phases:

1. **First visit** — No `gdpr_consent` cookie exists. `CookieConsentStorage::isConsentGiven()` returns `false`. The banner is displayed automatically via the `wp_footer` hook.
2. **User choice** — The client-side JavaScript writes a `gdpr_consent` cookie containing a JSON object (e.g. `{"analytics": true, "marketing": false}`) and reloads the page.
3. **Subsequent visits** — `RegisterGdpr` reads the consent via `CookieConsentStorage` and only loads scripts whose category was accepted (or that belong to a `required` category).
