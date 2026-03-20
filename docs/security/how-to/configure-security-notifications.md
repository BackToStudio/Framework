# Configure security notifications

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\SecurityNotifierInterface;

$notifier = $container->get(SecurityNotifierInterface::class);

$notifier->setRecipients(['admin@example.com', 'security@example.com']);
$notifier->setFailedLoginThreshold(5);
$notifier->addCriticalEvent('custom_event');
```
