# Create a custom tracking script

Use the `TrackingScript` entity directly:

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Entity\TrackingScript;

$services->set('custom_pixel', TrackingScript::class)
    ->args([
        'facebook-pixel',       // handle
        'marketing',            // categoryKey
        '!function(f,b,e,...)', // source (inline code)
        true,                   // inline
        'head',                 // location ('head' or 'footer')
        5,                      // priority
    ]);
```

Or implement `TrackingScriptInterface` for full control.
