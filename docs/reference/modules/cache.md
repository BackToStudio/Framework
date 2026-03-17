# Cache

PSR-16 SimpleCache implementation with 3 strategies: `MemoryCache` (request-scoped), `TransientCache` (database), `FilesystemCache` (disk).

## Contracts

### `CacheInterface`

PSR-16 SimpleCache compatible. Methods: `get`, `set`, `delete`, `clear`, `has`, `getMultiple`, `setMultiple`, `deleteMultiple`.

See [PSR-16 specification](https://www.php-fig.org/psr/psr-16/).
