# Tune cache preloading for your site size

Adjust batch size and delay based on your hosting capacity:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->cachePreloadEnabled(true)
        // Small sites (< 100 pages): preload aggressively
        ->cachePreloadBatchSize(100)
        ->cachePreloadDelay(2)

        // Large sites (1000+ pages): be conservative
        // ->cachePreloadBatchSize(25)
        // ->cachePreloadDelay(10)
    ;
};
```

- **Batch size:** Maximum URLs per preload run. Larger = faster warmup, more server load.
- **Delay:** Seconds before preload starts after a content change. Prevents preloading during rapid edits.

Preload requests include `X-Cache-Preload: 1` and `Cache-Control: no-cache` headers, and are sent without cookies as non-blocking requests.
