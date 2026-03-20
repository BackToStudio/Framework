# Handle database migrations on plugin update

Compare the stored version with the current version to run migrations:

```php
<?php

namespace MyPlugin\Core;

use BackTo\Framework\Contracts\HookDispatcherInterface;

final class DatabaseMigration
{
    private const CURRENT_VERSION = '1.2.0';

    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('plugins_loaded', [$this, 'migrate']);
    }

    public function migrate(): void
    {
        $installedVersion = get_option('my_plugin_version', '0.0.0');

        if (version_compare($installedVersion, self::CURRENT_VERSION, '>=')) {
            return;
        }

        if (version_compare($installedVersion, '1.1.0', '<')) {
            $this->migrateToV110();
        }

        if (version_compare($installedVersion, '1.2.0', '<')) {
            $this->migrateToV120();
        }

        update_option('my_plugin_version', self::CURRENT_VERSION);
    }

    private function migrateToV110(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'my_plugin_logs';
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN user_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER message");
    }

    private function migrateToV120(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'my_plugin_logs';
        $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_created_at (created_at)");
    }
}
```
