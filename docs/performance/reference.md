# Performance — API Reference

*Reference — Information-oriented*

---

## Interfaces

### `PageCacheInterface`

**Namespace:** `BackTo\Framework\Bundle\Performance\Contracts`

| Method | Return | Description |
|---|---|---|
| `get(string $url)` | `?string` | Cached HTML, or `null` on miss |
| `put(string $url, string $html, int $ttl)` | `void` | Store a page with TTL in seconds |
| `invalidate(string $url)` | `void` | Remove a cached page by URL |
| `flush()` | `void` | Remove all cached pages |

### `HtmlOptimizerInterface`

**Namespace:** `BackTo\Framework\Bundle\Performance\Contracts`

| Method | Return | Description |
|---|---|---|
| `optimize(string $html)` | `string` | Optimize HTML content (minification, cleanup) |

### `DatabaseOptimizerInterface`

**Namespace:** `BackTo\Framework\Bundle\Performance\Contracts`

| Method | Return | Description |
|---|---|---|
| `cleanup()` | `array<string, int>` | Run all cleanup tasks. Returns rows affected per task |
| `optimizeTables()` | `int` | Run `OPTIMIZE TABLE` on all prefixed tables. Returns count |

---

## Classes

### `PerformanceConfigurator`

**Namespace:** `BackTo\Framework\Bundle\Performance`
**Implements:** `ModuleConfiguratorInterface`

Fluent configurator used in `config/performance.php`. All methods return `self`.

| Method | Parameter key | Default |
|---|---|---|
| `cleanHead(bool)` | `performance.clean_head` | `true` |
| `disableEmojis(bool)` | `performance.disable_emojis` | `true` |
| `disableEmbeds(bool)` | `performance.disable_embeds` | `true` |
| `disableXmlrpc(bool)` | `performance.disable_xmlrpc` | `true` |
| `heartbeatDisableFrontend(bool)` | `performance.heartbeat.disable_frontend` | `true` |
| `heartbeatAdminInterval(int)` | `performance.heartbeat.admin_interval` | `60` |
| `deferScripts(bool)` | `performance.defer_scripts` | `true` |
| `deferExclude(string[])` | `performance.defer_exclude` | `['jquery-core', 'jquery-migrate']` |
| `removeQueryStrings(bool)` | `performance.remove_query_strings` | `true` |
| `lazyLoadSkipFirst(int)` | `performance.lazy_load_skip_first` | `1` |
| `addDecodingAsync(bool)` | `performance.add_decoding_async` | `true` |
| `addFetchpriority(bool)` | `performance.add_fetchpriority` | `true` |
| `minifyHtml(bool)` | `performance.minify_html` | `false` |
| `removeUnusedCss(bool)` | `performance.remove_unused_css` | `false` |
| `preconnect(string[])` | `performance.resource_hints.preconnect` | `[]` |
| `dnsPrefetch(string[])` | `performance.resource_hints.dns_prefetch` | `[]` |
| `preload(array[])` | `performance.resource_hints.preload` | `[]` |
| `revisionsLimit(int)` | `performance.revisions_limit` | `5` |
| `woocommerceOptimize(bool)` | `performance.woocommerce_optimize` | `true` |
| `pageCacheEnabled(bool)` | `performance.page_cache.enabled` | `false` |
| `pageCacheTtl(int)` | `performance.page_cache.ttl` | `3600` |
| `dbCleanupRevisionsLimit(int)` | `performance.db_cleanup.revisions_limit` | `5` |
| `cachePreloadEnabled(bool)` | `performance.cache_preload.enabled` | `true` |
| `cachePreloadDelay(int)` | `performance.cache_preload.delay` | `5` |
| `cachePreloadBatchSize(int)` | `performance.cache_preload.batch_size` | `50` |
| `htaccessGzip(bool)` | `performance.htaccess.gzip` | `true` |
| `htaccessBrowserCache(bool)` | `performance.htaccess.browser_cache` | `true` |
| `htaccessRemoveEtags(bool)` | `performance.htaccess.remove_etags` | `true` |
| `htaccessKeepAlive(bool)` | `performance.htaccess.keep_alive` | `true` |
| `htaccessStaticTtl(int)` | `performance.htaccess.static_ttl` | `31536000` |

### `CacheableRequestChecker`

**Namespace:** `BackTo\Framework\Bundle\Performance`

| Method | Return | Description |
|---|---|---|
| `isCacheable()` | `bool` | Whether the current request is eligible for caching |

A request is cacheable when: method is GET, visitor is not logged in, no query parameters, not an admin page, and URL does not match an excluded prefix.

Default excluded prefixes: `/wp-admin`, `/wp-json`, `/wp-login.php`, `/wp-cron.php`, `/xmlrpc.php`.

### `RequestUrlResolver`

**Namespace:** `BackTo\Framework\Bundle\Performance`

| Method | Return | Description |
|---|---|---|
| `getCurrentUrl()` | `string` | Canonical URL (scheme + validated host + path, no query string) |

### `PreloadUrlCollector`

**Namespace:** `BackTo\Framework\Bundle\Performance`

| Method | Return | Description |
|---|---|---|
| `getPostRelatedUrls(int $postId)` | `string[]` | URLs related to a post (permalink, home, blog, archives, taxonomies, author, date) |
| `getSiteUrls()` | `string[]` | Site-wide URLs (home, recent posts, pages, categories, tags) |

### `PreloadExecutor`

**Namespace:** `BackTo\Framework\Bundle\Performance`

| Method | Return | Description |
|---|---|---|
| `preload(string[] $urls)` | `void` | Send non-blocking loopback GET requests for each URL not already cached |

Sends `X-Cache-Preload: 1` and `Cache-Control: no-cache` headers.

---

## Hooks

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

---

## Infrastructure

### `WordPressPageCache`

**Implements:** `PageCacheInterface`

Filesystem-based cache. Each URL is hashed with MD5. Two files per entry: `{cacheDir}/{md5}.html` and `{cacheDir}/{md5}.meta` (serialized metadata with `url`, `expiry`, `created`).

### `WordPressHtmlOptimizer`

**Implements:** `HtmlOptimizerInterface`

Delegates to `CssMinifier` and `JsMinifier`. Preserves `<pre>`, `<code>`, `<textarea>` content. Strips HTML comments (except IE conditionals). Removes `type="text/javascript"` and `type="text/css"` attributes.

### `WordPressDatabaseOptimizer`

**Implements:** `DatabaseOptimizerInterface`

Cleanup tasks: `revisions`, `auto_drafts`, `trashed_posts`, `spam_comments`, `trashed_comments`, `expired_transients`, `orphaned_postmeta`, `orphaned_commentmeta`.

### `CssMinifier` / `JsMinifier`

**Namespace:** `BackTo\Framework\Bundle\Performance\Infrastructure`

`CssMinifier::minify(string $css): string` — Removes comments, collapses whitespace, strips spaces around punctuation, shortens hex colors, removes zero units.

`JsMinifier::minify(string $js): string` — Preserves string literals, removes comments (keeps `/*! */`), collapses whitespace, restores keyword spacing. Skips JSON-LD, importmaps, and `application/json` scripts.

---

## CSS utilities

**Namespace:** `BackTo\Framework\Bundle\Performance\Css`

| Class | Method | Description |
|---|---|---|
| `HtmlSelectorExtractor` | `extract(string $markup): array` | Returns `{classes, ids, tags}` as `array<string, true>` maps |
| `CssRuleFilter` | `filter(string $css, array $selectors): string` | Keeps only rules whose selectors match. Always keeps `@-rules` |
| `SelectorMatcher` | `isSelectorUsed(string $selector, array $selectors): bool` | `true` if selector matches page elements |

---

## DI Registration

Port bindings registered in `PerformanceExtension`:

| Interface | Implementation |
|---|---|
| `HtmlOptimizerInterface` | `WordPressHtmlOptimizer` |
| `DatabaseOptimizerInterface` | `WordPressDatabaseOptimizer` |
| `PageCacheInterface` | `WordPressPageCache` |
