# Page cache lifecycle

The page cache operates in five phases: **serve**, **capture**, **store**, **invalidate**, and **preload**.

### Serve (early exit)

`ServePageCache` hooks into `init` at priority 0 — the earliest possible point after WordPress bootstraps. If the request is cacheable and a cached file exists, the HTML is sent directly and PHP execution stops with `exit`. The response time drops from hundreds of milliseconds to single-digit milliseconds because neither the database nor the theme is loaded.

### Capture (output buffering)

On a cache miss, WordPress renders the page normally. At `template_redirect`, output buffering starts via `ob_start`. Pages that should not be cached (404s, search results) are excluded at this stage.

### Store (write to disk)

When the output buffer flushes at shutdown, the callback receives the complete HTML. Two safety checks run before storing: the HTML must not be empty and must not contain `Fatal error` (to avoid caching PHP error pages). The cached file is written alongside a `.meta` file containing the original URL, creation timestamp, and expiry timestamp.

### Invalidate (granular + full flush)

When a post is saved, only that post's cached page is invalidated via its permalink. A full cache flush is triggered in three cases: a post transitions to or from `publish` status (archives and listings change), the theme is switched (all HTML output changes), or the Customizer is saved (layout and styles may change). Comment changes invalidate the parent post's cache.

### Preload (background warming)

After invalidation, the cache is cold. `PreloadPageCache` schedules a WP-Cron event (with a configurable delay, default 5 seconds) that sends non-blocking loopback HTTP requests to the affected URLs. Each request triggers the normal serve-capture-store cycle, warming the cache before the next visitor arrives.

For post-level changes, `PreloadUrlCollector` gathers not just the post permalink but also the home page, blog page, post type archive, taxonomy archives, author page, and date archives. For site-wide changes (theme switch, Customizer save), it collects recent posts, pages, categories, and popular tags up to the configured batch size.
