# Register activation and deactivation hooks

Handle plugin lifecycle events outside the kernel (WordPress requires these in the main plugin file):

```php
<?php
// plugin.php

register_activation_hook(__FILE__, function () {
    // Create custom database table
    global $wpdb;
    $table = $wpdb->prefix . 'my_plugin_logs';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY event_type (event_type)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Store plugin version for future migrations
    update_option('my_plugin_version', '1.0.0');

    // Schedule cron events
    if (!wp_next_scheduled('my_plugin_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'my_plugin_daily_cleanup');
    }
});

register_deactivation_hook(__FILE__, function () {
    // Clear scheduled events
    wp_clear_scheduled_hook('my_plugin_daily_cleanup');
});
```

> **Note:** Do not drop tables on deactivation — only on uninstall. Create an `uninstall.php` file at the plugin root for destructive cleanup.
