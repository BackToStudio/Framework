# Detect login anomalies (impossible travel, new locations)

The `LoginAnomalyDetector` monitors login patterns and flags suspicious activity:

- **New country:** Login from a country never seen for this user.
- **Impossible travel:** Login from a different location within 1 hour of the previous login.
- **New IP:** Login from an IP not previously associated with the user.

Enable it in your service configuration:

```php
<?php

$services->set(\BackTo\Framework\Bundle\Security\Auth\LoginAnomalyDetector::class)
    ->autowire()
    ->autoconfigure();
```

The detector hooks into `wp_login` (priority 20) and logs anomalies at warning level:

```
[WARNING] Login anomaly detected {"user_id":42, "username":"admin", "anomalies":["new_country","impossible_travel"], "ip":"203.0.113.50", "country":"BR"}
```

It resolves the user's country from CDN/proxy headers (`HTTP_CF_IPCOUNTRY`, `HTTP_X_COUNTRY_CODE`) when behind trusted proxies, or falls back to the `LoginLocationRepositoryInterface` for historical lookups.

To react to anomalies (e.g. send an alert), listen for the log events or extend the class.
