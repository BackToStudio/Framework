# Check for plugin dependencies before activation

Verify that required plugins are active before your plugin boots:

```php
<?php
// plugin.php (main plugin file)

use BackTo\Framework\Bundle\Plugin\PluginKernel;

// Check dependencies before booting
add_action('admin_init', function () {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>My Plugin</strong> requires WooCommerce. The plugin has been deactivated.';
            echo '</p></div>';
        });
        return;
    }
});

// Only boot if dependencies are met
if (!function_exists('WC')) {
    return;
}

$kernel = new PluginKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);

$kernel->boot(
    directory: __DIR__,
    textDomain: 'my-plugin',
);
```
