# Optimize WooCommerce assets

WooCommerce asset removal is enabled by default. It dequeues WooCommerce styles and scripts on pages that are not WooCommerce pages (cart, checkout, account, product pages). This saves 200-500 KB per page load.

To disable:

```php
$performance->woocommerceOptimize(false);
```
