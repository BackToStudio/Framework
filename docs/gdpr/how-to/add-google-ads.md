# Add Google Ads

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAdsScript;

$services->set(GoogleAdsScript::class)
    ->args(['AW-XXXXXXXXX']);
```

Inline script in the `<head>`, priority 5. Category: `marketing`.
