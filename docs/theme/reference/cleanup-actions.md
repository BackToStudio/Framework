# Cleanup Actions

All actions are in `BackTo\Framework\Bundle\Theme\Actions` and implement `Hooks`.

| Class | Hook | Effect |
|---|---|---|
| `CleanHead` | `wp_head` (remove) | Removes `feed_links_extra`, `feed_links`, `rsd_link`, `wlwmanifest_link`, `index_rel_link`, `parent_post_rel_link`, `start_post_rel_link`, `adjacent_posts_rel_link` |
| `RemoveEmojis` | Multiple (remove) | Removes emoji scripts/styles from frontend, admin, and emails |
| `RemoveWordPressVersion` | `wp_head` (remove) + `the_generator` (filter) | Removes `wp_generator` and returns empty string for the generator filter |
| `RemoveSvgFilters` | `wp_body_open` (remove) | Removes `wp_global_styles_render_svg_filters` and the Gutenberg variant |
| `RemoveNavigationFallback` | `block_core_navigation_render_fallback` (filter) | Returns `false` to disable the navigation fallback |
