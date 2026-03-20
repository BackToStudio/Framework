# Obfuscate the login URL

```php
<?php

use BackTo\Framework\Bundle\Security\Hardening\AdminUrlObfuscation;

$obfuscation = $container->get(AdminUrlObfuscation::class);
$obfuscation->setLoginSlug('my-secure-login');
```

Direct access to `/wp-login.php` returns a 404. The `login_url`, `logout_url`, and `site_url` filters are updated automatically.
