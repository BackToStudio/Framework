# Swap the page cache implementation

To replace the filesystem-based cache with a custom implementation (e.g. Redis), override the DI binding:

```php
<?php

use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;

$containerBuilder->register(PageCacheInterface::class, MyRedisPageCache::class)
    ->setAutowired(true);
```

Your implementation must implement `PageCacheInterface`.
