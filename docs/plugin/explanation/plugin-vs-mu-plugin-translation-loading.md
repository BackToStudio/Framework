# Plugin vs mu-plugin translation loading

WordPress distinguishes standard plugins (`wp-content/plugins/`) from mu-plugins (`wp-content/mu-plugins/`). The translation loading functions differ:

- `load_plugin_textdomain()` — Searches in `wp-content/plugins/my-plugin/languages/`
- `load_muplugin_textdomain()` — Searches in `wp-content/mu-plugins/my-plugin/languages/`

The bundle provides two separate classes (`LoadPluginTextDomain` and `LoadMuPluginTextDomain`) to handle this difference, both implementing the same hook contract. They share `TextDomainLoaderInterface` as their port, keeping the adapter swappable.
