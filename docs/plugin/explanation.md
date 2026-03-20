# Plugin Bundle — Architecture & Design

*Explanation — Understanding-oriented*

---

## Kernel lifecycle

The `PluginKernel` follows a three-phase lifecycle:

1. **Construction** — The kernel receives the environment name (`production`, `development`, etc.) and the debug flag.
2. **Configuration** — `setProjectDir()` and `setTextDomain()` set the DI container parameters. Extensions are registered via `addExtension()`.
3. **Boot** — `boot()` creates a `ContainerBuilder`, loads the service configuration from `Resources/config/services.php`, compiles the container, and executes all registered hooks.

---

## Container parameters

The kernel injects two parameters into the DI container:

- `%pluginDirectory%` — Absolute path to the plugin root. Used to locate resources (templates, assets, translation files).
- `%pluginTextDomain%` — The text domain string. Used by I18n classes to load translations.

These parameters are bound via `$pluginDirectory` and `$pluginTextDomain` in the service configuration, enabling automatic constructor injection.

---

## Plugin vs mu-plugin translation loading

WordPress distinguishes standard plugins (`wp-content/plugins/`) from mu-plugins (`wp-content/mu-plugins/`). The translation loading functions differ:

- `load_plugin_textdomain()` — Searches in `wp-content/plugins/my-plugin/languages/`
- `load_muplugin_textdomain()` — Searches in `wp-content/mu-plugins/my-plugin/languages/`

The bundle provides two separate classes (`LoadPluginTextDomain` and `LoadMuPluginTextDomain`) to handle this difference, both implementing the same hook contract. They share `TextDomainLoaderInterface` as their port, keeping the adapter swappable.

---

## Relationship with ThemeKernel

`PluginKernel` and `ThemeKernel` both extend `AbstractKernel`. The differences are:

- **Parameter names** — `pluginDirectory` / `pluginTextDomain` vs `themeDirectory` / `themeTextDomain`
- **Translation hook** — `init` for plugins, `after_setup_theme` for themes
- **Translation path** — Plugin and theme directories use different WordPress lookup functions

The shared `AbstractKernel` provides the container compilation, extension management, and hook execution logic.
