# Configure image optimization

All image settings are configured via `PerformanceConfigurator`:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->lazyLoadSkipFirst(2)      // first 2 images are not lazy-loaded (default: 1)
        ->addDecodingAsync(true)     // add decoding="async" to images (default: true)
        ->addFetchpriority(true);   // add fetchpriority="high" to first image (default: true)
};
```

### Control how many images skip lazy-loading

The first image (likely the LCP element) is excluded from lazy-loading and receives `fetchpriority="high"` by default. Increase the count if your above-the-fold layout shows multiple images:

```php
$performance->lazyLoadSkipFirst(2);
```

### Disable decoding="async"

```php
$performance->addDecodingAsync(false);
```

### Disable fetchpriority="high"

```php
$performance->addFetchpriority(false);
```
