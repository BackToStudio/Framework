# Container parameters

The kernel injects two parameters into the DI container:

- `%pluginDirectory%` — Absolute path to the plugin root. Used to locate resources (templates, assets, translation files).
- `%pluginTextDomain%` — The text domain string. Used by I18n classes to load translations.

These parameters are bound via `$pluginDirectory` and `$pluginTextDomain` in the service configuration, enabling automatic constructor injection.
