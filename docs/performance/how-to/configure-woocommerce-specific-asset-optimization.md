# Configure WooCommerce-specific asset optimization


WooCommerce asset removal dequeues styles and scripts on non-WooCommerce pages. Customize which pages count as WooCommerce pages:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->woocommerceOptimize(true);
};
```

On non-WooCommerce pages (not cart, checkout, account, or product pages), the following are dequeued:

- All `woocommerce` and `wc-*` styles
- All `woocommerce` and `wc-*` scripts
- Cart fragments AJAX script

This typically saves 200-500 KB per page load. If you have custom pages that use WooCommerce shortcodes, exclude those URL prefixes from the page cache as well.
