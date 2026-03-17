# Cache

PSR-16 SimpleCache implementation with 3 strategies: `MemoryCache` (request-scoped), `TransientCache` (database), `FilesystemCache` (disk).

## Configuration

Cache parameters are managed by `CacheConfiguration` and overridden via the fluent `CacheConfigurator` in `config/cache.php`:

```php
<?php

use BackTo\Framework\Cache\CacheConfigurator;

return static function (CacheConfigurator $cache): void {
    $cache
        ->ttl(7200)
        ->enabled(false);
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `cache.ttl` | `3600` | `ttl(int)` |
| `cache.enabled` | `true` | `enabled(bool)` |

## Contracts

### `CacheInterface`

PSR-16 SimpleCache compatible. Methods: `get`, `set`, `delete`, `clear`, `has`, `getMultiple`, `setMultiple`, `deleteMultiple`.

See [PSR-16 specification](https://www.php-fig.org/psr/psr-16/).
