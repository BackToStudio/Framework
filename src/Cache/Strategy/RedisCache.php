<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Strategy;

use BackTo\Framework\Cache\Contracts\RedisClientInterface;
use DateInterval;

/**
 * PSR-16 cache backed by Redis.
 *
 * Requires a RedisClientInterface implementation (e.g. PhpRedisClient).
 * Values are serialized with PHP serialize() for type preservation.
 * Keys are prefixed to avoid collisions with other applications
 * sharing the same Redis instance.
 */
final class RedisCache extends AbstractCache
{
    private readonly RedisClientInterface $client;
    private readonly string $prefix;

    public function __construct(RedisClientInterface $client, string $prefix = 'btf_')
    {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $value = $this->client->get($this->prefix . $key);

        if ($value === null) {
            return $default;
        }

        return \unserialize($value, ['allowed_classes' => true]);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        return $this->client->set(
            $this->prefix . $key,
            \serialize($value),
            $seconds ?? 0,
        );
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return $this->client->del($this->prefix . $key);
    }

    public function clear(): bool
    {
        return $this->client->flushByPrefix($this->prefix);
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->client->exists($this->prefix . $key);
    }
}
