# Restrict REST API access

REST API authentication is required by default (`security.rest_api_require_auth = true`). Public routes (`/oembed/*`, `/wp-site-health/*`) are exempt.

To add custom public routes, pass additional patterns when the service is registered:

```php
<?php

use BackTo\Framework\Bundle\Security\Hardening\RestApiSecurity;

$restSecurity = new RestApiSecurity(
    $hookDispatcher,
    $requestContext,
    $userContext,
    $siteContext,
    additionalPublicPatterns: [
        '#^/myapp/v1/public/#',
        '#^/wc/store/#',
    ]
);
```

Unauthenticated users receive HTTP 401 on protected routes.
