# Set up file integrity monitoring

`FileIntegrityMonitor` tracks changes to critical WordPress files using SHA-256 baselines:

```php
<?php

$services->set(\BackTo\Framework\Bundle\Security\FileIntegrityMonitor::class)
    ->autowire()
    ->autoconfigure();
```

### How it works

1. On first run, a SHA-256 baseline is created for 9 critical files: `wp-config.php`, `.htaccess`, `wp-settings.php`, `wp-login.php`, `wp-load.php`, `wp-blog-header.php`, `index.php`, `wp-cron.php`, `xmlrpc.php`.
2. An hourly WP-Cron job compares current hashes against the baseline.
3. Changes are logged at warning level.

### Manual check

```php
<?php

use BackTo\Framework\Bundle\Security\FileIntegrityMonitor;

$monitor = $container->get(FileIntegrityMonitor::class);

// Force baseline creation
$monitor->createBaseline();

// Run a check
$changes = $monitor->check();
// [
//     'modified' => ['wp-config.php'],
//     'missing'  => [],
//     'added'    => ['suspicious.php'],
// ]

if (!empty($changes['modified']) || !empty($changes['added'])) {
    // Alert — files have been tampered with
}
```
