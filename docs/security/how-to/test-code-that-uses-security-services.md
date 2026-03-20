# Test code that uses security services

Mock the port interfaces in unit tests:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\LoginThrottleInterface;

$throttle = $this->createMock(LoginThrottleInterface::class);
$throttle->method('isThrottled')->willReturn(false);

$loginHardening = new LoginHardening($hookDispatcher, $throttle, $ipResolver, $logger);
```

All security rules depend on port interfaces, never on WordPress directly. Replace any adapter with a mock or test double.
