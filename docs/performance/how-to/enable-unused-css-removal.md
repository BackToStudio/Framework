# Enable unused CSS removal

This strips CSS rules from inline `<style>` blocks whose selectors do not match any element in the page HTML:

```php
$performance->removeUnusedCss(true);
```

This is especially effective with block themes that emit per-block inline CSS. The `global-styles-inline-css` block is preserved by default. All `@-rules` (`@media`, `@keyframes`, `@font-face`, etc.) are always kept.
