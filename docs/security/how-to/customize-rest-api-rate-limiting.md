# Customize REST API rate limiting

Modify the global limits:

```php
<?php

use BackTo\Framework\Bundle\Security\Network\RestApiRateLimiter;

$rateLimiter = $container->get(RestApiRateLimiter::class);

$rateLimiter->setDefaultLimit(100);  // requests per window
$rateLimiter->setDefaultWindow(120); // window in seconds
```

Set per-route limits:

```php
$rateLimiter->setRouteLimit('/jwt-auth/', 5, 300);
$rateLimiter->setRouteLimit('/wp/v2/posts', 200, 60);
```

When a client exceeds the limit, the response is HTTP 429 with headers `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After`.
