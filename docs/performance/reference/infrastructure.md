# Infrastructure

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
