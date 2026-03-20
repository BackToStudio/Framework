# Configure the page cache TTL

Pages are cached for 3600 seconds (1 hour) by default. To change the duration:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(7200); // 2 hours
};
```

For sites with infrequently updated content, a longer TTL (e.g. `86400` for 24 hours) is safe. Auto-invalidation ensures content changes are reflected immediately regardless of TTL.
