# Getting started with the Performance bundle

*Tutorial — Learning-oriented*

This tutorial walks you through enabling page caching and verifying that it works. By the end, you will have a working page cache that serves static HTML to visitors, and you will have seen the difference between a cache miss and a cache hit.

## Prerequisites

- A WordPress plugin or theme using the BackTo Framework
- The framework's DI container configured (the Performance bundle is loaded automatically)
- Access to a browser with developer tools

## Step 1: Create the configuration file

Create `config/performance.php` in your theme or plugin root:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(3600);
};
```

This enables the page cache with a one-hour TTL. The bundle stores pre-rendered HTML on disk and serves it to non-logged-in visitors without executing PHP or loading WordPress.

## Step 2: Visit a page (cache miss)

1. Log out of WordPress (the cache only serves anonymous visitors)
2. Open your browser's developer tools and switch to the **Network** tab
3. Visit any page on your site

In the response, look at the HTML source. You should see a comment at the bottom:

```html
<!-- X-Page-Cache: MISS -->
```

This means the page was rendered normally by WordPress, and the HTML was captured and stored in the cache for next time.

## Step 3: Reload the page (cache hit)

Reload the same page. This time, look at the response headers in your developer tools. You should see:

```
X-Page-Cache: HIT
```

The page was served directly from the cached file. WordPress, PHP, and the database were not involved. The response time should be noticeably faster.

In the HTML source, you will see a timestamp comment:

```html
<!-- Cached by BackTo Framework at 2026-03-20 14:30:00 UTC -->
```

## Step 4: See automatic invalidation

The cache automatically clears when content changes. To see this:

1. Log in to WordPress and edit any published post
2. Save the post
3. Log out and visit that post's URL
4. You should see `<!-- X-Page-Cache: MISS -->` again — the cache was invalidated when you saved

On the next reload, the page is served from cache again (`X-Page-Cache: HIT`).

## Step 5: Enable HTML minification

Add one line to your configuration:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(3600)
        ->minifyHtml(true);
};
```

Visit a page and view the source. The HTML is now minified: whitespace between block-level tags is removed, HTML comments are stripped, and inline CSS and JavaScript are compressed. The typical size reduction is 15-25%.

## Next steps

- See [Common tasks](how-to/README.md) for practical recipes (cache TTL tuning, resource hints, WooCommerce optimization, script deferral)
- See [API reference](reference.md) for the complete configuration and class documentation
- See [Architecture](explanation.md) to understand how the cache lifecycle and minification pipeline work
