# Add HubSpot

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\HubSpotScript;

$services->set(HubSpotScript::class)
    ->args(['12345678']);
```

External script in the `<footer>`, priority 10. Category: `marketing`.
