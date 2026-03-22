<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for Cache module parameters.
 *
 * Used in config/cache.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (CacheConfigurator $cache): void {
 *         $cache
 *             ->ttl(7200)
 *             ->enabled(false);
 *     };
 */
final class CacheConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function ttl(int $seconds): self
    {
        $this->overrides['cache.ttl'] = $seconds;

        return $this;
    }

    public function enabled(bool $enabled): self
    {
        $this->overrides['cache.enabled'] = $enabled;

        return $this;
    }

    /**
     * Set the cache strategy: 'transient', 'redis', 'filesystem', or 'memory'.
     */
    public function strategy(string $strategy): self
    {
        $this->overrides['cache.strategy'] = $strategy;

        return $this;
    }

    public function redisHost(string $host): self
    {
        $this->overrides['cache.redis.host'] = $host;

        return $this;
    }

    public function redisPort(int $port): self
    {
        $this->overrides['cache.redis.port'] = $port;

        return $this;
    }

    public function redisPassword(string $password): self
    {
        $this->overrides['cache.redis.password'] = $password;

        return $this;
    }

    public function redisDatabase(int $database): self
    {
        $this->overrides['cache.redis.database'] = $database;

        return $this;
    }

    public function redisTimeout(float $timeout): self
    {
        $this->overrides['cache.redis.timeout'] = $timeout;

        return $this;
    }

    public function redisPrefix(string $prefix): self
    {
        $this->overrides['cache.redis.prefix'] = $prefix;

        return $this;
    }

    /**
     * Shorthand: configure Redis as the cache strategy.
     */
    public function redis(string $host = '127.0.0.1', int $port = 6379, string $password = '', int $database = 0): self
    {
        return $this
            ->strategy('redis')
            ->redisHost($host)
            ->redisPort($port)
            ->redisPassword($password)
            ->redisDatabase($database);
    }

    /**
     * @return array<string, mixed>
     */
    public function toParameters(): array
    {
        return $this->overrides;
    }
}
