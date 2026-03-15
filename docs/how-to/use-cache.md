# How to Use the Cache System

The Cache module provides three strategies, all compatible with [PSR-16 SimpleCache](https://www.php-fig.org/psr/psr-16/).

## Choose a strategy

| Strategy | Persistence | Best for |
|----------|-------------|----------|
| `MemoryCache` | Request only | Unit tests, short computations |
| `TransientCache` | Database (wp_options) | WordPress-native caching |
| `FilesystemCache` | Disk | Large payloads, no DB overhead |

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

Bind the desired strategy in your `config/services.php`:

```php
$services->set(CacheInterface::class, TransientCache::class)
    ->arg('$prefix', 'mytheme_');
```

## Invalid keys

These characters are reserved and will throw `InvalidArgumentException`: `{}()/\@:`
