# Optimize WooCommerce assets

WooCommerce asset removal is enabled by default. It dequeues WooCommerce styles and scripts on pages that are not WooCommerce pages (cart, checkout, account, product pages).

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->woocommerceOptimize(true);
};
```

On non-WooCommerce pages, the following are dequeued:

- All `woocommerce` and `wc-*` styles
- All `woocommerce` and `wc-*` scripts
- Cart fragments AJAX script

This typically saves 200-500 KB per page load.

To disable:

```php
$performance->woocommerceOptimize(false);
```

If you have custom pages that use WooCommerce shortcodes, exclude those URL prefixes from the page cache as well.
