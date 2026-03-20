# Enable unused CSS removal

This strips CSS rules from inline `<style>` blocks whose selectors do not match any element in the page HTML:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->removeUnusedCss(true);
};
```

This is especially effective with block themes that emit per-block inline CSS, where 60-80% of rules may be unused on a given page.

### Preserve additional style blocks

The `global-styles-inline-css` block is preserved by default. To preserve additional style blocks (e.g. WooCommerce inline styles):

```php
$performance
    ->removeUnusedCss(true)
    ->removeUnusedCssPreserveIds([
        'global-styles-inline-css',
        'wc-blocks-inline-css',
    ]);
```

All `@-rules` (`@media`, `@keyframes`, `@font-face`, `@import`, `@supports`) are always kept regardless of selector matching. See the [explanation](../explanation/unused-css-removal-tree-shaking.md) for details on how the selector matching works.
