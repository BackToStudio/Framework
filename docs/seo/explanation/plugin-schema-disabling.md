# Plugin schema disabling

SEO plugins generate their own JSON-LD, which would duplicate the framework's output. `DisablePluginSchema` neutralizes this:

- **Yoast SEO**: `wpseo_json_ld_output` filter with `__return_empty_array`
- **SEOPress**: `seopress_schemas_auto_enabled` filter with `__return_false`

This is enabled by default (`schema.disable_plugin_schema` is `true`). Themes can disable it in `config/seo.php` to keep the plugin's schema output.

The action checks that an SEO provider is active before attempting to disable anything, avoiding unnecessary filter registration.
