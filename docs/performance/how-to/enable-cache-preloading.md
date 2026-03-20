# Enable cache preloading

Cache preloading warms the cache in the background after content changes, so visitors always hit a warm cache:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->cachePreloadEnabled(true)
        ->cachePreloadDelay(5)       // seconds before preload fires
        ->cachePreloadBatchSize(50); // max URLs per batch
};
```

Preloading is triggered automatically on post save, publish, theme switch, and Customizer save.
