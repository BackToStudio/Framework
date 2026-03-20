# Register custom consent categories

Define your own consent categories beyond the defaults:

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Entity\ConsentCategory;

$services->set('consent_category_functional', ConsentCategory::class)
    ->args([
        'functional',                                    // key
        'Functional cookies',                            // label
        'Cookies for enhanced features like chat.',      // description
        false,                                           // required (user can opt out)
    ])
    ->tag('wordpress.consent_category');

$services->set('consent_category_performance', ConsentCategory::class)
    ->args([
        'performance',
        'Performance cookies',
        'Cookies that help us understand how visitors use the site.',
        false,
    ])
    ->tag('wordpress.consent_category');
```

Required categories are pre-checked and disabled in the banner (users cannot opt out).
