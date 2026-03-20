# Add Google Analytics (GA4)

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAnalyticsScript;

$services->set(GoogleAnalyticsScript::class)
    ->args(['G-XXXXXXXXXX']);
```

Inline script in the `<head>`, priority 5. Category: `analytics`.
