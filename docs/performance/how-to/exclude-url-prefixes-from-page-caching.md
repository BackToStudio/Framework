# Exclude URL prefixes from page caching

The `CacheableRequestChecker` excludes `/wp-admin`, `/wp-json`, `/wp-login.php`, `/wp-cron.php`, and `/xmlrpc.php` by default. To add custom prefixes, override the constructor argument in your extension:

```php
<?php

use BackTo\Framework\Bundle\Performance\CacheableRequestChecker;

$containerBuilder->getDefinition(CacheableRequestChecker::class)
    ->setArgument('$excludedPrefixes', [
        '/wp-admin',
        '/wp-json',
        '/wp-login.php',
        '/wp-cron.php',
        '/xmlrpc.php',
        '/my-private-area',
        '/api/custom',
    ]);
```
