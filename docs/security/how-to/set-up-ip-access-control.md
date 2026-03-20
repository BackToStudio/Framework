# Set up IP access control

The `IPAccessControl` supports whitelist and blacklist modes with CIDR notation (IPv4 and IPv6).

### Restrict admin access to specific IPs

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\IPAccessControlInterface;

$ipControl = $container->get(IPAccessControlInterface::class);

$ipControl->addToWhitelist('203.0.113.10');
$ipControl->addToWhitelist('198.51.100.0/24');
```

When a whitelist is defined, only whitelisted IPs can access `/wp-admin` and `/wp-login.php`. All others receive HTTP 403.

### Block specific IPs

```php
$ipControl->addToBlacklist('192.0.2.50');
$ipControl->addToBlacklist('2001:db8::/32');
```

If no whitelist is defined, all IPs are allowed except those on the blacklist.
