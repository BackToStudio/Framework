# Configure CORS for headless WordPress

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\CorsManagerInterface;

$cors = $container->get(CorsManagerInterface::class);

$cors->addAllowedOrigin('https://app.example.com');
$cors->addAllowedOrigin('https://staging.example.com');
$cors->addAllowedMethod(['GET', 'POST', 'PUT', 'DELETE']);
$cors->addAllowedHeader(['Content-Type', 'Authorization', 'X-WP-Nonce']);
$cors->setAllowCredentials(true);
$cors->setMaxAge(86400);
```

> **Warning:** The wildcard `*` is not allowed when `allowCredentials` is `true`. List origins explicitly.
