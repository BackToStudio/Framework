# Purge old audit log events

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityAuditLogger;

$auditLogger = $container->get(SecurityAuditLogger::class);
$purged = $auditLogger->purgeOldEvents(90); // events older than 90 days
```

Export is available from the Audit Log admin page. A 60-second cooldown prevents repeated exports.
