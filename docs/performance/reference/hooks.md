# Hooks

### `ServePageCache`

| Hook | Type | Callback | Priority |
|---|---|---|---|
| `init` | action | `serveCachedPage` | `0` |
| `template_redirect` | action | `startOutputBuffering` | `10` |

Response headers: `X-Page-Cache: HIT` on cache hit. HTML comment `<!-- X-Page-Cache: MISS -->` on cache miss. Excludes 404 and search pages from output buffering.

### `InvalidatePageCache`

| Hook | Type | Callback |
|---|---|---|
| `save_post` | action | `onPostSaved` |
| `deleted_post` | action | `onPostDeleted` |
| `transition_post_status` | action | `onPostStatusChange` |
| `comment_post` | action | `onCommentChange` |
| `edit_comment` | action | `onCommentChange` |
| `switch_theme` | action | `flushAll` |
| `customize_save_after` | action | `flushAll` |

### `PreloadPageCache`

| Hook | Type | Callback | Priority |
|---|---|---|---|
| `save_post` | action | `schedulePostPreload` | `20` |
| `transition_post_status` | action | `scheduleOnPublish` | `20` |
| `switch_theme` | action | `scheduleFullPreload` | `10` |
| `customize_save_after` | action | `scheduleFullPreload` | `10` |
| `btf_preload_page_cache` | action | `executePostPreload` | `10` |
| `btf_preload_page_cache_full` | action | `executeFullPreload` | `10` |

Constants: `CRON_HOOK = 'btf_preload_page_cache'`, `CRON_FULL_HOOK = 'btf_preload_page_cache_full'`.

### `CleanHead`

Removes from `wp_head`: `rsd_link`, `wlwmanifest_link`, `wp_shortlink_wp_head`, `rest_output_link_wp_head`, `wp_oembed_add_discovery_links`, `adjacent_posts_rel_link_wp_head`, `wp_generator`, `feed_links` (priority 2), `feed_links_extra` (priority 3). Filters `wp_resource_hints` to remove `s.w.org` DNS prefetch.

### `DisableEmojis`

Removes from `wp_head`: `print_emoji_detection_script` (priority 7). Removes from `admin_print_scripts`, `wp_print_styles`, `admin_print_styles`. Filters `wp_resource_hints` to remove emoji CDN DNS prefetch. Filters `tiny_mce_plugins` to remove `wpemoji`. Filters `emoji_svg_url` to return `false`.

### `DisableEmbeds`

Removes `wp_oembed_add_discovery_links` and `wp_oembed_add_host_js` from `wp_head`. Deregisters `wp-embed` script on `wp_footer`. Filters `embed_oembed_discover` to return `false`. Filters `rewrite_rules_array` to remove `embed=true` rules.

### `DisableXMLRPC`

Filters `xmlrpc_enabled` to return `false`. Filters `wp_headers` to remove `X-Pingback` header. Removes `rsd_link` from `wp_head`.

### `DisableHeartbeat`

| Hook | Type | Callback |
|---|---|---|
| `init` | action | `deregisterHeartbeatOnFrontend` |
| `heartbeat_settings` | filter | `setAdminInterval` |

### `DeferScripts`

| Hook | Type | Callback |
|---|---|---|
| `script_loader_tag` | filter | `addDeferAttribute` |
| `script_loader_src` | filter | `removeVersionQueryString` |
| `style_loader_src` | filter | `removeVersionQueryString` |

Skips admin pages and scripts that already have `defer` or `async`.

### `OptimizeImages`

| Hook | Type | Callback |
|---|---|---|
| `wp_get_attachment_image_attributes` | filter | `addDecodingAsync` |
| `wp_content_img_tag` | filter | `addFetchPriorityToLcp` |
| `wp_lazy_loading_enabled` | filter | `enableLazyLoading` |

### `AddResourceHints`

| Hook | Type | Callback | Priority |
|---|---|---|---|
| `wp_resource_hints` | filter | `addHints` | `10` |
| `wp_head` | action | `addPreloadLinks` | `1` |

### `MinifyHtml`

| Hook | Type | Callback |
|---|---|---|
| `template_redirect` | action | `startBuffering` |

Only active when `performance.minify_html` is `true`. Skips admin pages.

### `RemoveUnusedCss`

| Hook | Type | Callback | Priority |
|---|---|---|---|
| `template_redirect` | action | `startBuffering` | `9` |

Only active when `performance.remove_unused_css` is `true`. Runs before `MinifyHtml`. Preserves style blocks with `id="global-styles-inline-css"`. Always keeps `@-rules`.

### `LimitPostRevisions`

| Hook | Type | Callback |
|---|---|---|
| `wp_revisions_to_keep` | filter | `limitRevisions` |

### `OptimizeWooCommerce`

| Hook | Type | Callback | Priority |
|---|---|---|---|
| `wp_enqueue_scripts` | action | `dequeueWooCommerceAssets` | `99` |

Only active when the `WooCommerce` class is loaded. Dequeues styles: `woocommerce-general`, `woocommerce-layout`, `woocommerce-smallscreen`, `wc-blocks-style`. Dequeues scripts: `wc-cart-fragments`, `woocommerce`, `wc-add-to-cart`. Preserves assets on pages matching `is_woocommerce()`, `is_cart()`, `is_checkout()`, or `is_account_page()`.

### `OptimizeHtaccess`

| Hook | Type | Callback |
|---|---|---|
| `admin_init` | action | `applyDirectives` |

Implements `ActivationHooks`: calls `applyDirectives()` on activation, `removeDirectives()` on deactivation. Marker: `BackTo Performance`. Skips writes when directives hash matches stored transient.

### `CleanDashboard`

| Hook | Type | Callback |
|---|---|---|
| `wp_dashboard_setup` | action | `removeDashboardWidgets` |

Implements `AdminHooks`. Removes meta boxes: `dashboard_incoming_links`, `dashboard_plugins`, `dashboard_primary`, `dashboard_secondary`, `dashboard_quick_press`, `dashboard_recent_drafts`. Removes `wp_welcome_panel`.
