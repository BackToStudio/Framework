# Performance — How-to guides

*How-to — Task-oriented*

Practical recipes for common performance tuning tasks.

---

## Configure the page cache TTL

Pages are cached for 3600 seconds (1 hour) by default. To change the duration:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(7200); // 2 hours
};
```

For sites with infrequently updated content, a longer TTL (e.g. `86400` for 24 hours) is safe. Auto-invalidation ensures content changes are reflected immediately regardless of TTL.

---

## Exclude URL prefixes from page caching

The `CacheableRequestChecker` excludes `/wp-admin`, `/wp-json`, `/wp-login.php`, `/wp-cron.php`, and `/xmlrpc.php` by default. To add custom prefixes, override the constructor argument in your extension:

```php
<?php

use BackTo\Framework\Bundle\Performance\CacheableRequestChecker;

$containerBuilder->getDefinition(CacheableRequestChecker::class)
    ->setArgument('$excludedPrefixes', [
        '/wp-admin',
        '/wp-json',
        '/wp-login.php',
        '/wp-cron.php',
        '/xmlrpc.php',
        '/my-private-area',
        '/api/custom',
    ]);
```

---

## Enable cache preloading

Cache preloading warms the cache in the background after content changes, so visitors always hit a warm cache:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->cachePreloadEnabled(true)
        ->cachePreloadDelay(5)       // seconds before preload fires
        ->cachePreloadBatchSize(50); // max URLs per batch
};
```

Preloading is triggered automatically on post save, publish, theme switch, and Customizer save.

---

## Add resource hints (preconnect, dns-prefetch, preload)

### Preconnect

Establish early connections to third-party origins (DNS + TCP + TLS):

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance->preconnect([
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
    ]);
};
```

### DNS prefetch

Resolve DNS only (lighter than preconnect):

```php
$performance->dnsPrefetch([
    '//analytics.example.com',
    '//pixel.tracking.com',
]);
```

### Preload

Force early loading of critical resources. Each resource requires a URL and an `as` type. The `type` field is optional:

```php
$performance->preload([
    ['url' => '/wp-content/themes/my-theme/fonts/custom.woff2', 'as' => 'font', 'type' => 'font/woff2'],
    ['url' => '/wp-content/themes/my-theme/css/critical.css', 'as' => 'style'],
]);
```

The `crossorigin` attribute is added automatically for `font` and `fetch` resources.

---

## Exclude scripts from deferral

By default, all frontend scripts receive the `defer` attribute except `jquery-core` and `jquery-migrate`. To exclude additional scripts:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance->deferExclude([
        'jquery-core',
        'jquery-migrate',
        'my-critical-script',
    ]);
};
```

---

## Enable HTML minification

HTML minification is disabled by default. Enable it explicitly:

```php
$performance->minifyHtml(true);
```

This removes HTML comments, collapses whitespace between block-level tags, minifies inline CSS and JavaScript, and strips redundant `type` attributes. Content inside `<pre>`, `<code>`, and `<textarea>` is preserved.

---

## Enable unused CSS removal

This strips CSS rules from inline `<style>` blocks whose selectors do not match any element in the page HTML:

```php
$performance->removeUnusedCss(true);
```

This is especially effective with block themes that emit per-block inline CSS. The `global-styles-inline-css` block is preserved by default. All `@-rules` (`@media`, `@keyframes`, `@font-face`, etc.) are always kept.

---

## Configure image optimization

### Control how many images skip lazy-loading

The first image (likely the LCP element) is excluded from lazy-loading and receives `fetchpriority="high"` by default. To adjust:

```php
$performance->lazyLoadSkipFirst(2); // first 2 images are not lazy-loaded
```

### Disable decoding="async"

```php
$performance->addDecodingAsync(false);
```

### Disable fetchpriority="high"

```php
$performance->addFetchpriority(false);
```

---

## Optimize WooCommerce assets

WooCommerce asset removal is enabled by default. It dequeues WooCommerce styles and scripts on pages that are not WooCommerce pages (cart, checkout, account, product pages). This saves 200-500 KB per page load.

To disable:

```php
$performance->woocommerceOptimize(false);
```

---

## Configure the Heartbeat API

### Disable Heartbeat on the frontend

```php
$performance->heartbeatDisableFrontend(true); // enabled by default
```

### Change the admin interval

The admin Heartbeat interval defaults to 60 seconds (WordPress default is 15):

```php
$performance->heartbeatAdminInterval(120); // 120 seconds
```

---

## Customize .htaccess static asset TTL

The default TTL for static assets (CSS, JS, images, fonts) in `.htaccess` is 31,536,000 seconds (1 year):

```php
$performance->htaccessStaticTtl(2592000); // 30 days
```

A one-year TTL with versioned filenames (hash in the filename) is the recommended strategy. HTML files are never browser-cached (`max-age=0`) because the server-side page cache manages them.

---

## Disable individual .htaccess optimizations

Each `.htaccess` directive group can be toggled independently:

```php
$performance
    ->htaccessGzip(false)          // disable gzip compression
    ->htaccessBrowserCache(true)   // keep browser caching
    ->htaccessRemoveEtags(true)    // keep ETag removal
    ->htaccessKeepAlive(false);    // disable Keep-Alive
```

---

## Limit post revisions

```php
$performance->revisionsLimit(3); // keep at most 3 revisions per post
```

The default is 5. This applies the `wp_revisions_to_keep` filter.

---

## Disable head cleanup or emoji removal

These are enabled by default. To disable:

```php
$performance
    ->cleanHead(false)
    ->disableEmojis(false)
    ->disableEmbeds(false)
    ->disableXmlrpc(false);
```

---

## Swap the page cache implementation

To replace the filesystem-based cache with a custom implementation (e.g. Redis), override the DI binding:

```php
<?php

use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;

$containerBuilder->register(PageCacheInterface::class, MyRedisPageCache::class)
    ->setAutowired(true);
```

Your implementation must implement `PageCacheInterface`.
