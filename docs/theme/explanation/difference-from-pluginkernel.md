# Difference from PluginKernel


`ThemeKernel` and `PluginKernel` both extend `AbstractKernel`. The differences are:

- **DI parameter names** — `themeDirectory` / `themeTextDomain` vs `pluginDirectory` / `pluginTextDomain`.
- **Translation hook** — `after_setup_theme` for themes, `init` for plugins. WordPress requires theme translations to be loaded during theme setup.
- **Translation lookup path** — `load_theme_textdomain()` searches in the theme's `languages/` directory, while `load_plugin_textdomain()` searches in the plugin's directory.

The shared `AbstractKernel` handles container compilation, extension management, and hook execution identically for both.
