# Configure Content Security Policy (CSP) headers

Add allowed sources via the `ContentSecurityPolicyInterface`:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\ContentSecurityPolicyInterface;

$csp = $container->get(ContentSecurityPolicyInterface::class);

$csp->addDirective('script-src', 'https://cdn.example.com');
$csp->addDirective('style-src', ['https://fonts.googleapis.com', "'unsafe-inline'"]);
$csp->addDirective('img-src', ['https:', 'data:']);
$csp->addDirective('connect-src', 'https://api.example.com');
```

To test without blocking resources, enable report-only mode:

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security->cspReportOnly(true);
};
```

This sends `Content-Security-Policy-Report-Only` instead of `Content-Security-Policy`.
