# Add Gtag Loader with custom category

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GtagLoaderScript;

// Default: analytics category
$services->set(GtagLoaderScript::class)
    ->args(['G-XXXXXXXXXX']);

// Or assign to marketing category
$services->set('gtag_marketing', GtagLoaderScript::class)
    ->args(['AW-XXXXXXXXX', 'marketing']);
```

External script in the `<head>`, priority 4.
