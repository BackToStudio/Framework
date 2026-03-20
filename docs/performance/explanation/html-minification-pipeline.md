# HTML minification pipeline

`WordPressHtmlOptimizer` uses an **extract-process-restore** strategy to minify HTML without breaking content:

1. **Extract** — `<pre>`, `<code>`, `<textarea>`, `<style>`, and `<script>` blocks are replaced with placeholders (`<!--PRESERVED_0-->`, etc.)
2. **Process** — The remaining HTML is aggressively minified: comments removed, whitespace between block-level elements collapsed, multiple spaces reduced
3. **Restore** — Placeholders are replaced with original content (for preserved blocks) or minified content (for style/script blocks)

### Block vs. inline whitespace

The minifier distinguishes block-level elements (where inter-tag whitespace is insignificant) from inline elements (where a space may be meaningful). Whitespace between block-level tags like `</div><div>` is removed entirely. Whitespace between inline elements is collapsed to a single space to preserve text flow.

### Inline CSS and JavaScript

`CssMinifier` handles inline `<style>` blocks: removes comments, collapses whitespace, strips spaces around CSS punctuation, shortens hex colors (`#aabbcc` to `#abc`), and removes zero units (`0px` to `0`).

`JsMinifier` handles inline `<script>` blocks with a conservative approach: string literals are extracted before processing to prevent corruption, comments are removed (except license comments `/*! */`), and keyword spacing is restored after whitespace collapse. JSON-LD, importmaps, and `application/json` scripts are left untouched because they contain structured data, not executable code.
