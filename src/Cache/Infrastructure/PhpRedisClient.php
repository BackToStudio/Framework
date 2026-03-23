<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Infrastructure;

use BackTo\Framework\Cache\Contracts\RedisClientInterface;

/**
 * Redis client adapter using the phpredis extension.
 *
 * Requires ext-redis. Falls back gracefully if the extension
 * is not installed (connection will fail at construction time).
 */
final class PhpRedisClient implements RedisClientInterface
{
    private \Redis $redis;

    /**
     * @param string $host     Redis host (default: 127.0.0.1)
     * @param int    $port     Redis port (default: 6379)
     * @param string $password Redis password (empty = no auth)
     * @param int    $database Redis database index (default: 0)
     * @param float  $timeout  Connection timeout in seconds
     * @param string $prefix   Key prefix applied to all operations
     */
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        string $password = '',
        int $database = 0,
        float $timeout = 2.0,
        private readonly string $prefix = '',
    ) {
        if ($host === '') {
            throw new \InvalidArgumentException('Redis host cannot be empty.');
        }

        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException(\sprintf('Redis port must be between 1 and 65535, got %d.', $port));
        }

        if ($database < 0) {
            throw new \InvalidArgumentException(\sprintf('Redis database index must be non-negative, got %d.', $database));
        }

        if ($timeout <= 0.0) {
            throw new \InvalidArgumentException('Redis connection timeout must be positive.');
        }

        $this->redis = new \Redis();
        $this->redis->connect($host, $port, $timeout);

        if ($password !== '') {
            $this->redis->auth($password);
        }

        if ($database !== 0) {
            $this->redis->select($database);
        }
    }

    public function get(string $key): ?string
    {
        $value = $this->redis->get($this->prefix . $key);

        return $value === false ? null : (string) $value;
    }

    public function set(string $key, string $value, int $ttl = 0): bool
    {
        $prefixed = $this->prefix . $key;

        if ($ttl > 0) {
            return $this->redis->setex($prefixed, $ttl, $value);
        }

        return $this->redis->set($prefixed, $value);
    }

    public function del(string $key): bool
    {
        return $this->redis->del($this->prefix . $key) > 0;
    }

    public function exists(string $key): bool
    {
        return (bool) $this->redis->exists($this->prefix . $key);
    }

    public function flushByPrefix(string $prefix): bool
    {
        $pattern = $this->prefix . $prefix . '*';
        $iterator = null;

        do {
            $keys = $this->redis->scan($iterator, $pattern, 100);

            if ($keys !== false && $keys !== []) {
                $this->redis->del(...$keys);
            }
        } while ($iterator > 0);

        return true;
    }

    public function ping(): bool
    {
        try {
            return $this->redis->ping() !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    public function info(): array
    {
        $info = $this->redis->info();

        if (!\is_array($info)) {
            return [];
        }

        return \array_map('strval', $info);
    }
}
