# Understand what each cleanup action removes

**`CleanHead`** — Removes RSS feed links, RSD link, WLW manifest link, and relational links (index, parent, start, adjacent posts).

**`RemoveEmojis`** — Removes all emoji-related scripts and styles from the frontend, admin, and emails.

**`RemoveWordPressVersion`** — Hides the WordPress version number from the `<head>` meta tag and RSS feeds.

**`RemoveSvgFilters`** — Removes the global SVG filters injected by WordPress and Gutenberg at `wp_body_open`.

**`RemoveNavigationFallback`** — Disables the fallback rendering of the Navigation block.
