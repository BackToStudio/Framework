# Cookie security

`CookieConsentStorage` applies several protections when reading the `gdpr_consent` cookie:

- **Type check** — Verifies the value is a string before processing.
- **Size limit** — Rejects values exceeding 4,096 bytes (browser cookie size limit).
- **JSON validation** — Decodes the JSON and validates the returned type is an array.
- **SameSite** — The cookie is written client-side with `SameSite=Lax` to prevent CSRF-based consent manipulation.
