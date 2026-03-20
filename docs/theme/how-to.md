# Theme Bundle — How-to guides

*How-to — Task-oriented*

Practical recipes for common theme configuration tasks.

---

## Disable a specific cleanup action

All cleanup actions are enabled by default. To keep WordPress emojis, for example, exclude `RemoveEmojis` from service autoloading:

```php
<?php

$services->load('BackTo\\Framework\\Bundle\\Theme\\Actions\\', 'Actions/*')
    ->exclude('Actions/RemoveEmojis.php');
```

The same approach works for any cleanup action (`CleanHead`, `RemoveWordPressVersion`, `RemoveSvgFilters`, `RemoveNavigationFallback`).

---

## Understand what each cleanup action removes

**`CleanHead`** — Removes RSS feed links, RSD link, WLW manifest link, and relational links (index, parent, start, adjacent posts).

**`RemoveEmojis`** — Removes all emoji-related scripts and styles from the frontend, admin, and emails.

**`RemoveWordPressVersion`** — Hides the WordPress version number from the `<head>` meta tag and RSS feeds.

**`RemoveSvgFilters`** — Removes the global SVG filters injected by WordPress and Gutenberg at `wp_body_open`.

**`RemoveNavigationFallback`** — Disables the fallback rendering of the Navigation block.

---

## Load theme translations manually

`LoadThemeTextDomain` is active automatically. It hooks into `after_setup_theme` and calls `load_theme_textdomain()` with the path `{themeDirectory}/languages`.

If you need to change the translation path, override the service in your configuration and pass a different directory.
