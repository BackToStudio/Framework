# Performance

WordPress performance optimization with autoconfigured rules. Configure via `config/performance.php` using the fluent `PerformanceConfigurator`.

## Classes

| Class | Role |
|-------|------|
| `PerformanceConfiguration` | Default parameter values for the Performance module |
| `PerformanceConfigurator` | Fluent configurator for `config/performance.php` (implements `ModuleConfiguratorInterface`) |
| `CleanHead` | Removes unnecessary tags from `<head>` |
| `DisableEmojis` | Disables emoji scripts and styles |
| `DisableEmbeds` | Disables oEmbed scripts |
| `HeartbeatControl` | Controls heartbeat frequency and frontend disable |
| `DeferScripts` | Defers script loading |
| `RemoveQueryStrings` | Strips version query strings from assets |
| `LazyLoadOptimizer` | Configures lazy loading, decoding, and fetchpriority |
| `HtmlMinifier` | Minifies HTML output |
| `ResourceHints` | Adds preconnect, dns-prefetch, and preload hints |
| `RevisionLimiter` | Limits post revisions |
| `WooCommerceOptimizer` | Disables unnecessary WooCommerce assets |
| `PageCacheManager` | Full page cache |
| `DatabaseCleanup` | Cleans up old revisions and transients |
| `CachePreloader` | Preloads cache for popular pages |
| `HtaccessOptimizer` | Gzip, browser cache, ETags, Keep-Alive |
| `Contracts\HtmlOptimizerInterface` | Port interface for HTML optimization |
| `Contracts\DatabaseOptimizerInterface` | Port interface for database optimization |
| `Contracts\PageCacheInterface` | Port interface for page caching |

## Configuration

Performance parameters are managed by `PerformanceConfiguration` and overridden via the fluent `PerformanceConfigurator` in `config/performance.php`:

```php
<?php

use BackTo\Framework\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(7200)
        ->minifyHtml(true)
        ->htaccessGzip(false);
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `performance.clean_head` | `true` | `cleanHead(bool)` |
| `performance.disable_emojis` | `true` | `disableEmojis(bool)` |
| `performance.disable_embeds` | `true` | `disableEmbeds(bool)` |
| `performance.disable_xmlrpc` | `true` | `disableXmlrpc(bool)` |
| `performance.heartbeat.disable_frontend` | `true` | `heartbeatDisableFrontend(bool)` |
| `performance.heartbeat.admin_interval` | `60` | `heartbeatAdminInterval(int)` |
| `performance.defer_scripts` | `true` | `deferScripts(bool)` |
| `performance.defer_exclude` | `['jquery-core', 'jquery-migrate']` | `deferExclude(array)` |
| `performance.remove_query_strings` | `true` | `removeQueryStrings(bool)` |
| `performance.lazy_load_skip_first` | `1` | `lazyLoadSkipFirst(int)` |
| `performance.add_decoding_async` | `true` | `addDecodingAsync(bool)` |
| `performance.add_fetchpriority` | `true` | `addFetchpriority(bool)` |
| `performance.minify_html` | `false` | `minifyHtml(bool)` |
| `performance.resource_hints.preconnect` | `[]` | `preconnect(array)` |
| `performance.resource_hints.dns_prefetch` | `[]` | `dnsPrefetch(array)` |
| `performance.resource_hints.preload` | `[]` | `preload(array)` |
| `performance.revisions_limit` | `5` | `revisionsLimit(int)` |
| `performance.woocommerce_optimize` | `true` | `woocommerceOptimize(bool)` |
| `performance.page_cache.enabled` | `false` | `pageCacheEnabled(bool)` |
| `performance.page_cache.ttl` | `3600` | `pageCacheTtl(int)` |
| `performance.db_cleanup.revisions_limit` | `5` | `dbCleanupRevisionsLimit(int)` |
| `performance.cache_preload.enabled` | `true` | `cachePreloadEnabled(bool)` |
| `performance.cache_preload.delay` | `5` | `cachePreloadDelay(int)` |
| `performance.cache_preload.batch_size` | `50` | `cachePreloadBatchSize(int)` |
| `performance.htaccess.gzip` | `true` | `htaccessGzip(bool)` |
| `performance.htaccess.browser_cache` | `true` | `htaccessBrowserCache(bool)` |
| `performance.htaccess.remove_etags` | `true` | `htaccessRemoveEtags(bool)` |
| `performance.htaccess.keep_alive` | `true` | `htaccessKeepAlive(bool)` |
| `performance.htaccess.static_ttl` | `31536000` | `htaccessStaticTtl(int)` |
