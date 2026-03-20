# Add Google Tag Manager

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GoogleTagManagerScript;

$services->set(GoogleTagManagerScript::class)
    ->args(['GTM-XXXXXXX']);
```

Inline script in the `<head>`, priority 1 (loaded first). Category: `analytics`.
