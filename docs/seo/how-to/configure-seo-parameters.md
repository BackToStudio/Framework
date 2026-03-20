# Configure SEO parameters

Use the `SeoConfigurator` in your `config/seo.php` to adjust title separator and default robots directive:

```php
<?php

// config/seo.php
use BackTo\Framework\Bundle\Seo\SeoConfigurator;

return static function (SeoConfigurator $seo): void {
    $seo
        ->titleSeparator('-')
        ->robotsDefault('noindex, nofollow');
};
```
