# Performance — Architecture & Design

*Explanation — Understanding-oriented*

---

## Why a performance bundle in a WordPress framework?

WordPress loads its full stack on every request: database connections, theme files, plugin hooks, and template rendering. For anonymous visitors viewing published content, most of this work produces identical output. The Performance bundle short-circuits this by caching the rendered HTML and serving it before WordPress boots.

Beyond caching, WordPress ships with features that most sites never use (emoji scripts, oEmbed, XML-RPC, Heartbeat on the frontend). Each adds kilobytes of JavaScript and CSS, plus server-side overhead. The bundle removes these by default, opting for a "clean slate" approach where developers explicitly re-enable what they need.

---

## Page cache lifecycle

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

---

## CacheableRequestChecker design

The checker applies an elimination cascade to determine if a request should be cached:

1. **Admin page** — not cacheable (personalized UI)
2. **Non-GET method** — not cacheable (state-changing requests)
3. **Logged-in user** — not cacheable (personalized content like admin bar)
4. **Query parameters** — not cacheable (variable output)
5. **Excluded URL prefix** — not cacheable (WordPress internals)

The order is deliberate: cheap checks (admin flag, HTTP method) run first. The logged-in check is more expensive because it reads session cookies.

---

## URL canonicalization

The `RequestUrlResolver` builds the cache key by normalizing the current request URL. The host is validated against the configured `site_url` to prevent cache poisoning via a spoofed `Host` header. Query strings are stripped (requests with query parameters are never cached). This ensures that the same page always produces the same cache key regardless of HTTP header variations.

---

## HTML minification pipeline

`WordPressHtmlOptimizer` uses an **extract-process-restore** strategy to minify HTML without breaking content:

1. **Extract** — `<pre>`, `<code>`, `<textarea>`, `<style>`, and `<script>` blocks are replaced with placeholders (`<!--PRESERVED_0-->`, etc.)
2. **Process** — The remaining HTML is aggressively minified: comments removed, whitespace between block-level elements collapsed, multiple spaces reduced
3. **Restore** — Placeholders are replaced with original content (for preserved blocks) or minified content (for style/script blocks)

### Block vs. inline whitespace

The minifier distinguishes block-level elements (where inter-tag whitespace is insignificant) from inline elements (where a space may be meaningful). Whitespace between block-level tags like `</div><div>` is removed entirely. Whitespace between inline elements is collapsed to a single space to preserve text flow.

### Inline CSS and JavaScript

`CssMinifier` handles inline `<style>` blocks: removes comments, collapses whitespace, strips spaces around CSS punctuation, shortens hex colors (`#aabbcc` to `#abc`), and removes zero units (`0px` to `0`).

`JsMinifier` handles inline `<script>` blocks with a conservative approach: string literals are extracted before processing to prevent corruption, comments are removed (except license comments `/*! */`), and keyword spacing is restored after whitespace collapse. JSON-LD, importmaps, and `application/json` scripts are left untouched because they contain structured data, not executable code.

---

## Unused CSS removal (tree-shaking)

The unused CSS removal feature analyzes the rendered HTML and strips CSS rules from inline `<style>` blocks whose selectors do not match any element on the page. This is implemented across three single-responsibility classes.

### HtmlSelectorExtractor

Scans the HTML markup (with style blocks removed) and builds three lookup maps: classes, IDs, and tag names. Each map uses `array<string, true>` for O(1) lookups.

### SelectorMatcher

Determines if a CSS selector is "used" with deliberately conservative rules:

- **Universal selectors** (`*`, `:root`, `html`, `body`): always kept
- **CSS custom properties** (`--variable`): always kept
- **Comma-separated selectors** (`a, .b, #c`): kept if any sub-selector matches
- **Compound selectors** (`.foo.bar`): all classes must be present
- **Descendant selectors** (`.parent .child`): only the subject (last element) is checked
- **Pseudo-classes and pseudo-elements** (`:hover`, `::before`): stripped before matching

This approach favors keeping a few unnecessary rules over accidentally removing a needed one that would cause a visual defect.

### CssRuleFilter

Iterates through CSS rules and applies `SelectorMatcher` to each selector. All `@-rules` (`@media`, `@supports`, `@keyframes`, `@font-face`, `@import`, `@charset`) are always preserved because removing them could break conditional declarations or nested rules.

CSS parsing uses manual brace-depth tracking rather than regex to correctly handle nested blocks like `@media { .class { ... } }`.

### Execution order

`RemoveUnusedCss` runs at priority 9 on `template_redirect`. `MinifyHtml` runs at the default priority 10. This ensures tree-shaking processes the raw HTML first, then minification compresses the result.

---

## .htaccess directive strategy

### Marker-based injection

Directives are written to `.htaccess` using WordPress's `insert_with_markers()`, which manages a delimited block:

```
# BEGIN BackTo Performance
...directives...
# END BackTo Performance
```

This ensures the framework's directives do not interfere with WordPress core or other plugins. Updates overwrite only the BackTo Performance block. Deactivation removes the block cleanly.

### Apache modules

Each directive group is wrapped in `<IfModule>` for graceful degradation on servers where the module is unavailable:

- **`mod_deflate`** — Gzip compression for text-based resources. Already-compressed formats (images, video, woff2) are excluded via `SetEnvIfNoCase` to avoid double compression.
- **`mod_expires`** — Browser caching with differentiated TTLs. HTML gets TTL 0 (managed by the server-side page cache). Static assets get a configurable TTL (default 1 year). Dynamic data formats (JSON, XML) get TTL 0.
- **`mod_headers`** — `Cache-Control: public, max-age=..., immutable` for static assets (the `immutable` flag prevents conditional requests). ETag removal to avoid unnecessary revalidation. `Connection: keep-alive` for persistent connections.

### Write optimization

Writing to `.htaccess` is expensive (read, parse, write). To avoid redundant writes on every `admin_init`, an MD5 hash of the current directives is stored as a WordPress transient (key: `backto_htaccess_hash`, TTL: 24 hours). The file is only rewritten when the hash changes, meaning subsequent admin page loads trigger no file I/O as long as the configuration remains stable.

---

## Port architecture

The bundle follows the Hexagonal Architecture pattern used throughout the framework. Three port interfaces (`PageCacheInterface`, `HtmlOptimizerInterface`, `DatabaseOptimizerInterface`) separate domain logic from WordPress-specific implementations. This means:

- Hook classes depend on interfaces, not concrete adapters
- The filesystem page cache can be swapped for Redis or Memcached without changing any hook
- Unit tests can mock the interfaces without WordPress running

The port bindings are registered in `PerformanceExtension`, which maps each interface to its WordPress adapter.
