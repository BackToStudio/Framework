# Cache

PSR-16 SimpleCache implementation with 3 strategies.

## Classes

| Class | Role |
|-------|------|
| `Strategy\MemoryCache` | In-memory array (request-scoped) |
| `Strategy\TransientCache` | WordPress transients (database) |
| `Strategy\FilesystemCache` | Disk-based via Symfony Filesystem |
| `Strategy\AbstractCache` | Shared key validation + TTL conversion |
| `Contracts\CacheInterface` | PSR-16 compatible interface |
| `Contracts\InvalidArgumentException` | Exception for invalid cache keys |

## Contracts

### `CacheInterface`

PSR-16 SimpleCache compatible. Methods: `get`, `set`, `delete`, `clear`, `has`, `getMultiple`, `setMultiple`, `deleteMultiple`.

See [PSR-16 specification](https://www.php-fig.org/psr/psr-16/).
