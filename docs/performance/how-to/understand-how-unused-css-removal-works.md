# Understand how unused CSS removal works

The `RemoveUnusedCss` feature operates via output buffering to strip CSS rules that don't match any element in the page HTML:

1. **HTML analysis** — `HtmlSelectorExtractor` scans the page for used classes, IDs, and HTML tags.
2. **CSS filtering** — `CssRuleFilter` parses each `<style>` block and keeps only rules whose selectors match found elements.
3. **Selector matching** — `SelectorMatcher` handles compound selectors (`.foo.bar`), descendant selectors, pseudo-classes, and combinators.

### What is always preserved

- All `@-rules` (`@media`, `@keyframes`, `@font-face`, `@import`, `@supports`)
- CSS custom properties (`--*`)
- Universal selectors (`*`, `:root`, `html`, `body`)
- Style blocks with IDs listed in `preserveIds` (default: `global-styles-inline-css`)

### Recommended usage

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->removeUnusedCss(true)
        // Preserve WooCommerce inline styles if needed
        ->removeUnusedCssPreserveIds([
            'global-styles-inline-css',
            'wc-blocks-inline-css',
        ]);
};
```

This is most effective with block themes that emit per-block inline CSS, where 60-80% of rules may be unused on a given page.
