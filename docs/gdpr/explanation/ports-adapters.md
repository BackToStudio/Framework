# Ports & adapters

The bundle follows the hexagonal architecture pattern:

- **Port:** `ConsentStorageInterface` abstracts the storage mechanism. Consumer code depends on this interface, not on cookies directly.
- **Adapter:** `CookieConsentStorage` implements the port using `$_COOKIE`.

Replacing the storage mechanism (e.g. using a database or local storage API) requires only a new adapter implementing `ConsentStorageInterface`.

The registries (`ConsentCategoryRegistry`, `TrackingScriptRegistry`) are populated automatically by the compiler passes `RegisterConsentCategoryPass` and `RegisterTrackingScriptPass`, which collect services tagged `wordpress.consent_category` and `wordpress.tracking_script`.
