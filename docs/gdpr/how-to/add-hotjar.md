# Add Hotjar

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\HotjarScript;

$services->set(HotjarScript::class)
    ->args(['1234567']);
```

Inline script in the `<head>`, priority 10. Category: `analytics`.
