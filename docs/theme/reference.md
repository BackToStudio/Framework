# Theme Bundle — API Reference

*Reference — Information-oriented*

---

## Classes

### `ThemeKernel`

**Namespace:** `BackTo\Framework\Bundle\Theme`
**Extends:** `AbstractKernel`

Entry point for the theme.

#### Constructor

```php
new ThemeKernel(
    string $environment,
    bool $debug,
);
```

#### Inherited methods

| Method | Return | Description |
|---|---|---|
| `setProjectDir(string $dir)` | `void` | Set the theme root directory |
| `setTextDomain(string $domain)` | `void` | Set the text domain |
| `addExtension(ExtensionInterface $extension)` | `void` | Register a bundle extension |
| `boot()` | `void` | Compile the container and start hooks |

#### Protected methods

| Method | Return | Description |
|---|---|---|
| `getDirectoryParameterName()` | `string` | Returns `'themeDirectory'` |
| `getTextDomainParameterName()` | `string` | Returns `'themeTextDomain'` |
| `getKernelConfigDir()` | `string` | Path to `Resources/config` |

---

### `LoadThemeTextDomain`

**Namespace:** `BackTo\Framework\Bundle\Theme\I18n`
**Implements:** `Hooks`

Loads theme translations on the `after_setup_theme` hook via `load_theme_textdomain()`.

| Injected Parameter | Type | Description |
|---|---|---|
| `$themeDirectory` | `string` | Theme root directory |
| `$themeTextDomain` | `string` | Theme text domain |

---

## Cleanup Actions

All actions are in `BackTo\Framework\Bundle\Theme\Actions` and implement `Hooks`.

| Class | Hook | Effect |
|---|---|---|
| `CleanHead` | `wp_head` (remove) | Removes `feed_links_extra`, `feed_links`, `rsd_link`, `wlwmanifest_link`, `index_rel_link`, `parent_post_rel_link`, `start_post_rel_link`, `adjacent_posts_rel_link` |
| `RemoveEmojis` | Multiple (remove) | Removes emoji scripts/styles from frontend, admin, and emails |
| `RemoveWordPressVersion` | `wp_head` (remove) + `the_generator` (filter) | Removes `wp_generator` and returns empty string for the generator filter |
| `RemoveSvgFilters` | `wp_body_open` (remove) | Removes `wp_global_styles_render_svg_filters` and the Gutenberg variant |
| `RemoveNavigationFallback` | `block_core_navigation_render_fallback` (filter) | Returns `false` to disable the navigation fallback |

---

## Container Parameters

| Parameter | Description |
|---|---|
| `%themeDirectory%` | Absolute path to the theme root |
| `%themeTextDomain%` | Theme text domain string |
