<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\DependencyInjection\Compiler;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Cache\Contracts\RedisClientInterface;
use BackTo\Framework\Cache\HealthCheck\RedisHealthCheck;
use BackTo\Framework\Cache\Infrastructure\PhpRedisClient;
use BackTo\Framework\Cache\Strategy\FilesystemCache;
use BackTo\Framework\Cache\Strategy\MemoryCache;
use BackTo\Framework\Cache\Strategy\RedisCache;
use BackTo\Framework\Cache\Strategy\TransientCache;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

/**
 * Selects the active cache strategy based on the 'cache.strategy' parameter
 * and aliases CacheInterface to the chosen implementation.
 *
 * When strategy is 'redis', also registers the Redis client, RedisCache,
 * and RedisHealthCheck services.
 */
final class SelectCacheStrategyPass implements CompilerPassInterface
{
    private const STRATEGY_MAP = [
        'transient' => TransientCache::class,
        'redis' => RedisCache::class,
        'filesystem' => FilesystemCache::class,
        'memory' => MemoryCache::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        $strategy = $this->stringParam($container, 'cache.strategy', 'transient');

        if ($strategy === 'redis') {
            $this->registerRedisServices($container);
        }

        $class = self::STRATEGY_MAP[$strategy] ?? TransientCache::class;

        $container->setAlias(CacheInterface::class, $class);
    }

    private function registerRedisServices(ContainerBuilder $container): void
    {
        $host = $this->stringParam($container, 'cache.redis.host', '127.0.0.1');
        $port = $this->intParam($container, 'cache.redis.port', 6379);
        $password = $this->stringParam($container, 'cache.redis.password', '');
        $database = $this->intParam($container, 'cache.redis.database', 0);
        $timeout = $this->floatParam($container, 'cache.redis.timeout', 2.0);
        $prefix = $this->stringParam($container, 'cache.redis.prefix', 'btf_');

        // Redis client
        $container->register(PhpRedisClient::class)
            ->addArgument($host)
            ->addArgument($port)
            ->addArgument($password)
            ->addArgument($database)
            ->addArgument($timeout)
            ->addArgument($prefix);

        $container->setAlias(RedisClientInterface::class, PhpRedisClient::class);

        // Redis cache strategy
        $container->register(RedisCache::class)
            ->addArgument(new Reference(RedisClientInterface::class))
            ->addArgument($prefix);

        // Redis health check
        $container->register(RedisHealthCheck::class)
            ->addArgument(new Reference(RedisClientInterface::class))
            ->addTag('wordpress.health_check');
    }

    private function stringParam(ContainerBuilder $container, string $key, string $default): string
    {
        if (!$container->hasParameter($key)) {
            return $default;
        }

        $value = $container->getParameter($key);

        return \is_string($value) ? $value : $default;
    }

    private function intParam(ContainerBuilder $container, string $key, int $default): int
    {
        if (!$container->hasParameter($key)) {
            return $default;
        }

        $value = $container->getParameter($key);

        return \is_int($value) ? $value : $default;
    }

    private function floatParam(ContainerBuilder $container, string $key, float $default): float
    {
        if (!$container->hasParameter($key)) {
            return $default;
        }

        $value = $container->getParameter($key);

        return \is_float($value) || \is_int($value) ? (float) $value : $default;
    }
}
