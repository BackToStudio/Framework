# Relationship with ThemeKernel

`PluginKernel` and `ThemeKernel` both extend `AbstractKernel`. The differences are:

- **DI parameter names** — `pluginDirectory` / `pluginTextDomain` vs `themeDirectory` / `themeTextDomain`
- **Translation hook** — `init` for plugins, `after_setup_theme` for themes (WordPress requires theme translations to be loaded during theme setup)
- **Translation lookup path** — `load_plugin_textdomain()` searches in the plugin's `languages/` directory, while `load_theme_textdomain()` searches in the theme's directory

The shared `AbstractKernel` provides the container compilation, extension management, and hook execution logic.

See also: [Theme bundle — Difference from PluginKernel](../../theme/explanation/difference-from-pluginkernel.md)
