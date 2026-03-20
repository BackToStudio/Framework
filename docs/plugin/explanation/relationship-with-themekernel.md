# Relationship with ThemeKernel


`PluginKernel` and `ThemeKernel` both extend `AbstractKernel`. The differences are:

- **Parameter names** — `pluginDirectory` / `pluginTextDomain` vs `themeDirectory` / `themeTextDomain`
- **Translation hook** — `init` for plugins, `after_setup_theme` for themes
- **Translation path** — Plugin and theme directories use different WordPress lookup functions

The shared `AbstractKernel` provides the container compilation, extension management, and hook execution logic.
