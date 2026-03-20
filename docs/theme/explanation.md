# Theme Bundle — Architecture & Design

*Explanation — Understanding-oriented*

---

## Why clean the `<head>`?

WordPress injects numerous tags into the HTML `<head>` by default: RSS feed links, RSD (XML-RPC) links, Windows Live Writer manifest, emoji scripts and styles, version meta tags, SVG filters, and more. Most of these are unnecessary for a modern site and create problems:

- **Performance** — Emoji scripts and styles add HTTP requests and blocking JavaScript on every page load.
- **Security** — The WordPress version meta tag reveals the exact version to attackers scanning for known vulnerabilities.
- **HTML pollution** — Relational links, SVG filters, and feed links bloat the document with unused markup.

The bundle applies a **secure by default** approach: all cleanup actions are enabled automatically. Developers opt out of specific actions rather than opting in.

---

## Action granularity

Each cleanup action is a standalone class implementing the `Hooks` interface. They are discovered by Symfony's autowiring and executed at boot. This granularity means:

- Disabling one action does not affect the others.
- Each action is independently testable.
- New cleanup actions can be added without modifying existing code.

---

## Difference from PluginKernel

`ThemeKernel` and `PluginKernel` both extend `AbstractKernel`. The differences are:

- **DI parameter names** — `themeDirectory` / `themeTextDomain` vs `pluginDirectory` / `pluginTextDomain`.
- **Translation hook** — `after_setup_theme` for themes, `init` for plugins. WordPress requires theme translations to be loaded during theme setup.
- **Translation lookup path** — `load_theme_textdomain()` searches in the theme's `languages/` directory, while `load_plugin_textdomain()` searches in the plugin's directory.

The shared `AbstractKernel` handles container compilation, extension management, and hook execution identically for both.
