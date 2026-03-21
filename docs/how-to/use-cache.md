# How to Use the Cache System

The Cache module provides four strategies, all compatible with [PSR-16 SimpleCache](https://www.php-fig.org/psr/psr-16/).

## Choose a strategy

| Strategy | Persistence | Best for |
|----------|-------------|----------|
| `MemoryCache` | Request only | Unit tests, short computations |
| `TransientCache` | Database (wp_options) | WordPress-native caching (default) |
| `FilesystemCache` | Disk | Large payloads, no DB overhead |
| `RedisCache` | Redis server | Production sites, high traffic, shared cache |

## Use MemoryCache

```php
use BackTo\Framework\Cache\Strategy\MemoryCache;

$cache = new MemoryCache();
$cache->set('key', 'value', 300); // TTL in seconds
$value = $cache->get('key', 'default');
```

Values are stored in memory and lost at the end of the PHP request.

## Use TransientCache

```php
use BackTo\Framework\Cache\Strategy\TransientCache;

$cache = new TransientCache('my_prefix_');
$cache->set('key', 'value', 3600); // 1 hour

// Stored as WordPress transient: my_prefix_key
$value = $cache->get('key');
```

The prefix prevents key collisions between plugins. Key length is limited to 172 characters (WordPress transient limit).

## Use FilesystemCache

```php
use BackTo\Framework\Cache\Strategy\FilesystemCache;

$cache = new FilesystemCache('/path/to/cache/dir');
$cache->set('key', $largeData, 86400); // 24 hours
```

Values are serialized to disk using Symfony Filesystem.

## Use RedisCache

Redis provides persistent, high-performance caching shared across requests and servers.

### Configure via CacheConfigurator (recommended)

In your `config/cache.php`:

```php
use BackTo\Framework\Cache\CacheConfigurator;

return static function (CacheConfigurator $cache): void {
    $cache->redis('127.0.0.1', 6379, '', 0);
    // Or with full control:
    // $cache
    //     ->strategy('redis')
    //     ->redisHost('redis.internal')
    //     ->redisPort(6379)
    //     ->redisPassword('secret')
    //     ->redisDatabase(1)
    //     ->redisPrefix('mysite_');
};
```

### Requirements

- PHP extension `ext-redis` (phpredis) must be installed
- Redis server running and reachable

### Health check

When Redis is configured, `RedisHealthCheck` is automatically registered. It checks:
- Ping connectivity
- Memory usage (degraded if > 90% of maxmemory)
- Exposes redis_version, connected_clients, used_memory in metadata

### Available configuration parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `cache.strategy` | `transient` | Active strategy: `transient`, `redis`, `filesystem`, `memory` |
| `cache.redis.host` | `127.0.0.1` | Redis server host |
| `cache.redis.port` | `6379` | Redis server port |
| `cache.redis.password` | `''` | Redis AUTH password |
| `cache.redis.database` | `0` | Redis database index |
| `cache.redis.timeout` | `2.0` | Connection timeout (seconds) |
| `cache.redis.prefix` | `btf_` | Key prefix for namespace isolation |

## DateInterval TTL

All strategies accept `DateInterval` for TTL:

```php
$cache->set('key', 'value', new \DateInterval('PT1H')); // 1 hour
```

## Batch operations

```php
// Set multiple values
$cache->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
], 600);

// Get multiple values
$values = $cache->getMultiple(['key1', 'key2'], 'default');

// Delete multiple keys
$cache->deleteMultiple(['key1', 'key2']);
```

## Inject via DI

Typehint `CacheInterface` in your service constructors:

```php
use BackTo\Framework\Cache\Contracts\CacheInterface;

class MyService
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function expensiveComputation(): array
    {
        if ($this->cache->has('result')) {
            return $this->cache->get('result');
        }

        $result = // ...compute...
        $this->cache->set('result', $result, 3600);

        return $result;
    }
}
```

The active strategy is selected via the `cache.strategy` parameter (see Redis section above). The framework automatically aliases `CacheInterface` to the chosen implementation.

## Invalid keys

These characters are reserved and will throw `InvalidArgumentException`: `{}()/\@:`
