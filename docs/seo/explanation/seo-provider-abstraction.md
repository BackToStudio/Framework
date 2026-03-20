# SEO provider abstraction

### The problem

WordPress SEO plugins store data in completely different ways:

| Data | Yoast SEO | SEOPress |
|---|---|---|
| SEO title | `_yoast_wpseo_title` | `_seopress_titles_title` |
| Meta description | `_yoast_wpseo_metadesc` | `_seopress_titles_desc` |
| Canonical URL | `_yoast_wpseo_canonical` | `_seopress_robots_canonical` |
| Facebook URL | `wpseo_social[facebook_site]` | `seopress_social_option_name[..._facebook]` |
| Schema output | `wpseo_json_ld_output` filter | `seopress_schemas_auto_enabled` filter |

A theme accessing these meta keys directly couples itself to a specific plugin.

### The solution: SeoProviderInterface

The framework defines a unified `SeoProviderInterface` that extends `MetaProviderInterface` (title, description, Open Graph) and `SocialLinksProviderInterface` (social URLs). Each plugin is wrapped in a provider:

- `YoastProvider` -- reads `wpseo_social` and `_yoast_wpseo_*`
- `SeoPressProvider` -- reads `seopress_social_option_name` and `_seopress_*`

The `SeoManager` iterates over registered providers and returns the first one where `isActive()` returns `true`. Theme code never needs to know which plugin is installed.

### Adding support for a new plugin

To support a new SEO plugin (e.g. Rank Math):

1. Create a class implementing `SeoProviderInterface`
2. Register it via `SeoManager::addProvider()`

The rest of the framework (social links in Twig, Organization `sameAs`, plugin schema disabling) works automatically.
