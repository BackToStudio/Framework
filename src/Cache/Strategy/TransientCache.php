<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Strategy;

use BackTo\Framework\Cache\Contracts\CacheCleanerInterface;
use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use DateInterval;

/**
 * PSR-16 cache backed by WordPress transients.
 *
 * WordPress transients use the database (or object cache if available).
 * Keys are prefixed to avoid collisions and truncated to respect the
 * WordPress transient key limit of 172 characters.
 */
final class TransientCache extends AbstractCache
{
    private readonly string $prefix;
    private readonly CacheCleanerInterface $cacheCleaner;
    private readonly CacheStoreInterface $cacheStore;

    /** @var array<string, mixed> First-level in-memory cache to avoid repeated deserialization */
    private array $memoryCache = [];

    private const MISS_SENTINEL = "\x00__MISS__\x00";

    public function __construct(
        CacheCleanerInterface $cacheCleaner,
        CacheStoreInterface $cacheStore,
        string $prefix = 'btf_',
    ) {
        $this->cacheCleaner = $cacheCleaner;
        $this->cacheStore = $cacheStore;
        $this->prefix = $prefix;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        if (\array_key_exists($key, $this->memoryCache)) {
            return $this->memoryCache[$key] === self::MISS_SENTINEL ? $default : $this->memoryCache[$key];
        }

        $value = $this->cacheStore->get($this->prefixKey($key));

        if ($value === false) {
            $this->memoryCache[$key] = self::MISS_SENTINEL;

            return $default;
        }

        $deserialized = \unserialize($value, ['allowed_classes' => false]);
        $this->memoryCache[$key] = $deserialized;

        return $deserialized;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        $result = $this->cacheStore->set(
            $this->prefixKey($key),
            serialize($value),
            $seconds ?? 0
        );

        if ($result) {
            $this->memoryCache[$key] = $value;
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        unset($this->memoryCache[$key]);

        return $this->cacheStore->delete($this->prefixKey($key));
    }

    public function clear(): bool
    {
        $this->memoryCache = [];

        return $this->cacheCleaner->clearByPrefix($this->prefix);
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        if (\array_key_exists($key, $this->memoryCache)) {
            return $this->memoryCache[$key] !== self::MISS_SENTINEL;
        }

        return $this->cacheStore->get($this->prefixKey($key)) !== false;
    }

    /**
     * Prefix and truncate key to respect WordPress transient name limit (172 chars).
     *
     * Long keys are hashed using SHA-256 (truncated to 64 hex chars) instead of
     * MD5 to reduce collision risk. The original key length is prepended as an
     * additional differentiator.
     */
    private function prefixKey(string $key): string
    {
        $prefixed = $this->prefix . $key;

        if (strlen($prefixed) > 172) {
            $hash = hash('sha256', $key);
            $prefixed = $this->prefix . strlen($key) . '_' . $hash;
        }

        return $prefixed;
    }
}
