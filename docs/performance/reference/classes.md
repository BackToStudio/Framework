# Classes

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
