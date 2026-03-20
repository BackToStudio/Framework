# Configure the Heartbeat API

Configure Heartbeat settings via `PerformanceConfigurator`:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->heartbeatDisableFrontend(true)   // disable Heartbeat on frontend (default: true)
        ->heartbeatAdminInterval(60);      // admin interval in seconds (default: 60, WP default: 15)
};
```

### Disable Heartbeat on the frontend

```php
$performance->heartbeatDisableFrontend(true);
```

### Change the admin interval

The admin Heartbeat interval defaults to 60 seconds (WordPress default is 15):

```php
$performance->heartbeatAdminInterval(120); // 120 seconds
```
